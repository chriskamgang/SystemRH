<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Wallet du chauffeur : la recette de chaque trajet scanne y tombe au fil
 * de l'eau, et il declenche son retrait quand il le souhaite.
 */
class WalletChauffeur extends Model
{
    protected $table = 'wallets_chauffeur';

    protected $fillable = [
        'chauffeur_id', 'solde_fcfa', 'solde_reserve_fcfa',
        'total_percu_fcfa', 'total_retire_fcfa',
    ];

    protected function casts(): array
    {
        return [
            'solde_fcfa' => 'integer',
            'solde_reserve_fcfa' => 'integer',
            'total_percu_fcfa' => 'integer',
            'total_retire_fcfa' => 'integer',
        ];
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementWallet::class, 'wallet_id')->latest('id');
    }

    public function retraits(): HasMany
    {
        return $this->hasMany(RetraitChauffeur::class, 'wallet_id')->latest('id');
    }

    /** Ce que le chauffeur peut demander a retirer maintenant. */
    public function disponible(): int
    {
        return $this->solde_fcfa;
    }

    /** Un retrait est-il en cours d'aboutissement ? */
    public function aUnRetraitEnCours(): bool
    {
        return $this->solde_reserve_fcfa > 0;
    }
}
