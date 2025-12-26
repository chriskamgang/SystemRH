<?php

namespace App\Observers;

use App\Models\ManualAttendance;
use App\Models\UniteEnseignement;
use Carbon\Carbon;

class ManualAttendanceObserver
{
    /**
     * Handle the ManualAttendance "created" event.
     */
    public function created(ManualAttendance $manualAttendance): void
    {
        // Si c'est une session d'enseignement, ajouter les heures à l'UE
        if ($manualAttendance->isTeachingSession()) {
            $this->addHoursToUE($manualAttendance);
        }
    }

    /**
     * Handle the ManualAttendance "updating" event (avant la mise à jour).
     */
    public function updating(ManualAttendance $manualAttendance): void
    {
        // Si l'UE a changé ou les heures ont changé, on doit recalculer
        $original = $manualAttendance->getOriginal();

        // Retirer les anciennes heures de l'ancienne UE
        if (isset($original['unite_enseignement_id']) && $original['unite_enseignement_id']) {
            $this->removeHoursFromUE(
                $original['unite_enseignement_id'],
                $this->calculateHours($original['check_in_time'], $original['check_out_time'])
            );
        }
    }

    /**
     * Handle the ManualAttendance "updated" event (après la mise à jour).
     */
    public function updated(ManualAttendance $manualAttendance): void
    {
        // Ajouter les nouvelles heures à la nouvelle UE
        if ($manualAttendance->isTeachingSession()) {
            $this->addHoursToUE($manualAttendance);
        }
    }

    /**
     * Handle the ManualAttendance "deleted" event.
     */
    public function deleted(ManualAttendance $manualAttendance): void
    {
        // Retirer les heures de l'UE
        if ($manualAttendance->isTeachingSession()) {
            $this->removeHoursFromUE(
                $manualAttendance->unite_enseignement_id,
                $manualAttendance->duration_in_hours
            );
        }
    }

    /**
     * Handle the ManualAttendance "restored" event.
     */
    public function restored(ManualAttendance $manualAttendance): void
    {
        // Ré-ajouter les heures à l'UE
        if ($manualAttendance->isTeachingSession()) {
            $this->addHoursToUE($manualAttendance);
        }
    }

    /**
     * Ajouter les heures à une UE
     */
    private function addHoursToUE(ManualAttendance $manualAttendance): void
    {
        $ue = UniteEnseignement::find($manualAttendance->unite_enseignement_id);

        if ($ue) {
            $hours = $manualAttendance->duration_in_hours;
            $ue->heures_effectuees_validees = ($ue->heures_effectuees_validees ?? 0) + $hours;
            $ue->save();
        }
    }

    /**
     * Retirer les heures d'une UE
     */
    private function removeHoursFromUE(int $ueId, float $hours): void
    {
        $ue = UniteEnseignement::find($ueId);

        if ($ue) {
            $ue->heures_effectuees_validees = max(0, ($ue->heures_effectuees_validees ?? 0) - $hours);
            $ue->save();
        }
    }

    /**
     * Calculer les heures entre deux timestamps
     */
    private function calculateHours($checkIn, $checkOut): float
    {
        $checkInTime = Carbon::parse($checkIn);
        $checkOutTime = Carbon::parse($checkOut);

        return $checkOutTime->diffInMinutes($checkInTime) / 60;
    }
}
