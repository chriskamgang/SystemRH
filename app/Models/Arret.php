<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arret extends Model
{
    use HasFactory;

    protected $fillable = [
        'ligne_id', 'nom', 'ordre', 'latitude', 'longitude',
        'rayon_validation_metres', 'est_campus', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'ordre' => 'integer',
            'rayon_validation_metres' => 'integer',
            'est_campus' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class);
    }

    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }

    public function tournees(): HasMany
    {
        return $this->hasMany(Tournee::class);
    }
}
