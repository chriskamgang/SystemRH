<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id', 'tournee_id', 'latitude', 'longitude', 'vitesse_kmh', 'cap_degres', 'releve_le',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'vitesse_kmh' => 'integer',
            'cap_degres' => 'integer',
            'releve_le' => 'datetime',
        ];
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }
}
