<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCheckoutEndOfDay extends Command
{
    protected $signature = 'attendance:auto-checkout';
    protected $description = 'Clôturer automatiquement les check-ins sans check-out et les marquer comme demi-journée';

    public function handle()
    {
        $this->info('Recherche des check-ins ouverts...');

        // Trouver tous les check-ins sans check-out (aujourd'hui et jours précédents)
        $openCheckIns = Attendance::where('type', 'check-in')
            ->where('status', 'valid')
            ->whereDate('timestamp', '<', today()) // Seulement les jours passés
            ->get()
            ->filter(function ($checkIn) {
                return !Attendance::where('user_id', $checkIn->user_id)
                    ->where('campus_id', $checkIn->campus_id)
                    ->where('type', 'check-out')
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->whereDate('timestamp', $checkIn->timestamp->toDateString())
                    ->exists();
            });

        if ($openCheckIns->isEmpty()) {
            $this->info('Aucun check-in ouvert trouvé.');
            return 0;
        }

        $count = 0;
        foreach ($openCheckIns as $checkIn) {
            // Déterminer l'heure de fin de la plage
            $endTime = $checkIn->shift === 'evening' ? '21:00:00' : '18:00:00';
            $checkoutTimestamp = Carbon::parse($checkIn->timestamp->toDateString() . ' ' . $endTime);

            // Créer un auto-checkout
            Attendance::create([
                'user_id' => $checkIn->user_id,
                'campus_id' => $checkIn->campus_id,
                'unite_enseignement_id' => $checkIn->unite_enseignement_id,
                'type' => 'check-out',
                'shift' => $checkIn->shift,
                'timestamp' => $checkoutTimestamp,
                'latitude' => $checkIn->latitude,
                'longitude' => $checkIn->longitude,
                'accuracy' => $checkIn->accuracy,
                'is_half_day' => true,
                'device_info' => ['auto_checkout' => true, 'reason' => 'Pas de check-out - demi-journée'],
                'notes' => 'Auto-checkout: pas de check-out effectué, demi-journée comptabilisée',
                'status' => 'valid',
            ]);

            // Marquer le check-in aussi comme demi-journée
            $checkIn->update(['is_half_day' => true]);

            $count++;
            $this->line("  → Auto-checkout pour {$checkIn->user->last_name} {$checkIn->user->first_name} du {$checkIn->timestamp->format('d/m/Y')}");
        }

        $this->info("✓ {$count} check-in(s) clôturé(s) en demi-journée.");
        return 0;
    }
}
