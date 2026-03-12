<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========== SCHEDULED TASKS ==========

// Notifications de présence à heures configurables
// L'admin peut modifier les heures depuis le dashboard

// Vérifier toutes les minutes si c'est l'heure d'envoyer une notification
Schedule::call(function () {
    $enabled = \App\Models\Setting::get('notification_enabled', '1');

    if ($enabled != '1') {
        return; // Notifications désactivées
    }

    $currentTime = now()->format('H:i');

    // Récupérer les 5 heures configurées
    for ($i = 1; $i <= 5; $i++) {
        $configuredTime = \App\Models\Setting::get("notification_time_{$i}");

        if ($configuredTime && $configuredTime === $currentTime) {
            \Illuminate\Support\Facades\Log::info("🔔 Déclenchement notification #{$i} à {$currentTime}");
            \App\Services\PresenceNotificationService::sendPresenceCheckNotifications();
            break; // Une seule notification par minute
        }
    }
})->everyMinute()
  ->name('presence-check-dynamic')
  ->withoutOverlapping();

// Auto-checkout des vacataires dont le créneau UE est terminé
Schedule::command('schedule:auto-checkout')
    ->everyMinute()
    ->name('auto-checkout-ue')
    ->withoutOverlapping();
