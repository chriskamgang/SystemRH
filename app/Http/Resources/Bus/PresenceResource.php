<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un bus en ligne, tel que la carte des etudiants l'affiche (CDC 3.1).
 *
 * Le chauffeur n'est pas nomme : les etudiants suivent un vehicule, pas une
 * personne. Seule l'immatriculation, deja visible sur le bus, est exposee.
 */
class PresenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $tournee = $this->whenLoaded('tournee');

        return [
            'bus_id' => $this->bus_id,
            'immatriculation' => $this->bus?->immatriculation,
            'modele' => $this->bus?->modele,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'vitesse_kmh' => $this->vitesse_kmh,
            'cap_degres' => $this->cap_degres,

            // Ce qui distingue le bus au depot du bus en circulation.
            'etat' => $this->tournee_id ? 'en_tournee' : 'stationne',
            'tournee_id' => $this->tournee_id,

            'ligne' => $this->when(
                $this->tournee_id !== null && $this->relationLoaded('tournee'),
                fn () => $this->tournee?->affectation?->ligne?->nom,
            ),

            // Lieux desservis par le tour en cours : l'etudiant sait ainsi
            // si ce bus passe par chez lui.
            'lieux_desservis' => $this->when(
                $this->tournee_id !== null && $this->relationLoaded('tournee'),
                fn () => $this->tournee?->parcours?->etapes
                    ->pluck('lieu_id')
                    ->filter()
                    ->values() ?? collect(),
            ),

            'vu_le' => $this->vu_le?->toIso8601String(),
        ];
    }
}
