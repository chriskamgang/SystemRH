<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ManualAttendance extends Model
{
    protected $fillable = [
        'date',
        'check_in_time',
        'check_out_time',
        'user_id',
        'unite_enseignement_id',
        'campus_id',
        'session_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function uniteEnseignement(): BelongsTo
    {
        return $this->belongsTo(UniteEnseignement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accesseurs et méthodes helper
     */

    // Calculer la durée en heures (décimal)
    public function getDurationInHoursAttribute(): float
    {
        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        return abs($checkOut->diffInMinutes($checkIn)) / 60;
    }

    // Formater la durée (ex: "2h 30min")
    public function getFormattedDurationAttribute(): string
    {
        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        $totalMinutes = abs($checkOut->diffInMinutes($checkIn));
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($minutes > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$hours}h";
    }

    // Vérifier si c'est une session d'enseignement
    public function isTeachingSession(): bool
    {
        return !is_null($this->unite_enseignement_id);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
    }

    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeForUniteEnseignement($query, $ueId)
    {
        return $query->where('unite_enseignement_id', $ueId);
    }

    public function scopeSessionType($query, $type)
    {
        return $query->where('session_type', $type);
    }
}
