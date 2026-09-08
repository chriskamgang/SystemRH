<?php

namespace App\Models;

use App\Enums\StatutBus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bus';

    protected $fillable = ['immatriculation', 'modele', 'capacite', 'statut', 'actif'];

    protected function casts(): array
    {
        return [
            'statut' => StatutBus::class,
            'capacite' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function pannes(): HasMany
    {
        return $this->hasMany(Panne::class);
    }

    /** Derniere position GPS connue, pour la carte de supervision (3.5). */
    public function dernierePosition(): HasOne
    {
        return $this->hasOne(Position::class)->latestOfMany('releve_le');
    }
}
