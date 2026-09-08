<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'immatriculation' => $this->immatriculation,
            'modele' => $this->modele,
            'capacite' => $this->capacite,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'derniere_position' => new PositionResource($this->whenLoaded('dernierePosition')),
        ];
    }
}
