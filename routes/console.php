<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========== SCHEDULED TASKS ==========

// Notifications de présence "Êtes-vous en place?"
// Vérifie chaque minute si c'est l'heure d'envoyer
Schedule::call(function () {
    $settings = \App\Models\NotificationSetting::getSettings();

    if (!$settings->is_active) {
        return; // Notifications désactivées
    }

    $currentTime = now()->format('H:i');

    // Ne pas envoyer pendant la pause déjeuner
    if ($settings->break_enabled) {
        $breakStart = substr($settings->break_start_time, 0, 5);
        $breakEnd = substr($settings->break_end_time, 0, 5);
        if ($currentTime >= $breakStart && $currentTime < $breakEnd) {
            return;
        }
    }

    // Heures configurées dans NotificationSetting
    $permanentTime = substr($settings->permanent_semi_permanent_time, 0, 5);
    $temporaryTime = substr($settings->temporary_time, 0, 5);

    // Collecter toutes les heures configurées
    $configuredTimes = array_unique([$permanentTime, $temporaryTime]);

    // Ajouter les heures multiples dans Setting (si configurées)
    for ($i = 1; $i <= 5; $i++) {
        $time = \App\Models\Setting::get("notification_time_{$i}");
        if ($time && !in_array($time, $configuredTimes)) {
            $configuredTimes[] = $time;
        }
    }

    if (in_array($currentTime, $configuredTimes)) {
        \Illuminate\Support\Facades\Log::info("🔔 Déclenchement notification de présence à {$currentTime}");
        \App\Services\PresenceNotificationService::sendPresenceCheckNotifications();
    }
})->everyMinute()
  ->name('presence-check-dynamic')
  ->withoutOverlapping();

// Auto-checkout des vacataires dont le créneau UE est terminé
Schedule::command('schedule:auto-checkout')
    ->everyMinute()
    ->name('auto-checkout-ue')
    ->withoutOverlapping();

// Auto-checkout fin de journée : clôturer les check-ins sans check-out → demi-journée
Schedule::command('attendance:auto-checkout')
    ->dailyAt('06:00')
    ->name('auto-checkout-end-of-day')
    ->withoutOverlapping();

// Rappels de cours : notifie les enseignants X minutes avant leur cours
Schedule::command('schedule:send-course-reminders')
    ->everyMinute()
    ->name('course-reminders')
    ->withoutOverlapping();
