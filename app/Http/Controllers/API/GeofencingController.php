<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GeofencingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeofencingController extends Controller
{
    /**
     * Déclencher une notification de géofencing quand l'app détecte une entrée en zone
     * POST /api/geofencing/entry
     */
    public function onGeofenceEntry(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();

        $result = GeofencingService::sendGeofenceEntryNotification(
            $user->id,
            $request->campus_id
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'notification_sent' => $result['notification_sent'],
                    'geofence_notification_id' => $result['geofence_notification_id'] ?? null,
                ],
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }
    }

    /**
     * Marquer une notification de géofencing comme cliquée
     * POST /api/geofencing/clicked
     */
    public function markAsClicked(Request $request)
    {
        $request->validate([
            'geofence_notification_id' => 'required|exists:geofence_notifications,id',
        ]);

        $success = GeofencingService::markAsClicked($request->geofence_notification_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification marquée comme cliquée' : 'Erreur lors de la mise à jour',
        ]);
    }

    /**
     * Marquer une notification de géofencing comme ignorée
     * POST /api/geofencing/ignored
     */
    public function markAsIgnored(Request $request)
    {
        $request->validate([
            'geofence_notification_id' => 'required|exists:geofence_notifications,id',
        ]);

        $success = GeofencingService::markAsIgnored($request->geofence_notification_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification marquée comme ignorée' : 'Erreur lors de la mise à jour',
        ]);
    }

    /**
     * Obtenir le statut du géofencing (pour l'app mobile)
     * GET /api/geofencing/status
     */
    public function getStatus()
    {
        $enabled = \App\Models\Setting::get('geofence_notification_enabled', true);
        $cooldown = \App\Models\Setting::get('geofence_notification_cooldown_minutes', 360);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'cooldown_minutes' => $cooldown,
            ],
        ]);
    }
}
