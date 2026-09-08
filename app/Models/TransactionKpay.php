<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransactionKpay extends Model
{
    protected $table = 'transactions_kpay';

    protected $fillable = [
        'type', 'external_id', 'kpay_id', 'reference', 'provider_reference', 'statut',
        'montant', 'montant_net', 'frais', 'devise', 'provider', 'pays', 'telephone',
        'est_test', 'payable_type', 'payable_id', 'description', 'motif_echec',
        'metadonnees', 'reponse_brute', 'completee_le', 'echouee_le',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'montant_net' => 'integer',
            'frais' => 'integer',
            'est_test' => 'boolean',
            'metadonnees' => 'array',
            'reponse_brute' => 'array',
            'completee_le' => 'datetime',
            'echouee_le' => 'datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Plus aucune evolution n'est attendue de KPay.
     *
     * `REFUNDED` en fait partie : sans lui, un rafraichissement relirait le
     * paiement d'origine, toujours COMPLETED chez KPay, et rouvrirait des
     * droits que le remboursement vient de retirer.
     */
    public function estTerminale(): bool
    {
        return in_array($this->statut, ['COMPLETED', 'FAILED', 'CANCELLED', 'REFUNDED'], true);
    }

    public function estRemboursee(): bool
    {
        return $this->statut === 'REFUNDED';
    }

    public function estReussie(): bool
    {
        return $this->statut === 'COMPLETED';
    }
}
