<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'permanent_semi_permanent_time',
        'temporary_time',
        'response_delay_minutes',
        'penalty_hours',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
