<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookKpay extends Model
{
    protected $table = 'webhooks_kpay';

    protected $fillable = [
        'evenement', 'kpay_id', 'external_id', 'statut', 'charge_utile',
        'signature_valide', 'traite', 'erreur_traitement', 'recu_le',
    ];

    protected function casts(): array
    {
        return [
            'charge_utile' => 'array',
            'signature_valide' => 'boolean',
            'traite' => 'boolean',
            'recu_le' => 'datetime',
        ];
    }
}
