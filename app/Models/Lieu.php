<?php

namespace App\Models;

use App\Enums\TypeLieu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lieu physique desservi : point de ramassage ou campus.
 *
 * Il existe indépendamment des lignes, plusieurs d'entre elles pouvant
 * desservir le même campus.
 */
class Lieu extends Model
{
    use HasFactory;

    protected $table = 'lieux';

    protected $fillable = [
        'nom', 'adresse', 'type', 'latitude', 'longitude',
        'rayon_validation_metres', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeLieu::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'rayon_validation_metres' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeParcours::class);
    }

    /** Étudiants qui ont déclaré ce lieu comme point de ramassage. */
    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class, 'lieu_ramassage_id');
    }

    public function estCampus(): bool
    {
        return $this->type === TypeLieu::Campus;
    }

    /** Libellé complet : « Campus A — Tandja en face de congelcam ». */
    public function getIntituleAttribute(): string
    {
        return $this->adresse ? "{$this->nom} — {$this->adresse}" : $this->nom;
    }
}
