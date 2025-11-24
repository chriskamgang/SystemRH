<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PresenceCheck;
use App\Models\Campus;
use Illuminate\Http\Request;

class PresenceCheckController extends Controller
{
    /**
     * Liste des vérifications en attente pour l'utilisateur connecté
     */
    public function pending(Request $request)
    {
        $user = $request->user();

        // Récupérer les vérifications sans réponse (envoyées dans les dernières 24h)
        $pendingChecks = PresenceCheck::where('user_id', $user->id)
            ->whereNull('response')
            ->where('check_time', '>=', now()->subDay())
            ->with(['campus'])
            ->orderBy('check_time', 'desc')
            ->get();

        return response()->json([
            'pending_checks' => $pendingChecks,
            'total' => $pendingChecks->count(),
        ], 200);
    }

    /**
     * Répondre à une vérification de présence
     */
    public function respond(Request $request)
    {
        $request->validate([
            'presence_check_id' => 'required|exists:presence_checks,id',
            'response' => 'required|in:present,absent',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $presenceCheck = PresenceCheck::findOrFail($request->presence_check_id);

        // Vérifier que cette vérification appartient à l'utilisateur
        if ($presenceCheck->user_id !== $user->id) {
            return response()->json([
                'message' => 'Cette vérification ne vous appartient pas.',
            ], 403);
        }

        // Vérifier si déjà répondu
        if ($presenceCheck->response) {
            return response()->json([
                'message' => 'Vous avez déjà répondu à cette vérification.',
                'previous_response' => $presenceCheck->response,
                'response_time' => $presenceCheck->response_time,
            ], 400);
        }

        // Vérifier si la vérification n'est pas trop ancienne (> 24h)
        if ($presenceCheck->check_time->lt(now()->subDay())) {
            return response()->json([
                'message' => 'Cette vérification est expirée.',
            ], 400);
        }

        $campus = Campus::findOrFail($presenceCheck->campus_id);

        // Vérifier si l'utilisateur est dans la zone
        $isInZone = $campus->isUserInZone($request->latitude, $request->longitude);

        // Mettre à jour la vérification
        $presenceCheck->update([
            'response' => $request->response,
            'response_time' => now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_in_zone' => $isInZone,
        ]);

        // Si l'utilisateur dit "present" mais n'est pas dans la zone
        $warning = null;
        if ($request->response === 'present' && !$isInZone) {
            $warning = 'Vous avez répondu "présent" mais vous n\'êtes pas dans la zone du campus.';
        }

        // Si l'utilisateur dit "absent" mais est dans la zone
        if ($request->response === 'absent' && $isInZone) {
            $warning = 'Vous avez répondu "absent" mais vous êtes dans la zone du campus.';
        }

        $presenceCheck->load('campus');

        return response()->json([
            'message' => 'Réponse enregistrée avec succès.',
            'presence_check' => $presenceCheck,
            'is_in_zone' => $isInZone,
            'warning' => $warning,
        ], 200);
    }

    /**
     * Historique des vérifications de présence
     */
    public function history(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'response' => 'nullable|in:present,absent',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $query = PresenceCheck::where('user_id', $user->id)
            ->with(['campus']);

        // Filtres
        if ($request->start_date) {
            $query->whereDate('check_time', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('check_time', '<=', $request->end_date);
        }

        if ($request->response) {
            $query->where('response', $request->response);
        }

        $perPage = $request->per_page ?? 20;
        $checks = $query->orderBy('check_time', 'desc')->paginate($perPage);

        return response()->json([
            'presence_checks' => $checks,
        ], 200);
    }

    /**
     * Statistiques des vérifications de présence
     */
    public function stats(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $query = PresenceCheck::where('user_id', $user->id)
            ->whereBetween('check_time', [$startDate, $endDate]);

        // Total de vérifications envoyées
        $totalSent = $query->count();

        // Total répondu
        $totalResponded = (clone $query)->whereNotNull('response')->count();

        // Réponses "present"
        $totalPresent = (clone $query)->where('response', 'present')->count();

        // Réponses "absent"
        $totalAbsent = (clone $query)->where('response', 'absent')->count();

        // Non répondu
        $totalNoResponse = $totalSent - $totalResponded;

        // Réponses "present" hors zone
        $presentOutOfZone = (clone $query)
            ->where('response', 'present')
            ->where('is_in_zone', false)
            ->count();

        // Taux de réponse
        $responseRate = $totalSent > 0
            ? round(($totalResponded / $totalSent) * 100, 2)
            : 0;

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => [
                'total_sent' => $totalSent,
                'total_responded' => $totalResponded,
                'total_no_response' => $totalNoResponse,
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'present_out_of_zone' => $presentOutOfZone,
                'response_rate' => $responseRate,
            ],
        ], 200);
    }

    /**
     * Détails d'une vérification spécifique
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $presenceCheck = PresenceCheck::with(['campus'])->findOrFail($id);

        // Vérifier que cette vérification appartient à l'utilisateur
        if ($presenceCheck->user_id !== $user->id) {
            return response()->json([
                'message' => 'Cette vérification ne vous appartient pas.',
            ], 403);
        }

        return response()->json([
            'presence_check' => $presenceCheck,
        ], 200);
    }
}
