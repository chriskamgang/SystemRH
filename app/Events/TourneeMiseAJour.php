<?php

namespace App\Events;

use App\Models\Tournee;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Diffuse le changement d'etat d'un tour a chaque pointage (cycle 3.2). */
class TourneeMiseAJour implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Tournee $tournee) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new Channel("tournee.{$this->tournee->id}"),
            new Channel("ligne.{$this->tournee->affectation->ligne_id}"),
            new Channel('supervision.flotte'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tournee.maj';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'tournee_id' => $this->tournee->id,
            'bus_id' => $this->tournee->affectation->bus_id,
            'ligne_id' => $this->tournee->affectation->ligne_id,
            'arret' => $this->tournee->arret?->nom,
            'numero_tour' => $this->tournee->numero_tour,
            'statut' => $this->tournee->statut->value,
            'statut_libelle' => $this->tournee->statut->libelle(),
            'effectif_embarque' => $this->tournee->effectif_embarque,
            'demarre_le' => $this->tournee->demarre_le?->toIso8601String(),
            'arrive_point_le' => $this->tournee->arrive_point_le?->toIso8601String(),
            'depart_le' => $this->tournee->depart_le?->toIso8601String(),
            'termine_le' => $this->tournee->termine_le?->toIso8601String(),
        ];
    }
}
