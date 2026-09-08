<?php

namespace App\Events;

use App\Models\Panne;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Alerte de panne poussee en direct vers le back-office (3.5). */
class PanneDeclaree implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Panne $panne) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('supervision.incidents')];
    }

    public function broadcastAs(): string
    {
        return 'panne.declaree';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'panne_id' => $this->panne->id,
            'bus' => $this->panne->bus?->immatriculation,
            'chauffeur' => $this->panne->chauffeur?->user?->nom_complet,
            'type_panne' => $this->panne->type_panne,
            'passagers_immobilises' => $this->panne->passagers_immobilises,
            'latitude' => $this->panne->latitude,
            'longitude' => $this->panne->longitude,
            'declaree_le' => $this->panne->declaree_le?->toIso8601String(),
        ];
    }
}
