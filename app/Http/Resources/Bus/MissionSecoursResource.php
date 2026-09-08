<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionSecoursResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'mode_affectation' => $this->mode_affectation,
            'affectee_le' => $this->affectee_le?->toIso8601String(),
            'acceptee_le' => $this->acceptee_le?->toIso8601String(),
            'terminee_le' => $this->terminee_le?->toIso8601String(),
            'passagers_recuperes' => $this->passagers_recuperes,
            'panne_confirmee' => $this->panne_confirmee,
            'prise_en_charge_confirmee' => $this->prise_en_charge_confirmee,
            'ouvre_droit_a_prime' => $this->ouvreDroitAPrime(),
            'panne' => new PanneResource($this->whenLoaded('panne')),
            'bus' => new BusResource($this->whenLoaded('bus')),
        ];
    }
}
