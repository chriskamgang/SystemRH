<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campus;
use App\Models\Attendance;
use App\Models\PresenceCheck;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

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

        // Vérifier si c'est l'heure de la pause — ne pas envoyer pendant la pause
        $settings = NotificationSetting::getSettings();
        if ($settings->isInBreakPeriod($now)) {
            Log::info("⏸️ Pause déjeuner en cours - notifications de présence ignorées");
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

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
            $credentialsPath = config('firebase.credentials') ?? storage_path('firebase-credentials.json');

            if (!file_exists($credentialsPath)) {
                Log::warning("Firebase credentials non configuré: $credentialsPath");
                return false;
            }

            $creds = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $creds['project_id'] ?? null;

            if (!$projectId) {
                Log::error('Firebase project_id not found');
                return false;
            }

            // Obtenir le token OAuth2
            $sa = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $creds
            );
            $token = $sa->fetchAuthToken();
            $accessToken = $token['access_token'] ?? null;

            if (!$accessToken) {
                Log::error('Failed to get Firebase access token');
                return false;
            }

            $title = 'Vérification de présence';
            $body = 'Êtes-vous toujours présent sur le site ?';

            $payload = [
                'message' => [
                    'token' => $user->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'type' => 'presence_check',
                        'presence_check_id' => (string) $presenceCheck->id,
                        'check_time' => $presenceCheck->check_time->toIso8601String(),
                    ],
                    'apns' => [
                        'headers' => ['apns-priority' => '10'],
                        'payload' => ['aps' => ['sound' => 'default']],
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['sound' => 'default', 'channel_id' => 'presence_check_channel'],
                    ],
                ],
            ];

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200) {
                Log::info("✅ Notification FCM envoyée à {$user->full_name} (ID: {$user->id})");
                return true;
            }

            Log::error("❌ FCM API error (HTTP {$httpCode}) pour {$user->full_name}: {$response}");
            return false;

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
