<?php

namespace App\Events;

use App\Models\Position;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Diffuse la position d'un bus : suivi en direct cote etudiant (3.1)
 * et carte de supervision cote back-office (3.5).
 */
class PositionBusMiseAJour implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Position $position) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new Channel("bus.{$this->position->bus_id}"),
            new Channel('supervision.flotte'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'position.maj';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'bus_id' => $this->position->bus_id,
            'tournee_id' => $this->position->tournee_id,
            'latitude' => $this->position->latitude,
            'longitude' => $this->position->longitude,
            'vitesse_kmh' => $this->position->vitesse_kmh,
            'cap_degres' => $this->position->cap_degres,
            'releve_le' => $this->position->releve_le?->toIso8601String(),
        ];
    }
}
