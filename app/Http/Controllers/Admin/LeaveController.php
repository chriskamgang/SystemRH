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
