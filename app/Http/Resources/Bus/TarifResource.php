<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TarifResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'montant_fcfa' => $this->montant_fcfa,
            'jours_couverts' => $this->jours_couverts,
            'trajets_par_jour' => $this->trajets_par_jour,
            'montant_total_fcfa' => $this->montant_fcfa * $this->jours_couverts,
        ];
    }
}
