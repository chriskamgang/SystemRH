<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcoursResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sens' => $this->sens->value,
            'sens_libelle' => $this->sens->libelleCourt(),
            'libelle' => $this->libelle,
            'heure_depart' => $this->heure_depart,
            'duree_reference_minutes' => $this->duree_reference_minutes,
            'etapes' => $this->whenLoaded(
                'etapes',
                fn () => $this->etapes->map(fn ($etape) => [
                    'ordre' => $etape->ordre,
                    'est_terminus' => $etape->est_terminus,
                    'minutes_depuis_depart' => $etape->minutes_depuis_depart,
                    'lieu' => new LieuResource($etape->lieu),
                ]),
            ),
        ];
    }
}
