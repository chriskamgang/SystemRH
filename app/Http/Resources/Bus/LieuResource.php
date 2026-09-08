<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LieuResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'type' => $this->type->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rayon_validation_metres' => $this->rayon_validation_metres,

            // Lignes desservant ce lieu, chargées à la demande : l'écran
            // d'inscription les affiche sous le nom du point.
            'lignes' => $this->when(
                $this->relationLoaded('etapes'),
                fn () => $this->etapes
                    ->map(fn ($etape) => $etape->parcours?->ligne)
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn ($ligne) => [
                        'id' => $ligne->id,
                        'code' => $ligne->code,
                        'nom' => $ligne->nom,
                    ]),
            ),
        ];
    }
}
