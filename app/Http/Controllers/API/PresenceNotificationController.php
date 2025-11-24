<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PresenceNotificationService;
use App\Models\PresenceIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresenceNotificationController extends Controller
{
    protected $presenceService;

    public function __construct(PresenceNotificationService $presenceService)
    {
        $this->presenceService = $presenceService;
    }

    /**
     * Récupérer les incidents de présence en attente pour l'utilisateur connecté
     * GET /api/presence-notifications/pending
     */
    public function getPending(Request $request)
    {
        $user = $request->user();

        $incidents = PresenceIncident::with(['campus', 'attendance'])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('has_responded', false)
            ->whereDate('incident_date', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'incidents' => $incidents->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'campus_id' => $incident->campus_id,
                    'campus_name' => $incident->campus->name,
                    'incident_date' => $incident->incident_date->format('Y-m-d'),
                    'notification_sent_at' => $incident->notification_sent_at,
                    'response_deadline' => $incident->response_deadline,
                    'minutes_remaining' => $this->getMinutesRemaining($incident),
                ];
            }),
            'count' => $incidents->count(),
        ]);
    }

    /**
     * Répondre à une notification de présence (bouton "OUI")
     * POST /api/presence-notifications/respond
     */
    public function respond(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'incident_id' => 'required|exists:presence_incidents,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $result = $this->presenceService->respondToPresenceCheck(
                $request->incident_id,
                $user,
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'success' => true,
                'message' => 'Présence confirmée avec succès',
                'was_in_zone' => $result['was_in_zone'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Historique des incidents de présence de l'utilisateur
     * GET /api/presence-notifications/history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $incidents = PresenceIncident::with(['campus', 'validator'])
            ->where('user_id', $user->id)
            ->orderBy('incident_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'incidents' => $incidents->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'campus_name' => $incident->campus->name,
                    'incident_date' => $incident->incident_date->format('Y-m-d'),
                    'notification_sent_at' => $incident->notification_sent_at,
                    'has_responded' => $incident->has_responded,
                    'responded_at' => $incident->responded_at?->format('Y-m-d H:i:s'),
                    'was_in_zone' => $incident->was_in_zone,
                    'status' => $incident->status,
                    'penalty_hours' => $incident->penalty_hours,
                    'penalty_applied' => $incident->penalty_applied,
                    'validated_by_name' => $incident->validator?->full_name,
                    'validated_at' => $incident->validated_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'total' => $incidents->total(),
                'per_page' => $incidents->perPage(),
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
            ],
        ]);
    }

    /**
     * Statistiques de présence de l'utilisateur
     * GET /api/presence-notifications/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $totalIncidents = PresenceIncident::where('user_id', $user->id)->count();
        $respondedCount = PresenceIncident::where('user_id', $user->id)
            ->where('has_responded', true)->count();
        $validatedPenalties = PresenceIncident::where('user_id', $user->id)
            ->where('status', 'validated')->count();
        $totalPenaltyHours = PresenceIncident::where('user_id', $user->id)
            ->where('status', 'validated')
            ->sum('penalty_hours');

        $responseRate = $totalIncidents > 0
            ? round(($respondedCount / $totalIncidents) * 100, 2)
            : 100;

        return response()->json([
            'success' => true,
            'stats' => [
                'total_incidents' => $totalIncidents,
                'responded' => $respondedCount,
                'not_responded' => $totalIncidents - $respondedCount,
                'response_rate' => $responseRate,
                'validated_penalties' => $validatedPenalties,
                'total_penalty_hours' => (float) $totalPenaltyHours,
            ],
        ]);
    }

    /**
     * Calculer les minutes restantes avant la deadline
     */
    private function getMinutesRemaining($incident)
    {
        $deadline = \Carbon\Carbon::parse($incident->incident_date->format('Y-m-d') . ' ' . $incident->response_deadline);
        $now = \Carbon\Carbon::now();

        if ($now->gt($deadline)) {
            return 0;
        }

        return $now->diffInMinutes($deadline);
    }
}
