<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'permanent_semi_permanent_time',
        'temporary_time',
        'break_start_time',
        'break_end_time',
        'break_enabled',
        'response_delay_minutes',
        'penalty_hours',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'break_enabled' => 'boolean',
        'penalty_hours' => 'decimal:2',
        'response_delay_minutes' => 'integer',
    ];

    /**
     * Récupérer ou créer les paramètres (singleton pattern)
     */
    public static function getSettings()
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'permanent_semi_permanent_time' => '13:00:00',
                'temporary_time' => '14:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'break_enabled' => true,
                'response_delay_minutes' => 45,
                'penalty_hours' => 1.00,
                'is_active' => true,
            ]);
        }

        return $settings;
    }

    /**
     * Obtenir l'heure d'envoi pour un type d'employé
     */
    public function getNotificationTimeForEmployeeType($employeeType)
    {
        if ($employeeType === 'enseignant_vacataire') {
            return $this->temporary_time;
        }

        return $this->permanent_semi_permanent_time;
    }

    /**
     * Vérifier si l'heure donnée est pendant la pause
     */
    public function isInBreakPeriod(?Carbon $time = null): bool
    {
        if (!$this->break_enabled) {
            return false;
        }

        $time = $time ?? Carbon::now();
        $checkTime = Carbon::parse($time->format('H:i:s'));
        $breakStart = Carbon::parse($this->break_start_time);
        $breakEnd = Carbon::parse($this->break_end_time);

        return $checkTime->between($breakStart, $breakEnd);
    }

    /**
     * Calculer le chevauchement en minutes entre une session et la pause
     * Retourne 0 pour les vacataires (pas de pause déductible)
     */
    public static function calculateBreakOverlapMinutes(
        Carbon $sessionStart,
        Carbon $sessionEnd,
        ?string $employeeType = null
    ): int {
        // Les vacataires n'ont pas de pause déductible
        if ($employeeType === 'enseignant_vacataire') {
            return 0;
        }

        $settings = self::getSettings();

        if (!$settings->break_enabled) {
            return 0;
        }

        // Construire les heures de pause pour le même jour que la session
        $breakStart = $sessionStart->copy()->setTimeFromTimeString($settings->break_start_time);
        $breakEnd = $sessionStart->copy()->setTimeFromTimeString($settings->break_end_time);

        // Calculer le chevauchement
        $overlapStart = $sessionStart->max($breakStart);
        $overlapEnd = $sessionEnd->min($breakEnd);

        if ($overlapStart >= $overlapEnd) {
            return 0;
        }

        return (int) $overlapStart->diffInMinutes($overlapEnd);
    }
}
