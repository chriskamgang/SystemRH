<?php

namespace App\Models;

use App\Enums\StatutPanne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Panne extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id', 'chauffeur_id', 'tournee_id', 'type_panne', 'description',
        'latitude', 'longitude', 'passagers_immobilises', 'statut', 'declaree_le', 'resolue_le',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'passagers_immobilises' => 'integer',
            'statut' => StatutPanne::class,
            'declaree_le' => 'datetime',
            'resolue_le' => 'datetime',
        ];
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    public function missionSecours(): HasOne
    {
        return $this->hasOne(MissionSecours::class);
    }
}
