<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'vitesse_kmh' => $this->vitesse_kmh,
            'cap_degres' => $this->cap_degres,
            'releve_le' => $this->releve_le?->toIso8601String(),
        ];
    }
}
