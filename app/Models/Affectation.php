<?php

namespace App\Models;

use App\Enums\StatutAffectation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id', 'chauffeur_id', 'ligne_id', 'date_service', 'tours_prevus', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_service' => 'date',
            'tours_prevus' => 'integer',
            'statut' => StatutAffectation::class,
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

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class);
    }

    public function tournees(): HasMany
    {
        return $this->hasMany(Tournee::class);
    }

    public function toursValides(): int
    {
        return $this->tournees()->where('statut', 'termine')->count();
    }
}
