<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========== SCHEDULED TASKS ==========

// Envoyer les notifications de présence toutes les minutes
// (le service vérifie en interne si c'est l'heure configurée)
Schedule::command('presence:send-notifications')
    ->everyMinute()
    ->name('send-presence-notifications')
    ->withoutOverlapping()
    ->onOneServer();

// Traiter les incidents expirés toutes les minutes
Schedule::command('presence:process-expired')
    ->everyMinute()
    ->name('process-expired-incidents')
    ->withoutOverlapping()
    ->onOneServer();
