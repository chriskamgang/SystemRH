<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PanneResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_panne' => $this->type_panne,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'passagers_immobilises' => $this->passagers_immobilises,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'declaree_le' => $this->declaree_le?->toIso8601String(),
            'resolue_le' => $this->resolue_le?->toIso8601String(),
            'bus' => new BusResource($this->whenLoaded('bus')),
            'mission_secours' => new MissionSecoursResource($this->whenLoaded('missionSecours')),
        ];
    }
}
