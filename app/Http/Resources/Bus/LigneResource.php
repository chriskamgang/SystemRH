<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LigneResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'nom' => $this->nom,
            'description' => $this->description,
            'duree_trajet_minutes' => $this->duree_trajet_minutes,
            'tours_prevus_par_jour' => $this->tours_prevus_par_jour,
            'arrets' => ArretResource::collection($this->whenLoaded('arrets')),
        ];
    }
}
