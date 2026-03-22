<?php

namespace App\Console\Commands;

use App\Models\UeSchedule;
use App\Models\Setting;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCourseReminders extends Command
{
    protected $signature = 'schedule:send-course-reminders';
    protected $description = 'Envoyer des notifications de rappel aux enseignants avant leurs cours';

    public function handle()
    {
        // Vérifier si les rappels sont activés
        $enabled = Setting::get('course_reminders_enabled', '1');
        if ($enabled !== '1') {
            return;
        }

        $now = Carbon::now();
        $currentDay = UeSchedule::getCurrentDayFr();

        // Délai de rappel configurable (en minutes), par défaut 30 min
        $reminderMinutes = (int) Setting::get('course_reminder_minutes', 30);

        // Calculer l'heure cible : les cours qui commencent dans X minutes
        $targetTime = $now->copy()->addMinutes($reminderMinutes)->format('H:i');
        $currentTime = $now->format('H:i');

        Log::info("🔔 Vérification rappels de cours : jour={$currentDay}, heure={$currentTime}, cible={$targetTime} (dans {$reminderMinutes}min)");

        // Trouver tous les créneaux actifs pour aujourd'hui qui commencent dans X minutes
        $schedules = UeSchedule::with(['uniteEnseignement.enseignant', 'campus'])
            ->where('is_active', true)
            ->where('jour_semaine', $currentDay)
            ->validNow()
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("Aucun cours programmé aujourd'hui ({$currentDay}).");
            return;
        }

        $pushService = new PushNotificationService();
        $sent = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            // Vérifier si le cours commence dans la fenêtre de rappel (± 1 minute de tolérance)
            $courseStart = Carbon::parse($schedule->heure_debut);
            $diffMinutes = $now->diffInMinutes($courseStart, false); // false = peut être négatif si passé

            // Envoyer si le cours est dans [reminderMinutes - 1, reminderMinutes + 1]
            if ($diffMinutes < ($reminderMinutes - 1) || $diffMinutes > ($reminderMinutes + 1)) {
                continue;
            }

            $ue = $schedule->uniteEnseignement;
            if (!$ue || !$ue->enseignant) {
                continue;
            }

            $enseignant = $ue->enseignant;

            // Vérifier qu'il a un token FCM
            if (!$enseignant->fcm_token) {
                Log::info("⚠️ Pas de FCM token pour {$enseignant->full_name}");
                $skipped++;
                continue;
            }

            // Construire le message
            $heureDebut = substr($schedule->heure_debut, 0, 5);
            $heureFin = substr($schedule->heure_fin, 0, 5);
            $campusName = $schedule->campus->name ?? 'Campus';
            $salle = $schedule->salle ? "Salle {$schedule->salle}" : '';
            $lieu = $salle ? "{$campusName}, {$salle}" : $campusName;

            $title = "Rappel de cours dans {$reminderMinutes} min";
            $body = "{$ue->nom_matiere} ({$ue->code_ue})\n{$heureDebut} - {$heureFin} | {$lieu}";

            $data = [
                'type' => 'course_reminder',
                'ue_id' => (string) $ue->id,
                'schedule_id' => (string) $schedule->id,
                'campus_id' => (string) $schedule->campus_id,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ];

            $result = $pushService->sendToUser($enseignant, $title, $body, $data, 'course_reminder');

            if ($result) {
                $sent++;
                Log::info("✅ Rappel envoyé à {$enseignant->full_name} pour {$ue->nom_matiere} à {$heureDebut}");
                $this->info("✅ {$enseignant->full_name} → {$ue->nom_matiere} ({$heureDebut} - {$heureFin}) @ {$lieu}");
            } else {
                $skipped++;
                Log::warning("❌ Échec envoi rappel à {$enseignant->full_name}");
            }
        }

        $this->info("Terminé : {$sent} rappels envoyés, {$skipped} ignorés.");
        Log::info("🔔 Rappels de cours : {$sent} envoyés, {$skipped} ignorés");
    }
}
