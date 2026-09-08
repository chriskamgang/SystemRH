<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Retrait demande par un chauffeur depuis son wallet. */
class RetraitChauffeur extends Model
{
    protected $table = 'retraits_chauffeur';

    protected $fillable = [
        'wallet_id', 'chauffeur_id', 'montant_fcfa', 'telephone', 'provider',
        'statut', 'transaction_kpay_id', 'motif_echec', 'demande_le', 'traite_le',
    ];

    protected function casts(): array
    {
        return [
            'montant_fcfa' => 'integer',
            'demande_le' => 'datetime',
            'traite_le' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletChauffeur::class, 'wallet_id');
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionKpay::class, 'transaction_kpay_id');
    }

    public function estTerminal(): bool
    {
        return in_array($this->statut, ['paye', 'echoue', 'annule'], true);
    }
}
