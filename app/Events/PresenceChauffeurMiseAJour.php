<?php

namespace App\Events;

use App\Models\PresenceChauffeur;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Diffuse la position d'un bus en ligne, tournee ouverte ou non (CDC 3.1).
 *
 * PositionBusMiseAJour ne parle que d'un tour en cours ; cet evenement
 * couvre aussi le bus stationne, que l'etudiant doit voir sur sa carte des
 * que le chauffeur a ouvert l'application.
 *
 * Le canal « flotte » est public : il ne porte qu'une immatriculation et une
 * position, aucune donnee nominative. Le nom du chauffeur, lui, n'y figure
 * pas — les etudiants suivent un bus, pas une personne.
 */
class PresenceChauffeurMiseAJour implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PresenceChauffeur $presence,
        /** Vrai quand le chauffeur vient de quitter le service. */
        public readonly bool $horsLigne = false,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        $canaux = [
            new Channel('flotte'),
            new Channel('supervision.flotte'),
        ];

        if ($this->presence->bus_id) {
            $canaux[] = new Channel("bus.{$this->presence->bus_id}");
        }

        return $canaux;
    }

    public function broadcastAs(): string
    {
        return 'presence.maj';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'chauffeur_id' => $this->presence->chauffeur_id,
            'bus_id' => $this->presence->bus_id,
            'tournee_id' => $this->presence->tournee_id,
            'immatriculation' => $this->presence->bus?->immatriculation,
            'latitude' => $this->presence->latitude,
            'longitude' => $this->presence->longitude,
            'vitesse_kmh' => $this->presence->vitesse_kmh,
            'cap_degres' => $this->presence->cap_degres,

            // « en_tournee » quand un tour est ouvert, « stationne » sinon :
            // c'est ce qui distingue le bus qui roule du bus au depot.
            'etat' => $this->presence->tournee_id ? 'en_tournee' : 'stationne',
            'en_ligne' => ! $this->horsLigne,
            'vu_le' => $this->presence->vu_le?->toIso8601String(),
        ];
    }
}
