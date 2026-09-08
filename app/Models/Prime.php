<?php

namespace App\Models;

use App\Enums\StatutPrime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prime extends Model
{
    use HasFactory;

    protected $fillable = [
        'chauffeur_id', 'bareme_id', 'tournee_id', 'mission_secours_id',
        'type', 'libelle', 'montant_fcfa', 'date_acquisition', 'periode',
        'statut', 'validee_par', 'validee_le',
    ];

    protected function casts(): array
    {
        return [
            'montant_fcfa' => 'integer',
            'date_acquisition' => 'date',
            'statut' => StatutPrime::class,
            'validee_le' => 'datetime',
        ];
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function bareme(): BelongsTo
    {
        return $this->belongsTo(BaremePrime::class, 'bareme_id');
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    public function missionSecours(): BelongsTo
    {
        return $this->belongsTo(MissionSecours::class, 'mission_secours_id');
    }

    public function valideePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validee_par');
    }

    public function estPenalite(): bool
    {
        return $this->montant_fcfa < 0;
    }
}
