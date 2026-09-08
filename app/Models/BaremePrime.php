<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaremePrime extends Model
{
    use HasFactory;

    protected $table = 'baremes_primes';

    protected $fillable = ['code', 'libelle', 'montant_fcfa', 'periodicite', 'conditions', 'actif'];

    protected function casts(): array
    {
        return [
            'montant_fcfa' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function primes(): HasMany
    {
        return $this->hasMany(Prime::class, 'bareme_id');
    }
}
