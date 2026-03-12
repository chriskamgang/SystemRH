<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\UeSchedule;
use App\Models\Tardiness;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCheckoutUeSchedule extends Command
{
    protected $signature = 'schedule:auto-checkout';
    protected $description = 'Checkout automatique des vacataires dont le créneau UE est terminé';

    public function handle()
    {
        $jourActuel = UeSchedule::getCurrentDayFr();
        $now = Carbon::now();
        $currentTime = Carbon::parse($now->format('H:i:s'));

        // Trouver les check-ins actifs (sans check-out) d'aujourd'hui qui ont une UE
        $activeCheckIns = Attendance::where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->whereNotNull('unite_enseignement_id')
            ->where('status', 'valid')
            ->get();

        $autoCheckouts = 0;

        foreach ($activeCheckIns as $checkIn) {
            // Vérifier s'il y a déjà un check-out
            $hasCheckOut = Attendance::where('user_id', $checkIn->user_id)
                ->where('campus_id', $checkIn->campus_id)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', today())
                ->exists();

            if ($hasCheckOut) {
                continue;
            }

            // Trouver le créneau de cette UE pour aujourd'hui
            $schedule = UeSchedule::where('unite_enseignement_id', $checkIn->unite_enseignement_id)
                ->where('campus_id', $checkIn->campus_id)
                ->where('jour_semaine', $jourActuel)
                ->where('is_active', true)
                ->get()
                ->first(function ($s) use ($checkIn) {
                    // Trouver le créneau dont l'heure de début correspond au check-in
                    $checkInTime = Carbon::parse($checkIn->timestamp->format('H:i:s'));
                    $debut = Carbon::parse($s->heure_debut)->subMinutes(15);
                    $fin = Carbon::parse($s->heure_fin);
                    return $checkInTime->between($debut, $fin);
                });

            if (!$schedule) {
                continue;
            }

            // Vérifier si l'heure de fin du créneau est passée
            $heureFin = Carbon::parse($schedule->heure_fin);
            if ($currentTime->lt($heureFin)) {
                continue; // Le créneau n'est pas encore terminé
            }

            // Créer le check-out automatique
            $checkoutTime = Carbon::today()->setTimeFrom(Carbon::parse($schedule->heure_fin));

            Attendance::create([
                'user_id' => $checkIn->user_id,
                'campus_id' => $checkIn->campus_id,
                'type' => 'check-out',
                'shift' => $checkIn->shift,
                'timestamp' => $checkoutTime,
                'latitude' => $checkIn->latitude,
                'longitude' => $checkIn->longitude,
                'accuracy' => $checkIn->accuracy,
                'is_late' => false,
                'late_minutes' => 0,
                'device_info' => ['auto_checkout' => true, 'reason' => 'schedule_end'],
                'status' => 'valid',
                'unite_enseignement_id' => $checkIn->unite_enseignement_id,
            ]);

            $autoCheckouts++;

            Log::info("Auto-checkout UE: user={$checkIn->user_id}, ue={$checkIn->unite_enseignement_id}, heure_fin={$schedule->heure_fin}");
        }

        $this->info("Auto-checkout terminé : {$autoCheckouts} check-out(s) créé(s).");

        return Command::SUCCESS;
    }
}
