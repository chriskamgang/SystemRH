<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtudiantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule_insam' => $this->matricule_insam,

            // Point de ramassage declare : c'est la seule attache
            // geographique du profil, le campus variant d'un jour a l'autre.
            'lieu_ramassage' => new LieuResource($this->whenLoaded('lieuRamassage')),

            'ligne' => new LigneResource($this->whenLoaded('ligne')),
            'arret' => new ArretResource($this->whenLoaded('arret')),
        ];
    }
}
