<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campus;
use App\Models\GeofenceNotification;
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;

class GeofencingService
{
    /**
     * Envoyer une notification quand un utilisateur entre dans la zone d'un campus
     */
    public static function sendGeofenceEntryNotification(int $userId, int $campusId): array
    {
        try {
            // Vérifier si le géofencing est activé
            if (!Setting::get('geofence_notification_enabled', true)) {
                return [
                    'success' => false,
                    'message' => 'Géofencing désactivé',
                ];
            }

            $user = User::find($userId);
            $campus = Campus::find($campusId);

            if (!$user || !$campus) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur ou campus introuvable',
                ];
            }

            // Vérifier que l'utilisateur est actif
            if (!$user->is_active) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur inactif',
                ];
            }

            // Vérifier que le campus est actif
            if (!$campus->is_active) {
                return [
                    'success' => false,
                    'message' => 'Campus inactif',
                ];
            }

            // Vérifier que l'utilisateur est assigné à ce campus
            if (!$user->campuses->contains($campus->id)) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur non assigné à ce campus',
                ];
            }

            // Vérifier le cooldown (anti-spam)
            if (!GeofenceNotification::canSendNotification($userId, $campusId)) {
                return [
                    'success' => false,
                    'message' => 'Cooldown actif - notification déjà envoyée récemment',
                ];
            }

            // Vérifier si l'utilisateur a déjà un check-in actif sur ce campus
            $hasActiveCheckIn = \App\Models\Attendance::where('user_id', $userId)
                ->where('campus_id', $campusId)
                ->where('type', 'check-in')
                ->whereDate('timestamp', today())
                ->get()
                ->filter(function ($checkIn) use ($userId, $campusId) {
                    // Vérifier s'il n'y a pas de check-out correspondant
                    return !\App\Models\Attendance::where('user_id', $userId)
                        ->where('campus_id', $campusId)
                        ->where('type', 'check-out')
                        ->where('shift', $checkIn->shift)
                        ->where('timestamp', '>', $checkIn->timestamp)
                        ->whereDate('timestamp', today())
                        ->exists();
                })
                ->isNotEmpty();

            if ($hasActiveCheckIn) {
                return [
                    'success' => false,
                    'message' => 'Utilisateur déjà check-in sur ce campus',
                ];
            }

            // Créer l'enregistrement de notification de géofencing
            $geofenceNotification = GeofenceNotification::create([
                'user_id' => $userId,
                'campus_id' => $campusId,
                'sent_at' => now(),
                'action_taken' => 'pending',
            ]);

            // Envoyer la notification FCM
            $notificationSent = false;
            if ($user->fcm_token) {
                $notificationSent = self::sendFCMNotification($user, $campus, $geofenceNotification);
            }

            // Créer une notification dans la DB
            Notification::create([
                'user_id' => $userId,
                'type' => 'geofence_entry',
                'title' => "Vous êtes dans le {$campus->name}",
                'body' => 'Cliquez ici pour faire votre check-in rapidement !',
                'is_read' => false,
                'sent_at' => now(),
                'delivery_status' => $notificationSent ? 'sent' : 'failed',
                'data' => json_encode([
                    'campus_id' => $campusId,
                    'campus_name' => $campus->name,
                    'geofence_notification_id' => $geofenceNotification->id,
                    'action' => 'quick_checkin',
                ]),
            ]);

            Log::info("✅ Notification géofencing envoyée à {$user->full_name} pour {$campus->name}");

            return [
                'success' => true,
                'message' => 'Notification envoyée',
                'notification_sent' => $notificationSent,
                'geofence_notification_id' => $geofenceNotification->id,
            ];

        } catch (\Exception $e) {
            Log::error("❌ Erreur géofencing: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer une notification FCM de géofencing
     */
    private static function sendFCMNotification(User $user, Campus $campus, GeofenceNotification $geofenceNotification): bool
    {
        try {
            $firebaseCredentials = env('FIREBASE_CREDENTIALS');

            if (!$firebaseCredentials || !file_exists($firebaseCredentials)) {
                Log::warning("Firebase credentials non configuré");
                return false;
            }

            $factory = (new Factory)->withServiceAccount($firebaseCredentials);
            $messaging = $factory->createMessaging();

            $notification = FirebaseNotification::create(
                "Vous êtes dans le {$campus->name}",
                'Cliquez ici pour faire votre check-in rapidement !'
            );

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData([
                    'type' => 'geofence_entry',
                    'campus_id' => (string) $campus->id,
                    'campus_name' => $campus->name,
                    'geofence_notification_id' => (string) $geofenceNotification->id,
                    'action' => 'quick_checkin',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $messaging->send($message);

            Log::info("✅ Notification FCM géofencing envoyée à {$user->full_name}");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur FCM géofencing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marquer une notification de géofencing comme cliquée
     */
    public static function markAsClicked(int $geofenceNotificationId): bool
    {
        try {
            $notification = GeofenceNotification::find($geofenceNotificationId);

            if (!$notification) {
                return false;
            }

            $notification->update([
                'action_taken' => 'clicked',
                'action_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("❌ Erreur marking clicked: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marquer une notification de géofencing comme ignorée
     */
    public static function markAsIgnored(int $geofenceNotificationId): bool
    {
        try {
            $notification = GeofenceNotification::find($geofenceNotificationId);

            if (!$notification) {
                return false;
            }

            $notification->update([
                'action_taken' => 'ignored',
                'action_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("❌ Erreur marking ignored: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Statistiques des notifications de géofencing
     */
    public static function getStats(string $startDate = null, string $endDate = null): array
    {
        $query = GeofenceNotification::query();

        if ($startDate) {
            $query->where('sent_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('sent_at', '<=', $endDate);
        }

        $total = $query->count();
        $clicked = $query->clone()->where('action_taken', 'clicked')->count();
        $ignored = $query->clone()->where('action_taken', 'ignored')->count();
        $pending = $query->clone()->where('action_taken', 'pending')->count();

        return [
            'total' => $total,
            'clicked' => $clicked,
            'ignored' => $ignored,
            'pending' => $pending,
            'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
        ];
    }
}
