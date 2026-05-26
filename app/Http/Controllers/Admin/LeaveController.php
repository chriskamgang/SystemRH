<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Liste des demandes de congé (admin)
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        return view('admin.leaves.index', compact('leaves', 'pendingCount'));
    }

    /**
     * Détail d'une demande
     */
    public function show($id)
    {
        $leave = LeaveRequest::with(['user', 'reviewer'])->findOrFail($id);

        $balances = LeaveBalance::where('user_id', $leave->user_id)
            ->where('year', $leave->start_date->year)
            ->get()
            ->keyBy('type');

        return view('admin.leaves.show', compact('leave', 'balances'));
    }

    /**
     * Approuver une demande
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
        if (!in_array($leave->type, ['unpaid', 'other'])) {
            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $leave->user_id, 'year' => $leave->start_date->year, 'type' => $leave->type],
                ['total_days' => LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0, 'used_days' => 0]
            );
            $balance->increment('used_days', $leave->days_count);
        }

        // Notifier l'employé
        $this->notifyEmployee($leave, 'approved');

        return back()->with('success', 'Demande de congé approuvée.');
    }

    /**
     * Rejeter une demande
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
        }

        return view('admin.leaves.balances', compact('users', 'year'));
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
     * Formulaire d'assignation de congé en masse (plusieurs employés, même période)
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

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate   = \Carbon\Carbon::parse($request->end_date);
        $daysCount = $startDate->diffInDays($endDate) + 1;

        $assigned = 0;
        $skipped  = [];

        foreach ($request->user_ids as $userId) {
            // Vérifier chevauchement
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

            // Déduire du solde si applicable
            if (!in_array($leave->type, ['unpaid', 'other'])) {
                $balance = LeaveBalance::firstOrCreate(
                    ['user_id' => $userId, 'year' => $startDate->year, 'type' => $leave->type],
                    ['total_days' => LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0, 'used_days' => 0]
                );
                $balance->increment('used_days', $daysCount);
            }

            $this->notifyEmployee($leave, 'approved');
            $assigned++;
        }

        $msg = "Congé de {$daysCount} jour(s) assigné à {$assigned} employé(s) du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}.";
        if (!empty($skipped)) {
            $msg .= ' Ignorés (congé existant) : ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('admin.leaves.index')->with('success', $msg);
    }

    /**
     * Formulaire d'assignation directe de congé par l'administration
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
     * Assigner directement un congé à un employé (sans demande de sa part)
     * Le congé est immédiatement approuvé — la biométrie en tiendra compte
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

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate   = \Carbon\Carbon::parse($request->end_date);
        $daysCount = $startDate->diffInDays($endDate) + 1;

        // Vérifier qu'il n'y a pas déjà un congé approuvé qui chevauche cette période
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
            'status'         => 'approved',          // Directement approuvé
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
            'review_comment' => 'Assigné directement par l\'administration',
        ]);

        // Déduire du solde si applicable
        if (!in_array($leave->type, ['unpaid', 'other'])) {
            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $leave->user_id, 'year' => $startDate->year, 'type' => $leave->type],
                ['total_days' => LeaveRequest::DEFAULT_BALANCES[$leave->type] ?? 0, 'used_days' => 0]
            );
            $balance->increment('used_days', $daysCount);
        }

        // Notifier l'employé
        $this->notifyEmployee($leave, 'approved');

        $user = User::find($request->user_id);
        return redirect()->route('admin.leaves.index')
            ->with('success', "Congé de {$daysCount} jour(s) assigné à {$user->full_name} du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}. La biométrie en tiendra compte automatiquement.");
    }

    /**
     * Annuler un congé assigné (par l'administration)
     */
    public function cancel(string $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        // Remettre les jours dans le solde si nécessaire
        if ($leave->status === 'approved' && !in_array($leave->type, ['unpaid', 'other'])) {
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

        return back()->with('success', 'Congé annulé. La biométrie ne le prendra plus en compte.');
    }

    /**
     * Notifier l'employé par push notification
     */
    private function notifyEmployee(LeaveRequest $leave, string $decision)
    {
        try {
            $user = $leave->user;
            if (!$user->fcm_token) return;

            $title = $decision === 'approved'
                ? 'Congé approuvé'
                : 'Congé refusé';

            $body = $decision === 'approved'
                ? "Votre demande de {$leave->getTypeLabel()} du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')} a été approuvée."
                : "Votre demande de {$leave->getTypeLabel()} du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')} a été refusée.";

            $pushService = new PushNotificationService();
            $pushService->sendToUser($user, $title, $body, [
                'type' => 'leave_decision',
                'leave_id' => (string) $leave->id,
                'decision' => $decision,
            ], 'leave');
        } catch (\Exception $e) {
            \Log::warning('Erreur notification congé: ' . $e->getMessage());
        }
    }
}
