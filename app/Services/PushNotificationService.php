<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

class PushNotificationService
{
    protected $credentialsPath;
    protected $projectId;
    protected $initialized = false;

    public function __construct()
    {
        try {
            $this->credentialsPath = storage_path('firebase-credentials.json');

            if (!file_exists($this->credentialsPath)) {
                Log::error('Firebase credentials file not found at: ' . $this->credentialsPath);
                return;
            }

            $creds = json_decode(file_get_contents($this->credentialsPath), true);
            $this->projectId = $creds['project_id'] ?? null;

            if (!$this->projectId) {
                Log::error('Firebase project_id not found in credentials');
                return;
            }

            $this->initialized = true;
            Log::info('✓ Firebase Push Service initialized (FCM API V1 direct)');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase Push Service: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir un access token OAuth2
     */
    protected function getAccessToken(): ?string
    {
        try {
            $creds = json_decode(file_get_contents($this->credentialsPath), true);
            $sa = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $creds
            );
            $token = $sa->fetchAuthToken();
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get Firebase access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envoyer une notification push à un utilisateur
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'system')
    {
        if (!$user->fcm_token) {
            Log::warning("User {$user->id} has no FCM token");
            return false;
        }

        return $this->sendNotification($user->fcm_token, $title, $body, $data, $user, $type);
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendToMultipleUsers($users, string $title, string $body, array $data = [], string $type = 'system')
    {
        $successCount = 0;
        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data, $type)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Envoyer une notification de présence avec bouton d'action
     */
    public function sendPresenceCheckNotification(User $user, $incidentId, $campus)
    {
        $title = "Confirmation de présence";
        $body = "Êtes-vous toujours en place au {$campus->name} ?";

        $data = [
            'type' => 'presence_check',
            'incident_id' => (string)$incidentId,
            'campus_id' => (string)$campus->id,
            'campus_name' => $campus->name,
            'action_required' => 'true',
        ];

        return $this->sendNotification($user->fcm_token, $title, $body, $data, $user, 'presence_check', true);
    }

    /**
     * Envoyer une notification "Vous pouvez scanner"
     */
    public function sendScanAvailableNotification(User $user, $campus)
    {
        $title = "Pointage disponible";
        $body = "Vous êtes à {$campus->name}. Vous pouvez faire votre check-in maintenant !";

        $data = [
            'type' => 'scan_available',
            'campus_id' => (string)$campus->id,
            'campus_name' => $campus->name,
            'action_url' => 'check_in_screen',
        ];

        return $this->sendNotification($user->fcm_token, $title, $body, $data, $user, 'check_in_reminder');
    }

    /**
     * Méthode principale pour envoyer une notification via FCM API V1 (appel direct)
     */
    protected function sendNotification($fcmToken, string $title, string $body, array $data, User $user, string $type, bool $withAction = false)
    {
        if (!$this->initialized) {
            Log::error('Firebase Push Service not initialized');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('Failed to obtain Firebase access token');
                $this->saveNotificationToDatabase($user, $title, $body, $type, $data, false);
                return false;
            }

            // Construire le payload FCM v1
            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => $withAction ? 'presence_check_channel' : 'attendance_channel',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

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
                $this->saveNotificationToDatabase($user, $title, $body, $type, $data, true);
                Log::info("✓ Push notification sent to user {$user->id} via FCM API V1");
                return true;
            }

            Log::error("FCM API error (HTTP {$httpCode}) for user {$user->id}: {$response}");
            $this->saveNotificationToDatabase($user, $title, $body, $type, $data, false);
            return false;

        } catch (\Exception $e) {
            Log::error("Exception while sending push notification: " . $e->getMessage());
            $this->saveNotificationToDatabase($user, $title, $body, $type, $data, false);
            return false;
        }
    }

    /**
     * Sauvegarder la notification dans la base de données
     */
    protected function saveNotificationToDatabase(User $user, string $title, string $body, string $type, array $data, bool $success)
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'delivery_status' => $success ? 'delivered' : 'failed',
            'sent_at' => now(),
        ]);
    }

    /**
     * Tester l'envoi d'une notification
     */
    public function sendTestNotification(User $user)
    {
        return $this->sendToUser(
            $user,
            'Test de notification',
            'Ceci est une notification de test du système de pointage.',
            ['test' => 'true'],
            'system'
        );
    }

    /**
     * Vérifier si Firebase est correctement configuré
     */
    public function isConfigured(): bool
    {
        return $this->initialized;
    }
}
