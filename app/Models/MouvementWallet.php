<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Une variation du solde d'un wallet.
 *
 * Chaque ligne porte son montant signe et le solde qui en resulte : la
 * somme des mouvements doit toujours egaler le solde courant.
 */
class MouvementWallet extends Model
{
    protected $table = 'mouvements_wallet';

    protected $fillable = [
        'wallet_id', 'type', 'montant_fcfa', 'libelle',
        'origine_type', 'origine_id', 'solde_apres_fcfa',
    ];

    protected function casts(): array
    {
        return [
            'montant_fcfa' => 'integer',
            'solde_apres_fcfa' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletChauffeur::class, 'wallet_id');
    }

    public function origine(): MorphTo
    {
        return $this->morphTo();
    }

    public function estUneEntree(): bool
    {
        return $this->montant_fcfa > 0;
    }
}
