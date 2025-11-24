<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
        'late_tolerance' => 'integer',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

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
    public function isUserInZone($latitude, $longitude)
    {
        // Calcul de la distance en mètres entre deux points GPS
        $earthRadius = 6371000; // en mètres

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        return $distance <= $this->radius;
    }
}