<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArretResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'ordre' => $this->ordre,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rayon_validation_metres' => $this->rayon_validation_metres,
            'est_campus' => $this->est_campus,
        ];
    }
}
