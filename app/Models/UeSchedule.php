<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UeSchedule extends Model
{
    protected $table = 'ue_schedules';

    protected $fillable = [
        'unite_enseignement_id',
        'campus_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'salle',
        'date_debut_validite',
        'date_fin_validite',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_debut_validite' => 'date',
        'date_fin_validite' => 'date',
    ];

    // Relations
    public function uniteEnseignement(): BelongsTo
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, string $jour)
    {
        return $query->where('jour_semaine', $jour);
    }

    public function scopeForUe($query, int $ueId)
    {
        return $query->where('unite_enseignement_id', $ueId);
    }

    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeValidNow($query)
    {
        $today = now()->toDateString();
        return $query->where(function ($q) use ($today) {
            $q->whereNull('date_debut_validite')
              ->orWhere('date_debut_validite', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('date_fin_validite')
              ->orWhere('date_fin_validite', '>=', $today);
        });
    }

    // Helpers
    public static function getCurrentDayFr(): string
    {
        $days = [
            'Monday' => 'lundi',
            'Tuesday' => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday' => 'jeudi',
            'Friday' => 'vendredi',
            'Saturday' => 'samedi',
            'Sunday' => 'dimanche',
        ];

        return $days[now()->format('l')];
    }

    public function isCurrentlyActive(int $toleranceMinutes = 15): bool
    {
        $now = Carbon::now();
        $today = self::getCurrentDayFr();

        if ($this->jour_semaine !== $today) {
            return false;
        }

        // Vérifier la période de validité
        if ($this->date_debut_validite && $now->lt($this->date_debut_validite)) {
            return false;
        }
        if ($this->date_fin_validite && $now->gt($this->date_fin_validite->endOfDay())) {
            return false;
        }

        $debut = Carbon::parse($this->heure_debut)->subMinutes($toleranceMinutes);
        $fin = Carbon::parse($this->heure_fin)->addMinutes($toleranceMinutes);
        $current = Carbon::parse($now->format('H:i:s'));

        return $current->between($debut, $fin);
    }

    public function getDureeEnHeuresAttribute(): float
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        return $debut->diffInMinutes($fin) / 60;
    }

    public function getFormattedCreneauAttribute(): string
    {
        return substr($this->heure_debut, 0, 5) . ' - ' . substr($this->heure_fin, 0, 5);
    }
}
