<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrimeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'montant_fcfa' => $this->montant_fcfa,
            'est_penalite' => $this->estPenalite(),
            'date_acquisition' => $this->date_acquisition?->toDateString(),
            'periode' => $this->periode,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
        ];
    }
}
