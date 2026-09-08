<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Position d'un lieu dans un parcours. */
class EtapeParcours extends Model
{
    use HasFactory;

    protected $table = 'etapes_parcours';

    protected $fillable = [
        'parcours_id', 'lieu_id', 'ordre', 'est_terminus',
        'minutes_depuis_depart',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'est_terminus' => 'boolean',
            'minutes_depuis_depart' => 'integer',
        ];
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(Parcours::class);
    }

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }
}
