<?php

namespace App\Models;

use App\Enums\StatutTournee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournee extends Model
{
    use HasFactory;

    protected $fillable = [
        'parcours_id', 'lieu_id',
        'affectation_id', 'arret_id', 'numero_tour', 'statut',
        'demarre_le', 'arrive_point_le', 'depart_le', 'termine_le',
        'effectif_embarque', 'duree_reelle_minutes', 'anomalie_duree', 'note_anomalie',
        'embarquements_valides', 'passagers_sans_ticket', 'motif_ecart', 'commentaire_ecart',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutTournee::class,
            'demarre_le' => 'datetime',
            'arrive_point_le' => 'datetime',
            'depart_le' => 'datetime',
            'termine_le' => 'datetime',
            'numero_tour' => 'integer',
            'effectif_embarque' => 'integer',
            'embarquements_valides' => 'integer',
            'passagers_sans_ticket' => 'integer',
            'duree_reelle_minutes' => 'integer',
            'anomalie_duree' => 'boolean',
        ];
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(Parcours::class);
    }

    /** Lieu desservi par ce tour. */
    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }

    public function arret(): BelongsTo
    {
        return $this->belongsTo(Arret::class);
    }

    public function pointages(): HasMany
    {
        return $this->hasMany(Pointage::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function trajets(): HasMany
    {
        return $this->hasMany(Trajet::class);
    }

    public function panne(): HasMany
    {
        return $this->hasMany(Panne::class);
    }

    public function estTerminee(): bool
    {
        return $this->statut === StatutTournee::Termine;
    }

    /** Taux de remplissage du bus sur ce tour, pour la supervision (3.5). */
    public function tauxOccupation(): ?float
    {
        $capacite = $this->affectation?->bus?->capacite;

        if (! $capacite || $this->effectif_embarque === null) {
            return null;
        }

        return round($this->effectif_embarque / $capacite * 100, 1);
    }
}
