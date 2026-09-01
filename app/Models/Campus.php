<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'description',
        'latitude',
        'longitude',
        'radius',
        'start_time',
        'end_time',
        'late_tolerance',
        'working_days',
        'is_active',
        'attendance_mode',
        'night_start_time',
        'night_late_tolerance',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
        'late_tolerance' => 'integer',
        'working_days' => 'array',
        'is_active' => 'boolean',
        'night_late_tolerance' => 'integer',
    ];

    public function isHospitalMode(): bool
    {
        return $this->attendance_mode === 'hospital';
    }

    /**
     * Relations
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_campus')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function salles()
    {
        return $this->hasMany(Salle::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function presenceChecks()
    {
        return $this->hasMany(PresenceCheck::class);
    }

    public function tardiness()
    {
        return $this->hasMany(Tardiness::class);
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * Helper methods
     */
    /**
     * Calculer la distance entre l'utilisateur et le campus en mètres
     */
    public function distanceToUser($latitude, $longitude): float
    {
        $earthRadius = 6371000; // en mètres

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Vérifier si l'utilisateur est dans la zone du campus
     * Tolérance dynamique basée sur la précision GPS du téléphone
     * - Minimum 50m de marge (téléphones avec bon GPS)
     * - Jusqu'à la précision GPS reportée (téléphones bas de gamme)
     */
    public function isUserInZone($latitude, $longitude, $accuracy = null): bool
    {
        $distance = $this->distanceToUser($latitude, $longitude);

        // Tolérance dynamique basée sur le chevauchement GPS
        // Logique : si le cercle d'incertitude GPS chevauche la zone du campus,
        // l'employé PEUT être dans la zone → on l'autorise.
        // Sécurité : si distance - accuracy > radius, impossible d'être dans la zone.
        $baseTolerance = 50;

        if ($accuracy && $accuracy > 0) {
            // GPS très précis (< 100m) : tolérance fixe de 50m
            if ($accuracy <= 100) {
                $tolerance = $baseTolerance;
            }
            // GPS moyen (100-500m) : utiliser la précision complète
            elseif ($accuracy <= 500) {
                $tolerance = $accuracy;
            }
            // GPS faible (500-3000m) : chevauchement avec facteur de sécurité
            // On utilise 70% de la précision pour éviter les faux positifs à grande distance
            elseif ($accuracy <= 3000) {
                $tolerance = $accuracy * 0.7;
            }
            // GPS inutilisable (> 3000m) : cap à 2000m
            else {
                $tolerance = 2000;
            }
        } else {
            $tolerance = $baseTolerance;
        }

        return $distance <= ($this->radius + $tolerance);
    }
}