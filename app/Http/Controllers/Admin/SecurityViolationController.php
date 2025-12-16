<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityViolationController extends Controller
{
    /**
     * Afficher la liste des violations de sécurité
     */
    public function index(Request $request)
    {
        $query = SecurityViolation::with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $violations = $query->paginate(20);

        // Statistiques
        $stats = [
            'total' => SecurityViolation::count(),
            'pending' => SecurityViolation::where('status', 'pending')->count(),
            'high_severity' => SecurityViolation::whereIn('severity', ['high', 'critical'])->count(),
            'today' => SecurityViolation::whereDate('created_at', today())->count(),
            'suspended_users' => User::where('account_status', 'suspended')->count(),
        ];

        // Liste des utilisateurs pour le filtre
        $users = User::select('id', 'first_name', 'last_name')
            ->whereHas('securityViolations')
            ->orderBy('first_name')
            ->get();

        return view('admin.security.violations.index', compact('violations', 'stats', 'users'));
    }

    /**
     * Afficher les détails d'une violation
     */
    public function show($id)
    {
        $violation = SecurityViolation::with(['user', 'reviewer'])->findOrFail($id);

        // Historique de l'utilisateur
        $userViolations = SecurityViolation::where('user_id', $violation->user_id)
            ->where('id', '!=', $violation->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Violations récentes (24h)
        $recentViolations = SecurityViolation::where('user_id', $violation->user_id)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return view('admin.security.violations.show', compact('violation', 'userViolations', 'recentViolations'));
    }

    /**
     * Marquer une violation comme révisée
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'action' => 'required|in:dismiss,suspend,warn',
        ]);

        $violation = SecurityViolation::findOrFail($id);

        $violation->update([
            'status' => 'reviewed',
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Actions supplémentaires selon le choix
        if ($request->action === 'suspend') {
            $violation->user->update(['account_status' => 'suspended']);
            $violation->update(['status' => 'action_taken']);
            $message = 'Violation révisée et utilisateur suspendu';
        } elseif ($request->action === 'dismiss') {
            $violation->update(['status' => 'dismissed']);
            $message = 'Violation ignorée (faux positif)';
        } else {
            $message = 'Violation révisée avec avertissement';
        }

        return redirect()->route('admin.security.violations.index')
            ->with('success', $message);
    }

    /**
     * Suspendre/Réactiver un utilisateur
     */
    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->account_status === 'suspended') {
            $user->update(['account_status' => 'active']);
            $message = 'Compte réactivé avec succès';
        } else {
            $user->update(['account_status' => 'suspended']);
            $message = 'Compte suspendu avec succès';
        }

        return back()->with('success', $message);
    }

    /**
     * Dashboard des statistiques de sécurité
     */
    public function dashboard()
    {
        // Statistiques générales
        $stats = [
            'total_violations' => SecurityViolation::count(),
            'today' => SecurityViolation::whereDate('created_at', today())->count(),
            'this_week' => SecurityViolation::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => SecurityViolation::where('created_at', '>=', now()->startOfMonth())->count(),
            'pending' => SecurityViolation::where('status', 'pending')->count(),
            'critical' => SecurityViolation::where('severity', 'critical')->count(),
            'suspended_users' => User::where('account_status', 'suspended')->count(),
        ];

        // Violations récentes à haute sévérité
        $criticalViolations = SecurityViolation::with('user')
            ->whereIn('severity', ['high', 'critical'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top utilisateurs avec violations
        $topOffenders = User::withCount([
            'securityViolations' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        ])
        ->having('security_violations_count', '>', 0)
        ->orderBy('security_violations_count', 'desc')
        ->limit(10)
        ->get();

        // Violations par type
        $violationsByType = SecurityViolation::where('created_at', '>=', now()->subDays(30))
            ->get()
            ->flatMap(function ($violation) {
                $types = [];
                foreach ($violation->violation_type as $type => $value) {
                    if ($value) {
                        $types[] = $type;
                    }
                }
                return $types;
            })
            ->countBy()
            ->sortDesc();

        return view('admin.security.dashboard', compact('stats', 'criticalViolations', 'topOffenders', 'violationsByType'));
    }
}
