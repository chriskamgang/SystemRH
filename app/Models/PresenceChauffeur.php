<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Etat « en ligne » d'un chauffeur et derniere position connue (CDC 3.1).
 *
 * Le chauffeur passe en ligne des l'ouverture de l'application, avant tout
 * pointage : les etudiants voient le bus stationne comme le bus en route.
 */
class PresenceChauffeur extends Model
{
    use HasFactory;

    protected $table = 'presences_chauffeur';

    protected $fillable = [
        'chauffeur_id', 'bus_id', 'tournee_id',
        'latitude', 'longitude', 'vitesse_kmh', 'cap_degres', 'vu_le',
    ];

    /**
     * Delai de grace avant de considerer un chauffeur hors ligne.
     *
     * La position remonte toutes les quinze secondes ; une minute laisse
     * passer trois pings perdus — un tunnel, un reseau qui bascule — sans
     * faire disparaitre le bus de la carte des etudiants.
     */
    public const DELAI_GRACE_SECONDES = 60;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'vitesse_kmh' => 'integer',
            'cap_degres' => 'integer',
            'vu_le' => 'datetime',
        ];
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    /** Presences encore vivantes, position connue, donc affichables. */
    public function scopeEnLigne(Builder $query): Builder
    {
        return $query
            ->where('vu_le', '>=', now()->subSeconds(self::DELAI_GRACE_SECONDES))
            ->whereNotNull('bus_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    /** Vrai tant que le dernier ping tient dans le delai de grace. */
    public function estEnLigne(): bool
    {
        return $this->vu_le !== null
            && $this->vu_le->gt(now()->subSeconds(self::DELAI_GRACE_SECONDES));
    }
}
