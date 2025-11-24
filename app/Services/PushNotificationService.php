<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class PushNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            // Charger les credentials depuis le fichier JSON
            $credentialsPath = storage_path('firebase-credentials.json');

            if (!file_exists($credentialsPath)) {
                Log::error('Firebase credentials file not found at: ' . $credentialsPath);
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();

            Log::info('✓ Firebase Admin SDK initialized with API V1');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase Admin SDK: ' . $e->getMessage());
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
        $body = "Vous pouvez déjà scanner au campus {$campus->name}";

        $data = [
            'type' => 'scan_available',
            'campus_id' => (string)$campus->id,
            'campus_name' => $campus->name,
            'action_url' => 'check_in_screen',
        ];

        return $this->sendNotification($user->fcm_token, $title, $body, $data, $user, 'check_in_reminder');
    }

    /**
     * Méthode principale pour envoyer une notification via FCM API V1
     */
    protected function sendNotification($fcmToken, string $title, string $body, array $data, User $user, string $type, bool $withAction = false)
    {
        if (!$this->messaging) {
            Log::error('Firebase Messaging not initialized');
            return false;
        }

        try {
            // Créer la notification Firebase
            $notification = FirebaseNotification::create($title, $body);

            // Configuration Android avec priorité haute
            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => $withAction ? 'presence_check_channel' : 'attendance_channel',
                    'priority' => 'max',
                ],
            ]);

            // Configuration iOS avec priorité haute
            $apnsConfig = ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                    ],
                ],
            ]);

            // Créer le message
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig);

            // Envoyer le message
            $this->messaging->send($message);

            // Sauvegarder dans la base de données
            $this->saveNotificationToDatabase($user, $title, $body, $type, $data, true);

            Log::info("✓ Push notification sent successfully to user {$user->id} via API V1");
            return true;

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            Log::error("FCM token not found for user {$user->id}: " . $e->getMessage());
            $this->saveNotificationToDatabase($user, $title, $body, $type, $data, false);
            return false;

        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error("Firebase messaging error for user {$user->id}: " . $e->getMessage());
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
        return $this->messaging !== null;
    }
}
