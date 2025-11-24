<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campus;
use App\Models\Attendance;
use App\Models\PresenceIncident;
use App\Models\NotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PresenceNotificationService
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Envoyer les notifications de présence selon l'heure configurée
     * Cette méthode est appelée par le Cron Job
     */
    public function sendPresenceCheckNotifications()
    {
        $settings = NotificationSetting::getSettings();

        if (!$settings->is_active) {
            Log::info('Presence notification system is disabled');
            return;
        }

        $currentTime = Carbon::now()->format('H:i:s');

        // Vérifier si c'est l'heure d'envoi pour les permanents/semi-permanents
        if ($currentTime >= $settings->permanent_semi_permanent_time &&
            $currentTime < Carbon::parse($settings->permanent_semi_permanent_time)->addMinute()->format('H:i:s')) {
            $this->sendForEmployeeTypes(['enseignant_titulaire', 'administratif', 'technique', 'direction']);
        }

        // Vérifier si c'est l'heure d'envoi pour les temporaires
        if ($currentTime >= $settings->temporary_time &&
            $currentTime < Carbon::parse($settings->temporary_time)->addMinute()->format('H:i:s')) {
            $this->sendForEmployeeTypes(['enseignant_vacataire']);
        }
    }

    /**
     * Envoyer les notifications pour des types d'employés spécifiques
     */
    protected function sendForEmployeeTypes(array $employeeTypes)
    {
        $settings = NotificationSetting::getSettings();

        // Récupérer les utilisateurs qui ont check-in aujourd'hui
        $usersWithCheckin = $this->getUsersWithTodayCheckin($employeeTypes);

        $sentCount = 0;
        foreach ($usersWithCheckin as $userData) {
            $user = $userData['user'];
            $campus = $userData['campus'];
            $attendance = $userData['attendance'];

            // Vérifier si l'utilisateur est dans la zone du campus
            $isInZone = $this->checkUserInCampusZone($user, $campus);

            if (!$isInZone) {
                Log::info("User {$user->id} is not in campus {$campus->id} zone, skipping notification");
                continue;
            }

            // Vérifier s'il n'y a pas déjà un incident en attente aujourd'hui
            $existingIncident = PresenceIncident::where('user_id', $user->id)
                ->whereDate('incident_date', today())
                ->where('status', 'pending')
                ->first();

            if ($existingIncident) {
                Log::info("User {$user->id} already has a pending incident today");
                continue;
            }

            // Créer l'incident de présence
            $incident = $this->createPresenceIncident($user, $campus, $attendance, $settings);

            // Envoyer la notification push
            $sent = $this->pushService->sendPresenceCheckNotification($user, $incident->id, $campus);

            if ($sent) {
                $sentCount++;
                Log::info("Presence check notification sent to user {$user->id} at campus {$campus->id}");
            }
        }

        Log::info("Sent {$sentCount} presence check notifications");
        return $sentCount;
    }

    /**
     * Récupérer les utilisateurs qui ont check-in aujourd'hui
     */
    protected function getUsersWithTodayCheckin(array $employeeTypes)
    {
        $users = [];

        // Récupérer tous les check-in d'aujourd'hui qui n'ont pas de check-out
        $attendances = Attendance::with(['user', 'campus'])
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->whereHas('user', function($query) use ($employeeTypes) {
                $query->whereIn('employee_type', $employeeTypes)
                      ->where('is_active', true)
                      ->whereNotNull('fcm_token');
            })
            ->get();

        foreach ($attendances as $attendance) {
            // Vérifier si l'utilisateur n'a pas déjà fait check-out
            $hasCheckOut = Attendance::where('user_id', $attendance->user_id)
                ->where('campus_id', $attendance->campus_id)
                ->where('type', 'check-out')
                ->whereDate('timestamp', today())
                ->where('timestamp', '>', $attendance->timestamp)
                ->exists();

            if (!$hasCheckOut) {
                $users[] = [
                    'user' => $attendance->user,
                    'campus' => $attendance->campus,
                    'attendance' => $attendance,
                ];
            }
        }

        return $users;
    }

    /**
     * Vérifier si un utilisateur est dans la zone d'un campus
     * Note: Cette méthode nécessiterait la localisation en temps réel
     * Pour l'instant, on simule avec la dernière position connue
     */
    protected function checkUserInCampusZone(User $user, Campus $campus)
    {
        // TODO: Implémenter la vérification avec la position en temps réel
        // Pour l'instant, on suppose que l'utilisateur est dans la zone s'il a check-in

        // On pourrait ajouter une table user_locations avec la dernière position
        // et vérifier avec la méthode isUserInZone() du Campus

        return true; // Temporaire
    }

    /**
     * Créer un incident de présence
     */
    protected function createPresenceIncident(User $user, Campus $campus, Attendance $attendance, NotificationSetting $settings)
    {
        $notificationTime = Carbon::now();
        $responseDeadline = $notificationTime->copy()->addMinutes($settings->response_delay_minutes);

        return PresenceIncident::create([
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'attendance_id' => $attendance->id,
            'incident_date' => today(),
            'notification_sent_at' => $notificationTime->format('H:i:s'),
            'response_deadline' => $responseDeadline->format('H:i:s'),
            'penalty_hours' => $settings->penalty_hours,
            'status' => 'pending',
        ]);
    }

    /**
     * Créer automatiquement les incidents pour les non-réponses
     * Appelé par le Cron Job toutes les minutes
     */
    public function createIncidentsForNonResponses()
    {
        $settings = NotificationSetting::getSettings();

        if (!$settings->is_active) {
            return;
        }

        $currentTime = Carbon::now();

        // Récupérer les incidents en attente dont le délai est dépassé
        $expiredIncidents = PresenceIncident::where('status', 'pending')
            ->where('has_responded', false)
            ->whereDate('incident_date', today())
            ->where('response_deadline', '<', $currentTime->format('H:i:s'))
            ->get();

        $count = 0;
        foreach ($expiredIncidents as $incident) {
            // L'incident reste en status 'pending' pour que l'admin le valide
            // On ne fait que logger
            Log::info("Incident {$incident->id} expired - User {$incident->user_id} did not respond");
            $count++;
        }

        if ($count > 0) {
            Log::info("Found {$count} expired presence incidents waiting for admin validation");
        }

        return $count;
    }

    /**
     * Envoyer une notification "Vous pouvez scanner"
     */
    public function sendScanAvailableNotification(User $user, Campus $campus)
    {
        // Vérifier si l'utilisateur n'a pas déjà check-in dans ce campus aujourd'hui
        $hasCheckedIn = Attendance::where('user_id', $user->id)
            ->where('campus_id', $campus->id)
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->exists();

        if ($hasCheckedIn) {
            return false; // Déjà check-in
        }

        return $this->pushService->sendScanAvailableNotification($user, $campus);
    }

    /**
     * Répondre à une notification de présence (appelé depuis l'API mobile)
     */
    public function respondToPresenceCheck($incidentId, User $user, $latitude, $longitude)
    {
        $incident = PresenceIncident::findOrFail($incidentId);

        // Vérifier que c'est bien l'utilisateur concerné
        if ($incident->user_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        // Vérifier si le délai n'est pas expiré
        $deadline = Carbon::parse($incident->incident_date->format('Y-m-d') . ' ' . $incident->response_deadline);
        if (Carbon::now()->gt($deadline)) {
            throw new \Exception('Response deadline expired');
        }

        // Vérifier si l'utilisateur est dans la zone
        $campus = $incident->campus;
        $wasInZone = $campus->isUserInZone($latitude, $longitude);

        // Marquer comme répondu
        $incident->markAsResponded($latitude, $longitude, $wasInZone);

        Log::info("User {$user->id} responded to presence incident {$incidentId}");

        return [
            'success' => true,
            'was_in_zone' => $wasInZone,
            'message' => 'Présence confirmée avec succès',
        ];
    }
}
