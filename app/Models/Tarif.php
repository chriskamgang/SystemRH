<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarif extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'libelle', 'montant_fcfa', 'jours_couverts', 'trajets_par_jour', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'montant_fcfa' => 'integer',
            'jours_couverts' => 'integer',
            'trajets_par_jour' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    /** Nombre total de trajets ouverts par ce tarif. */
    public function trajetsTotal(): int
    {
        return $this->jours_couverts * $this->trajets_par_jour;
    }
}
