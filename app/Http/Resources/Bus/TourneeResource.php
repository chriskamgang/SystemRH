<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourneeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_tour' => $this->numero_tour,
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'effectif_embarque' => $this->effectif_embarque,
            'taux_occupation' => $this->tauxOccupation(),
            // Comptage billettique : tickets scannes, ecart et sa justification.
            'embarquements_valides' => $this->embarquements_valides,
            'passagers_sans_ticket' => $this->passagers_sans_ticket,
            'motif_ecart' => $this->motif_ecart,
            'commentaire_ecart' => $this->commentaire_ecart,
            'ecart_justifie' => $this->passagers_sans_ticket === 0 || filled($this->motif_ecart),
            'duree_reelle_minutes' => $this->duree_reelle_minutes,
            'anomalie_duree' => $this->anomalie_duree,
            'horodatage' => [
                'demarre_le' => $this->demarre_le?->toIso8601String(),
                'arrive_point_le' => $this->arrive_point_le?->toIso8601String(),
                'depart_le' => $this->depart_le?->toIso8601String(),
                'termine_le' => $this->termine_le?->toIso8601String(),
            ],
            'lieu' => new LieuResource($this->whenLoaded('lieu')),
            'parcours' => new ParcoursResource($this->whenLoaded('parcours')),
            'affectation' => new AffectationResource($this->whenLoaded('affectation')),
        ];
    }
}
