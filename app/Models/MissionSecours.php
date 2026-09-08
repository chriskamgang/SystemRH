<?php

namespace App\Models;

use App\Enums\StatutMissionSecours;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MissionSecours extends Model
{
    use HasFactory;

    protected $table = 'missions_secours';

    protected $fillable = [
        'panne_id', 'chauffeur_id', 'bus_id', 'statut',
        'affectee_le', 'acceptee_le', 'terminee_le',
        'passagers_recuperes', 'panne_confirmee', 'prise_en_charge_confirmee',
        'mode_affectation', 'affectee_par',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutMissionSecours::class,
            'affectee_le' => 'datetime',
            'acceptee_le' => 'datetime',
            'terminee_le' => 'datetime',
            'passagers_recuperes' => 'integer',
            'panne_confirmee' => 'boolean',
            'prise_en_charge_confirmee' => 'boolean',
        ];
    }

    public function panne(): BelongsTo
    {
        return $this->belongsTo(Panne::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function affecteePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affectee_par');
    }

    public function prime(): HasOne
    {
        return $this->hasOne(Prime::class, 'mission_secours_id');
    }

    /**
     * Controle croise (regle 3.4) : la prime de secours n'est due que si
     * la panne est confirmee ET la prise en charge des passagers attestee.
     */
    public function ouvreDroitAPrime(): bool
    {
        return $this->statut === StatutMissionSecours::Terminee
            && $this->panne_confirmee
            && $this->prise_en_charge_confirmee;
    }
}
