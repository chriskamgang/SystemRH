<?php

namespace App\Models;

use App\Enums\SensParcours;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ligne extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'nom', 'description', 'duree_trajet_minutes', 'tours_prevus_par_jour', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'duree_trajet_minutes' => 'integer',
            'tours_prevus_par_jour' => 'integer',
        ];
    }

    public function arrets(): HasMany
    {
        return $this->hasMany(Arret::class)->orderBy('ordre');
    }

    public function parcours(): HasMany
    {
        return $this->hasMany(Parcours::class);
    }

    /** Itineraire du matin : points de ramassage vers le campus. */
    public function parcoursAller(): ?Parcours
    {
        return $this->parcours()
            ->where('sens', SensParcours::Aller)
            ->where('actif', true)
            ->first();
    }

    /** Itineraire du soir : campus vers les points de descente. */
    public function parcoursRetour(): ?Parcours
    {
        return $this->parcours()
            ->where('sens', SensParcours::Retour)
            ->where('actif', true)
            ->first();
    }

    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /** Nombre d'etudiants inscrits sur la ligne : base du dimensionnement de flotte (3.5). */
    public function effectifInscrit(): int
    {
        return $this->etudiants()->count();
    }
}
