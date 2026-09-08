<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chauffeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'matricule', 'numero_permis', 'permis_expire_le', 'disponible_secours',
    ];

    protected function casts(): array
    {
        return [
            'permis_expire_le' => 'date',
            'disponible_secours' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function pannes(): HasMany
    {
        return $this->hasMany(Panne::class);
    }

    public function missionsSecours(): HasMany
    {
        return $this->hasMany(MissionSecours::class);
    }

    public function primes(): HasMany
    {
        return $this->hasMany(Prime::class);
    }

    public function cloturesPaie(): HasMany
    {
        return $this->hasMany(CloturePaie::class);
    }

    /** Etat « en ligne » et derniere position connue (3.1). */
    public function presence(): HasOne
    {
        return $this->hasOne(PresenceChauffeur::class);
    }

    /** Cagnotte du mois en cours : somme des primes validees ou payees (3.3). */
    public function cagnotte(?string $periode = null): int
    {
        return (int) $this->primes()
            ->where('periode', $periode ?? now()->format('Y-m'))
            ->whereIn('statut', ['validee', 'payee'])
            ->sum('montant_fcfa');
    }
}
