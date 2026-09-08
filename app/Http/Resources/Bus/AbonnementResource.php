<?php

namespace App\Http\Resources\Bus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonnementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'montant_paye_fcfa' => $this->montant_paye_fcfa,
            'trajets_restants' => $this->trajets_restants,
            // Trajets ajoutes par une recharge, acquis une fois regles.
            'trajets_en_attente' => $this->trajets_en_attente,
            'montant_du_fcfa' => $this->montant_du_fcfa,
            'montant_a_regler_fcfa' => $this->montantARegler(),
            'attend_un_paiement' => $this->attendUnPaiement(),
            'utilisable' => $this->estUtilisable(),
            'statut' => $this->statut->value,
            'statut_libelle' => $this->statut->libelle(),
            'moyen_paiement' => $this->moyen_paiement,
            'reference_paiement' => $this->reference_paiement,
            'paye_le' => $this->paye_le?->toIso8601String(),
            'tarif' => new TarifResource($this->whenLoaded('tarif')),
        ];
    }
}
