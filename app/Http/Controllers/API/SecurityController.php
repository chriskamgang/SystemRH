<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    /**
     * Signaler une violation de sécurité depuis l'app mobile
     */
    public function reportViolation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'violation_type' => 'required|array',
            'violation_type.vpn' => 'nullable|boolean',
            'violation_type.mock' => 'nullable|boolean',
            'violation_type.root' => 'nullable|boolean',
            'violation_type.emulator' => 'nullable|boolean',
            'violation_type.gps_inconsistent' => 'nullable|boolean',
            'device_info' => 'nullable|array',
            'occurred_at' => 'required|date',
        ]);

        // Créer la violation
        $violation = new SecurityViolation();
        $violation->user_id = $validated['user_id'];
        $violation->violation_type = $validated['violation_type'];
        $violation->device_info = $validated['device_info'] ?? [];
        $violation->ip_address = $request->ip();
        $violation->user_agent = $request->userAgent();
        $violation->occurred_at = $validated['occurred_at'];

        // Calculer la sévérité automatiquement
        $violation->severity = $violation->calculateSeverity();
        $violation->save();

        // Logger l'événement
        Log::warning('Security violation detected', [
            'user_id' => $validated['user_id'],
            'violations' => $validated['violation_type'],
            'severity' => $violation->severity,
        ]);

        // Vérifier les violations répétées
        $this->checkRepeatedViolations($validated['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'Violation de sécurité signalée',
            'violation_id' => $violation->id,
        ]);
    }

    /**
     * Vérifier si l'utilisateur a des violations répétées
     */
    private function checkRepeatedViolations($userId)
    {
        // Compter les violations dans les dernières 24h
        $violationsCount = SecurityViolation::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $user = User::find($userId);

        if ($violationsCount >= 3 && $user->account_status !== 'suspended') {
            // Suspendre le compte après 3 violations
            $user->update(['account_status' => 'suspended']);

            Log::alert('User account suspended due to repeated security violations', [
                'user_id' => $userId,
                'violations_count' => $violationsCount,
            ]);

            // TODO: Envoyer notification aux admins
            // Notification::send(
            //     User::where('role', 'admin')->get(),
            //     new SecurityViolationAlert($user, $violationsCount)
            // );
        } elseif ($violationsCount >= 1) {
            // Avertir les admins même pour 1 violation
            Log::warning('Security violation alert', [
                'user_id' => $userId,
                'violations_count' => $violationsCount,
            ]);
        }

        return $violationsCount;
    }

    /**
     * Vérifier le statut de sécurité avant pointage (optionnel)
     */
    public function checkSecurityStatus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        // Vérifier si compte suspendu
        if ($user->account_status === 'suspended') {
            return response()->json([
                'allowed' => false,
                'reason' => 'account_suspended',
                'message' => 'Votre compte a été suspendu. Contactez votre administrateur.',
            ], 403);
        }

        // Compter les violations récentes
        $recentViolations = SecurityViolation::where('user_id', $validated['user_id'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentViolations >= 3) {
            return response()->json([
                'allowed' => false,
                'reason' => 'too_many_violations',
                'message' => 'Trop de tentatives de fraude détectées. Compte bloqué.',
            ], 403);
        }

        return response()->json([
            'allowed' => true,
            'message' => 'Sécurité OK',
            'violations_count' => $recentViolations,
        ]);
    }

    /**
     * Obtenir l'historique des violations d'un utilisateur
     */
    public function getUserViolations(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $violations = SecurityViolation::where('user_id', $validated['user_id'])
            ->orderBy('created_at', 'desc')
            ->limit($validated['limit'] ?? 20)
            ->get()
            ->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'violation_types' => $violation->getViolationTypesFormatted(),
                    'severity' => $violation->severity,
                    'occurred_at' => $violation->occurred_at->toIso8601String(),
                    'status' => $violation->status,
                ];
            });

        return response()->json([
            'success' => true,
            'violations' => $violations,
            'total' => $violations->count(),
        ]);
    }
}
