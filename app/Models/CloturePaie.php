<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloturePaie extends Model
{
    use HasFactory;

    protected $table = 'clotures_paie';

    protected $fillable = [
        'periode', 'chauffeur_id', 'tours_valides', 'secours_realises', 'jours_assiduite',
        'effectif_transporte', 'total_primes_fcfa', 'total_penalites_fcfa', 'net_a_payer_fcfa',
        'statut', 'cloturee_par', 'cloturee_le',
    ];

    protected function casts(): array
    {
        return [
            'tours_valides' => 'integer',
            'secours_realises' => 'integer',
            'jours_assiduite' => 'integer',
            'effectif_transporte' => 'integer',
            'total_primes_fcfa' => 'integer',
            'total_penalites_fcfa' => 'integer',
            'net_a_payer_fcfa' => 'integer',
            'cloturee_le' => 'datetime',
        ];
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function clotureePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloturee_par');
    }
}
