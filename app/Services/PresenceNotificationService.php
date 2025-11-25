<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campus;
use App\Models\Attendance;
use App\Models\PresenceCheck;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Factory;

class PresenceNotificationService
{

    /**
     * Envoyer les notifications de présence à tous ceux qui ont check-in
     * Appelé par le Cron Job selon les heures configurées dans les settings
     */
    public static function sendPresenceCheckNotifications(): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        Log::info("📱 Envoi des notifications de présence - " . $now->format('H:i'));

        // Récupérer tous les employés qui ont un check-in actif aujourd'hui
        $activeCheckIns = self::getActiveCheckIns($today);

        if (empty($activeCheckIns)) {
            Log::info("Aucun employé actif trouvé pour les notifications");
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        $sent = 0;
        $failed = 0;
        $results = [];

        foreach ($activeCheckIns as $checkIn) {
            try {
                $user = $checkIn->user;

                // Créer l'enregistrement de vérification de présence
                $presenceCheck = PresenceCheck::create([
                    'user_id' => $user->id,
                    'campus_id' => $checkIn->campus_id,
                    'check_time' => $now,
                    'response' => 'no_response',
                    'notification_sent' => false,
                ]);

                // Envoyer la notification FCM si l'utilisateur a un token
                $notificationSent = false;
                if ($user->fcm_token) {
                    $notificationSent = self::sendFCMNotification($user, $presenceCheck);

                    if ($notificationSent) {
                        $presenceCheck->update(['notification_sent' => true]);
                        $sent++;
                    } else {
                        $failed++;
                    }
                } else {
                    Log::warning("⚠️ User {$user->id} ({$user->full_name}) n'a pas de FCM token");
                    $failed++;
                }

                // Créer une notification dans la DB
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'presence_check',
                    'title' => 'Vérification de présence',
                    'body' => 'Êtes-vous toujours présent sur le site ?',
                    'is_read' => false,
                    'sent_at' => $now,
                    'delivery_status' => $notificationSent ? 'sent' : 'failed',
                    'data' => json_encode([
                        'presence_check_id' => $presenceCheck->id,
                        'check_time' => $now->toIso8601String(),
                    ]),
                ]);

                $results[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'campus' => $checkIn->campus->name,
                    'presence_check_id' => $presenceCheck->id,
                    'status' => $notificationSent ? 'sent' : 'failed',
                ];

            } catch (\Exception $e) {
                Log::error("❌ Erreur notification pour user {$user->id}: " . $e->getMessage());
                $failed++;
            }
        }

        Log::info("✅ Notifications: {$sent} envoyées, {$failed} échecs sur " . count($activeCheckIns) . " employés");

        return [
            'total' => count($activeCheckIns),
            'sent' => $sent,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Récupérer tous les check-ins actifs (sans check-out) d'aujourd'hui
     */
    private static function getActiveCheckIns(string $date): array
    {
        $checkIns = Attendance::where('type', 'check-in')
            ->whereDate('timestamp', $date)
            ->with(['user', 'campus'])
            ->get();

        $activeCheckIns = [];

        foreach ($checkIns as $checkIn) {
            // Vérifier si l'utilisateur est actif
            if (!$checkIn->user || !$checkIn->user->is_active) {
                continue;
            }

            // Vérifier s'il existe un check-out correspondant
            $hasCheckOut = Attendance::where('user_id', $checkIn->user_id)
                ->where('campus_id', $checkIn->campus_id)
                ->where('type', 'check-out')
                ->where('shift', $checkIn->shift)
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', $date)
                ->exists();

            // Si pas de check-out, l'employé est toujours actif
            if (!$hasCheckOut) {
                $activeCheckIns[] = $checkIn;
            }
        }

        return $activeCheckIns;
    }

    /**
     * Envoyer une notification FCM à un utilisateur
     */
    private static function sendFCMNotification(User $user, PresenceCheck $presenceCheck): bool
    {
        try {
            // Vérifier si Firebase est configuré
            $firebaseCredentials = env('FIREBASE_CREDENTIALS');

            if (!$firebaseCredentials || !file_exists($firebaseCredentials)) {
                Log::warning("Firebase credentials non configuré");
                return false;
            }

            $factory = (new Factory)->withServiceAccount($firebaseCredentials);
            $messaging = $factory->createMessaging();

            $notification = FirebaseNotification::create(
                'Vérification de présence',
                'Êtes-vous toujours présent sur le site ?'
            );

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData([
                    'type' => 'presence_check',
                    'presence_check_id' => (string) $presenceCheck->id,
                    'check_time' => $presenceCheck->check_time->toIso8601String(),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            $messaging->send($message);

            Log::info("✅ Notification FCM envoyée à {$user->full_name} (ID: {$user->id})");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur FCM pour {$user->full_name} (ID: {$user->id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les statistiques des notifications du jour
     */
    public static function getTodayStats(): array
    {
        $today = Carbon::today();

        $total = PresenceCheck::whereDate('check_time', $today)->count();
        $sent = PresenceCheck::whereDate('check_time', $today)
            ->where('notification_sent', true)
            ->count();
        $responded = PresenceCheck::whereDate('check_time', $today)
            ->where('response', '!=', 'no_response')
            ->count();
        $noResponse = PresenceCheck::whereDate('check_time', $today)
            ->where('response', 'no_response')
            ->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'responded' => $responded,
            'no_response' => $noResponse,
            'response_rate' => $total > 0 ? round(($responded / $total) * 100, 2) : 0,
        ];
    }
}
