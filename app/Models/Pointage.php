<?php

namespace App\Models;

use App\Enums\EtapePointage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pointage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournee_id', 'etape', 'latitude', 'longitude',
        'distance_metres', 'dans_zone', 'effectif', 'pointe_le',
    ];

    protected function casts(): array
    {
        return [
            'etape' => EtapePointage::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'distance_metres' => 'integer',
            'dans_zone' => 'boolean',
            'effectif' => 'integer',
            'pointe_le' => 'datetime',
        ];
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }
}
