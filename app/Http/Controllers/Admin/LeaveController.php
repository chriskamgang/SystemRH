<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Services\LeaveCalculationService;
use App\Services\PushNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Liste des demandes de congé (admin)
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['user', 'reviewer', 'managerReviewer'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'awaiting_manager') {
                $query->where('status', 'pending')->where('manager_status', 'pending');
            } elseif ($request->status === 'awaiting_rh') {
                $query->where('status', 'pending')->where('manager_status', 'approved');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $leaves = $query->paginate(20);

        $pendingCount = LeaveRequest::where('status', 'pending')->count();
        $awaitingManagerCount = LeaveRequest::where('status', 'pending')->where('manager_status', 'pending')->count();
        $awaitingRhCount = LeaveRequest::where('status', 'pending')
            ->where(function ($q) {
                $q->where('manager_status', 'approved')->orWhereNull('manager_status');
            })->count();

        return view('admin.leaves.index', compact('leaves', 'pendingCount', 'awaitingManagerCount', 'awaitingRhCount'));
    }

    /**
     * Détail d'une demande
     */
    public function show($id)
    {
        $leave = LeaveRequest::with(['user', 'reviewer', 'managerReviewer'])->findOrFail($id);

        $balances = LeaveBalance::where('user_id', $leave->user_id)
            ->where('year', $leave->start_date->year)
            ->get()
            ->keyBy('type');

        // Détail du calcul de quota pour congé annuel
        $quotaBreakdown = null;
        if ($leave->type === 'annual') {
            $quotaBreakdown = LeaveCalculationService::getQuotaBreakdown($leave->user);
        }

        return view('admin.leaves.show', compact('leave', 'balances', 'quotaBreakdown'));
    }

    /**
     * Avis du supérieur hiérarchique (étape 1)
     */
    public function managerApprove(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->manager_status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée par le supérieur.');
        }

        $leave->update([
            'manager_status' => 'approved',
            'manager_reviewed_by' => auth()->id(),
            'manager_reviewed_at' => now(),
            'manager_comment' => $request->comment,
        ]);

        // Notifier l'employé et le RH
        $this->notifyEmployee($leave, 'manager_approved');

        return back()->with('success', 'Avis favorable du supérieur enregistré. En attente de validation RH.');
    }

    /**
     * Rejet par le supérieur hiérarchique
     */
    public function managerReject(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string|max:500']);

        $leave = LeaveRequest::findOrFail($id);

        if ($leave->manager_status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée par le supérieur.');
        }

        $leave->update([
            'manager_status' => 'rejected',
            'status' => 'rejected',
            'manager_reviewed_by' => auth()->id(),
            'manager_reviewed_at' => now(),
            'manager_comment' => $request->comment,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => 'Rejeté par le supérieur hiérarchique : ' . $request->comment,
        ]);

        $this->notifyEmployee($leave, 'rejected');

        return back()->with('success', 'Demande rejetée par le supérieur.');
    }

    /**
     * Approuver une demande (étape 2 - RH)
     */
    public function approve(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if (!$leave->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $request->comment,
        ]);

        // Déduire du solde
        if (!in_array($leave->type, ['unpaid', 'other', 'work_accident'])) {
            $defaultDays = $leave->type === 'annual'
                ? LeaveCalculationService::calculateAnnualQuota($leave->user)
                : (LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0);

            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $leave->user_id, 'year' => $leave->start_date->year, 'type' => $leave->type],
                ['total_days' => $defaultDays, 'used_days' => 0]
            );
            $balance->increment('used_days', $leave->days_count);
        }

        // Notifier l'employé
        $this->notifyEmployee($leave, 'approved');

        return back()->with('success', 'Demande de congé approuvée.');
    }

    /**
     * Rejeter une demande (RH)
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $leave = LeaveRequest::findOrFail($id);

        if (!$leave->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $request->comment,
        ]);

        $this->notifyEmployee($leave, 'rejected');

        return back()->with('success', 'Demande de congé rejetée.');
    }

    /**
     * Gestion des soldes de congés
     */
    public function balances(Request $request)
    {
        $query = User::where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->orderBy('last_name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);
        $year = $request->query('year', now()->year);

        // Charger les soldes pour chaque utilisateur
        foreach ($users as $user) {
            $user->leave_balances = LeaveBalance::where('user_id', $user->id)
                ->where('year', $year)
                ->get()
                ->keyBy('type');

            // Calcul du quota théorique
            $user->quota_annuel = LeaveCalculationService::calculateAnnualQuota($user);
            $user->quota_breakdown = LeaveCalculationService::getQuotaBreakdown($user);
        }

        return view('admin.leaves.balances', compact('users', 'year'));
    }

    /**
     * Recalculer les soldes annuels pour tous les employés
     */
    public function recalculateBalances(Request $request)
    {
        $year = $request->input('year', now()->year);

        $users = User::where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $quota = LeaveCalculationService::calculateAnnualQuota($user);

            LeaveBalance::updateOrCreate(
                ['user_id' => $user->id, 'year' => $year, 'type' => 'annual'],
                ['total_days' => $quota]
            );
            $count++;
        }

        return back()->with('success', "Soldes annuels recalculés pour {$count} employé(s) (année {$year}).");
    }

    /**
     * Mettre à jour le solde d'un employé
     */
    public function updateBalance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:' . implode(',', array_keys(LeaveRequest::TYPES)),
            'total_days' => 'required|integer|min:0',
        ]);

        $year = $request->input('year', now()->year);

        LeaveBalance::updateOrCreate(
            ['user_id' => $request->user_id, 'year' => $year, 'type' => $request->type],
            ['total_days' => $request->total_days]
        );

        return back()->with('success', 'Solde mis à jour.');
    }

    /**
     * Formulaire d'assignation de congé en masse
     */
    public function bulkAssignForm(Request $request)
    {
        $users = User::where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.leaves.bulk-assign', compact('users'));
    }

    /**
     * Assigner un congé à plusieurs employés en même temps
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'type'       => 'required|in:' . implode(',', array_keys(LeaveRequest::TYPES)),
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate   = Carbon::parse($request->end_date);
        $daysCount = (int) ceil(LeaveCalculationService::calculateWorkingDays($startDate, $endDate));

        $assigned = 0;
        $skipped  = [];

        foreach ($request->user_ids as $userId) {
            $overlap = LeaveRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })->exists();

            if ($overlap) {
                $user = User::find($userId);
                $skipped[] = $user->full_name;
                continue;
            }

            $leave = LeaveRequest::create([
                'user_id'        => $userId,
                'type'           => $request->type,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'days_count'     => $daysCount,
                'reason'         => $request->reason ?: 'Congé assigné par l\'administration',
                'status'         => 'approved',
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now(),
                'review_comment' => 'Assigné en masse par l\'administration',
            ]);

            if (!in_array($leave->type, ['unpaid', 'other', 'work_accident'])) {
                $user = User::find($userId);
                $defaultDays = $leave->type === 'annual'
                    ? LeaveCalculationService::calculateAnnualQuota($user)
                    : (LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0);

                $balance = LeaveBalance::firstOrCreate(
                    ['user_id' => $userId, 'year' => $startDate->year, 'type' => $leave->type],
                    ['total_days' => $defaultDays, 'used_days' => 0]
                );
                $balance->increment('used_days', $daysCount);
            }

            $this->notifyEmployee($leave, 'approved');
            $assigned++;
        }

        $msg = "Congé de {$daysCount} jour(s) ouvrable(s) assigné à {$assigned} employé(s) du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}.";
        if (!empty($skipped)) {
            $msg .= ' Ignorés (congé existant) : ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('admin.leaves.index')->with('success', $msg);
    }

    /**
     * Formulaire d'assignation directe de congé
     */
    public function assignForm(Request $request)
    {
        $users = User::where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selectedUserId = $request->query('user_id');

        return view('admin.leaves.assign', compact('users', 'selectedUserId'));
    }

    /**
     * Assigner directement un congé à un employé
     */
    public function assign(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'type'       => 'required|in:' . implode(',', array_keys(LeaveRequest::TYPES)),
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate   = Carbon::parse($request->end_date);
        $daysCount = (int) ceil(LeaveCalculationService::calculateWorkingDays($startDate, $endDate));

        $overlap = LeaveRequest::where('user_id', $request->user_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })->first();

        if ($overlap) {
            return back()->withInput()
                ->with('error', 'Un congé approuvé existe déjà sur cette période ('
                    . $overlap->start_date->format('d/m/Y') . ' au ' . $overlap->end_date->format('d/m/Y') . ').');
        }

        $leave = LeaveRequest::create([
            'user_id'        => $request->user_id,
            'type'           => $request->type,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'days_count'     => $daysCount,
            'reason'         => $request->reason ?: 'Congé assigné par l\'administration',
            'status'         => 'approved',
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
            'review_comment' => 'Assigné directement par l\'administration',
        ]);

        if (!in_array($leave->type, ['unpaid', 'other', 'work_accident'])) {
            $user = User::find($request->user_id);
            $defaultDays = $leave->type === 'annual'
                ? LeaveCalculationService::calculateAnnualQuota($user)
                : (LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0);

            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $leave->user_id, 'year' => $startDate->year, 'type' => $leave->type],
                ['total_days' => $defaultDays, 'used_days' => 0]
            );
            $balance->increment('used_days', $daysCount);
        }

        $this->notifyEmployee($leave, 'approved');

        $user = User::find($request->user_id);
        return redirect()->route('admin.leaves.index')
            ->with('success', "Congé de {$daysCount} jour(s) ouvrable(s) assigné à {$user->full_name} du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}.");
    }

    /**
     * Annuler un congé
     */
    public function cancel(string $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status === 'approved' && !in_array($leave->type, ['unpaid', 'other', 'work_accident'])) {
            $balance = LeaveBalance::where('user_id', $leave->user_id)
                ->where('year', $leave->start_date->year)
                ->where('type', $leave->type)
                ->first();
            if ($balance) {
                $balance->decrement('used_days', $leave->days_count);
            }
        }

        $leave->update([
            'status'         => 'cancelled',
            'review_comment' => ($leave->review_comment ? $leave->review_comment . ' | ' : '') . 'Annulé par l\'administration le ' . now()->format('d/m/Y'),
        ]);

        return back()->with('success', 'Congé annulé.');
    }

    /**
     * Générer la lettre de mise en congé PDF
     */
    public function downloadLetter($id)
    {
        $leave = LeaveRequest::with(['user.department', 'user.company', 'reviewer'])->findOrFail($id);

        if ($leave->status !== 'approved') {
            return back()->with('error', 'Seuls les congés approuvés peuvent générer une lettre.');
        }

        $pdf = Pdf::loadView('admin.leaves.letter-pdf', [
            'leave' => $leave,
            'user' => $leave->user,
            'company' => $leave->user->company,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("lettre-conge-{$leave->user->employee_id}-{$leave->start_date->format('Ymd')}.pdf");
    }

    // ========== JOURS FÉRIÉS ==========

    /**
     * Gestion des jours fériés
     */
    public function holidays(Request $request)
    {
        $holidays = PublicHoliday::orderBy('date')->get();

        return view('admin.leaves.holidays', compact('holidays'));
    }

    /**
     * Ajouter un jour férié
     */
    public function storeHoliday(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'boolean',
        ]);

        PublicHoliday::create([
            'name' => $request->name,
            'date' => $request->date,
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        return back()->with('success', 'Jour férié ajouté.');
    }

    /**
     * Supprimer un jour férié
     */
    public function destroyHoliday($id)
    {
        PublicHoliday::findOrFail($id)->delete();
        return back()->with('success', 'Jour férié supprimé.');
    }

    /**
     * Initialiser les jours fériés du Cameroun
     */
    public function seedCameroonHolidays()
    {
        $count = 0;
        foreach (LeaveCalculationService::CAMEROON_HOLIDAYS as $holiday) {
            $exists = PublicHoliday::where('name', $holiday['name'])->exists();
            if (!$exists) {
                PublicHoliday::create([
                    'name' => $holiday['name'],
                    'date' => Carbon::createFromDate(now()->year, $holiday['month'], $holiday['day']),
                    'is_recurring' => true,
                ]);
                $count++;
            }
        }

        return back()->with('success', "{$count} jour(s) férié(s) camerounais ajouté(s).");
    }

    /**
     * API : Calculer les jours ouvrables entre deux dates (AJAX)
     */
    public function calculateDays(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $days = LeaveCalculationService::calculateWorkingDays(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'success' => true,
            'working_days' => $days,
            'working_days_rounded' => (int) ceil($days),
        ]);
    }

    /**
     * Notifier l'employé par push notification
     */
    private function notifyEmployee(LeaveRequest $leave, string $decision)
    {
        try {
            $user = $leave->user;
            if (!$user->fcm_token) return;

            $titles = [
                'approved' => 'Congé approuvé',
                'rejected' => 'Congé refusé',
                'manager_approved' => 'Avis favorable du supérieur',
            ];

            $bodies = [
                'approved' => "Votre demande de {$leave->getTypeLabel()} du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')} a été approuvée.",
                'rejected' => "Votre demande de {$leave->getTypeLabel()} du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')} a été refusée.",
                'manager_approved' => "Votre supérieur a donné un avis favorable pour votre congé du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')}. En attente de validation RH.",
            ];

            $pushService = new PushNotificationService();
            $pushService->sendToUser($user, $titles[$decision] ?? 'Congé', $bodies[$decision] ?? '', [
                'type' => 'leave_decision',
                'leave_id' => (string) $leave->id,
                'decision' => $decision,
            ], 'leave');
        } catch (\Exception $e) {
            \Log::warning('Erreur notification congé: ' . $e->getMessage());
        }
    }
}
