<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteEnseignement extends Model
{
    protected $table = 'unites_enseignement';

    protected $fillable = [
        'vacataire_id',
        'code_ue',
        'nom_matiere',
        'volume_horaire_total',
        'statut',
        'annee_academique',
        'semestre',
        'date_attribution',
        'date_activation',
        'created_by',
        'activated_by',
    ];

    protected $casts = [
        'volume_horaire_total' => 'decimal:2',
        'date_attribution' => 'datetime',
        'date_activation' => 'datetime',
        'semestre' => 'integer',
    ];

    /**
     * Relations
     */

    // Vacataire (enseignant) à qui l'UE est attribuée
    public function vacataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vacataire_id');
    }

    // Admin qui a créé/attribué l'UE
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Admin qui a activé l'UE
    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    // Incidents de présence liés à cette UE
    public function presenceIncidents(): HasMany
    {
        return $this->hasMany(PresenceIncident::class, 'unite_enseignement_id');
    }

    /**
     * Scopes
     */

    // Filtre les UE activées
    public function scopeActivee($query)
    {
        return $query->where('statut', 'activee');
    }

    // Filtre les UE non activées
    public function scopeNonActivee($query)
    {
        return $query->where('statut', 'non_activee');
    }

    // Filtre par vacataire
    public function scopeForVacataire($query, $vacataireId)
    {
        return $query->where('vacataire_id', $vacataireId);
    }

    // Filtre par année académique
    public function scopeAnneeAcademique($query, $annee)
    {
        return $query->where('annee_academique', $annee);
    }

    /**
     * Méthodes helper
     */

    // Vérifie si l'UE est activée
    public function isActivee(): bool
    {
        return $this->statut === 'activee';
    }

    // Vérifie si l'UE est non activée
    public function isNonActivee(): bool
    {
        return $this->statut === 'non_activee';
    }

    // Activer l'UE
    public function activer($adminId = null): void
    {
        $this->update([
            'statut' => 'activee',
            'date_activation' => now(),
            'activated_by' => $adminId,
        ]);
    }

    // Désactiver l'UE
    public function desactiver(): void
    {
        $this->update([
            'statut' => 'non_activee',
            'date_activation' => null,
            'activated_by' => null,
        ]);
    }

    // Calculer les heures effectuées par le vacataire pour cette UE
    public function getHeuresEffectueesAttribute(): float
    {
        // Calculer à partir des attendances (check-in/check-out)
        $attendances = \App\Models\Attendance::where('user_id', $this->vacataire_id)
            ->where('unite_enseignement_id', $this->id)
            ->where('type', 'check-in')
            ->where('status', 'valid')
            ->get();

        $totalHours = 0;
        foreach ($attendances as $checkIn) {
            // Trouver le check-out correspondant
            $checkOut = \App\Models\Attendance::where('user_id', $this->vacataire_id)
                ->where('unite_enseignement_id', $this->id)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', $checkIn->timestamp->format('Y-m-d'))
                ->first();

            if ($checkOut) {
                $totalHours += $checkIn->timestamp->diffInHours($checkOut->timestamp, true);
            }
        }

        return round($totalHours, 2);
    }

    // Calculer les heures restantes
    public function getHeuresRestantesAttribute(): float
    {
        return max(0, $this->volume_horaire_total - $this->heures_effectuees);
    }

    // Calculer le pourcentage de progression
    public function getPourcentageProgressionAttribute(): float
    {
        if ($this->volume_horaire_total == 0) {
            return 0;
        }
        return min(100, ($this->heures_effectuees / $this->volume_horaire_total) * 100);
    }

    // Calculer le montant payé (heures effectuées × taux horaire du vacataire)
    public function getMontantPayeAttribute(): float
    {
        $tauxHoraire = $this->vacataire->hourly_rate ?? 0;
        return $this->heures_effectuees * $tauxHoraire;
    }

    // Calculer le montant potentiel restant
    public function getMontantRestantAttribute(): float
    {
        $tauxHoraire = $this->vacataire->hourly_rate ?? 0;
        return $this->heures_restantes * $tauxHoraire;
    }

    // Calculer le montant maximum possible
    public function getMontantMaxAttribute(): float
    {
        $tauxHoraire = $this->vacataire->hourly_rate ?? 0;
        return $this->volume_horaire_total * $tauxHoraire;
    }
}
