<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_service' => $this->date_service?->toDateString(),
            'tours_prevus' => $this->tours_prevus,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'bus' => new BusResource($this->whenLoaded('bus')),
            'ligne' => new LigneResource($this->whenLoaded('ligne')),
        ];
    }
}
