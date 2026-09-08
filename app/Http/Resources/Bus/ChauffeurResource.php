<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChauffeurResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'numero_permis' => $this->numero_permis,
            'permis_expire_le' => $this->permis_expire_le?->toDateString(),
            'disponible_secours' => $this->disponible_secours,
        ];
    }
}
