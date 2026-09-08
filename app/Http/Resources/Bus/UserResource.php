<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        // Les cles restent celles qu'attend l'application mobile ; seules
        // leurs sources changent, la table `users` fusionnee nommant
        // l'identite a la maniere d'Estuaire RH.
        return [
            'id' => $this->id,
            'nom' => $this->last_name,
            'prenom' => $this->first_name,
            'nom_complet' => $this->nom_complet,
            'telephone' => $this->telephone_bus,
            'email' => $this->email,
            'role' => $this->role_bus?->value,
            'role_libelle' => $this->role_bus?->libelle(),
            'actif' => $this->actif_bus,

            // Scolarite : le couple qui rattache l'etudiant a ses UE, donc
            // a l'emploi du temps servi par Estuaire RH. Vide tant qu'il
            // n'a pas ete renseigne a la completion de profil.
            'niveau' => $this->niveau,
            'specialite' => $this->specialite,
            'scolarite_renseignee' => filled($this->niveau) && filled($this->specialite),

            'etudiant' => new EtudiantResource($this->whenLoaded('etudiant')),
            'chauffeur' => new ChauffeurResource($this->whenLoaded('chauffeur')),
        ];
    }
}
