<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteEnseignement extends Model
{
    protected $table = 'unites_enseignement';

    protected $fillable = [
        'enseignant_id',
        'code_ue',
        'nom_matiere',
        'volume_horaire_total',
        'heures_effectuees_validees',
        'derniere_mise_a_jour_heures',
        'statut',
        'annee_academique',
        'semestre',
        'specialite',
        'niveau',
        'date_attribution',
        'date_activation',
        'created_by',
        'activated_by',
    ];

    protected $casts = [
        'volume_horaire_total' => 'decimal:2',
        'heures_effectuees_validees' => 'decimal:2',
        'derniere_mise_a_jour_heures' => 'datetime',
        'date_attribution' => 'datetime',
        'date_activation' => 'datetime',
        'semestre' => 'integer',
    ];

    /**
     * Relations
     */

    // Enseignant (vacataire ou semi-permanent) à qui l'UE est attribuée
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    // Alias pour rétrocompatibilité
    public function vacataire(): BelongsTo
    {
        return $this->enseignant();
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

    // Détails des paiements pour cette UE
    public function paymentDetails(): HasMany
    {
        return $this->hasMany(VacatairePaymentDetail::class, 'unite_enseignement_id');
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

    // Filtre par enseignant (vacataire ou semi-permanent)
    public function scopeForEnseignant($query, $enseignantId)
    {
        return $query->where('enseignant_id', $enseignantId);
    }

    // Alias pour rétrocompatibilité
    public function scopeForVacataire($query, $vacataireId)
    {
        return $this->scopeForEnseignant($query, $vacataireId);
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

    // Calculer les heures effectuées par l'enseignant pour cette UE
    public function getHeuresEffectueesAttribute(): float
    {
        $totalHours = 0;

        // ===== 1. PRÉSENCES GPS (Attendance) =====
        $attendances = \App\Models\Attendance::where('user_id', $this->enseignant_id)
            ->where('unite_enseignement_id', $this->id)
            ->where('type', 'check-in')
            ->where('status', 'valid')
            ->get();

        foreach ($attendances as $checkIn) {
            // Trouver le check-out correspondant
            $checkOut = \App\Models\Attendance::where('user_id', $this->enseignant_id)
                ->where('unite_enseignement_id', $this->id)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', $checkIn->timestamp->format('Y-m-d'))
                ->first();

            if ($checkOut) {
                $totalHours += $checkIn->timestamp->diffInHours($checkOut->timestamp, true);
            }
        }

        // ===== 2. PRÉSENCES MANUELLES (ManualAttendance) =====
        $manualAttendances = \App\Models\ManualAttendance::where('user_id', $this->enseignant_id)
            ->where('unite_enseignement_id', $this->id)
            ->get();

        foreach ($manualAttendances as $manualAttendance) {
            // Calculer les heures à partir de check_in_time et check_out_time
            $totalHours += $manualAttendance->duration_in_hours;
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

    // Calculer le montant payé (heures effectuées × taux horaire)
    // Note: Seulement pour les vacataires, pas pour les semi-permanents
    public function getMontantPayeAttribute(): float
    {
        $enseignant = $this->enseignant;

        // Si c'est un semi-permanent, ne pas calculer de montant (salaire fixe)
        if ($enseignant && $enseignant->isSemiPermanent()) {
            return 0;
        }

        $tauxHoraire = $enseignant->hourly_rate ?? 0;
        return $this->heures_effectuees * $tauxHoraire;
    }

    // Calculer le montant potentiel restant
    public function getMontantRestantAttribute(): float
    {
        $enseignant = $this->enseignant;

        // Si c'est un semi-permanent, ne pas calculer de montant (salaire fixe)
        if ($enseignant && $enseignant->isSemiPermanent()) {
            return 0;
        }

        $tauxHoraire = $enseignant->hourly_rate ?? 0;
        return $this->heures_restantes * $tauxHoraire;
    }

    // Calculer le montant maximum possible
    public function getMontantMaxAttribute(): float
    {
        $enseignant = $this->enseignant;

        // Si c'est un semi-permanent, ne pas calculer de montant (salaire fixe)
        if ($enseignant && $enseignant->isSemiPermanent()) {
            return 0;
        }

        $tauxHoraire = $enseignant->hourly_rate ?? 0;
        return $this->volume_horaire_total * $tauxHoraire;
    }

    // Ajouter des heures validées
    public function ajouterHeuresValidees(float $heures): void
    {
        $this->update([
            'heures_effectuees_validees' => $this->heures_effectuees_validees + $heures,
            'derniere_mise_a_jour_heures' => now(),
        ]);
    }

    // Soustraire des heures validées (lors de la suppression d'un paiement)
    public function soustraireHeuresValidees(float $heures): void
    {
        $this->update([
            'heures_effectuees_validees' => max(0, $this->heures_effectuees_validees - $heures),
            'derniere_mise_a_jour_heures' => now(),
        ]);
    }

    // Obtenir les heures restantes basées sur les heures validées
    public function getHeuresRestantesValideesAttribute(): float
    {
        return max(0, $this->volume_horaire_total - $this->heures_effectuees_validees);
    }

    // Calculer le total payé pour cette UE
    public function getTotalPayeAttribute(): float
    {
        return $this->paymentDetails()->sum('montant');
    }

    // Calculer le pourcentage de progression basé sur les heures validées
    public function getPourcentageProgressionValideesAttribute(): float
    {
        if ($this->volume_horaire_total == 0) {
            return 0;
        }
        return min(100, ($this->heures_effectuees_validees / $this->volume_horaire_total) * 100);
    }
}
