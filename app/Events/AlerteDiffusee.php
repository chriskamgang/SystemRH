<?php

namespace App\Events;

use App\Models\NotificationApp;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Notification individuelle poussee a un utilisateur sur son canal prive. */
class AlerteDiffusee implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly NotificationApp $notification) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("utilisateur.{$this->notification->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'titre' => $this->notification->titre,
            'message' => $this->notification->message,
            'donnees' => $this->notification->donnees,
            'cree_le' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
