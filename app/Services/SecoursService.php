<?php

namespace App\Services;

use App\Enums\StatutBus;
use App\Enums\StatutMissionSecours;
use App\Enums\StatutPanne;
use App\Events\PanneDeclaree;
use App\Models\Bus;
use App\Models\Chauffeur;
use App\Models\MissionSecours;
use App\Models\Panne;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Module pannes et missions de secours (sections 3.3 et 3.5).
 */
class SecoursService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly PrimeService $primes,
        private readonly NotificationService $notifications,
    ) {}

    /** Le chauffeur en difficulte declare une panne : alerte immediate au back-office. */
    public function declarerPanne(
        Chauffeur $chauffeur,
        Bus $bus,
        ?Tournee $tournee,
        array $donnees,
    ): Panne {
        return DB::transaction(function () use ($chauffeur, $bus, $tournee, $donnees) {
            $panne = Panne::create([
                'bus_id' => $bus->id,
                'chauffeur_id' => $chauffeur->id,
                'tournee_id' => $tournee?->id,
                'type_panne' => $donnees['type_panne'] ?? null,
                'description' => $donnees['description'] ?? null,
                'latitude' => $donnees['latitude'] ?? null,
                'longitude' => $donnees['longitude'] ?? null,
                'passagers_immobilises' => $donnees['passagers_immobilises']
                    ?? $tournee?->effectif_embarque,
                'statut' => StatutPanne::Declaree,
                'declaree_le' => now(),
            ]);

            $bus->update(['statut' => StatutBus::EnPanne]);

            PanneDeclaree::dispatch($panne->load('bus', 'chauffeur.user'));
            $this->notifications->alerterGestionnairesPanne($panne);

            // Les etudiants qui attendent a l'arret sont les premiers
            // concernes : ils sont prevenus sans attendre qu'un secours
            // soit affecte (3.1).
            $this->notifications->notifierEtudiantsPanne($panne->load('tournee.parcours.etapes'));

            return $panne;
        });
    }

    /**
     * Affecte un chauffeur disponible a la panne (3.5 : manuel ou semi-automatique).
     * En mode semi-automatique, le chauffeur retenu est le plus proche du lieu de la panne.
     */
    public function affecterSecours(
        Panne $panne,
        ?Chauffeur $chauffeur = null,
        ?User $operateur = null,
    ): MissionSecours {
        if ($panne->missionSecours) {
            return $panne->missionSecours;
        }

        $mode = $chauffeur ? 'manuelle' : 'semi_automatique';
        $chauffeur ??= $this->chauffeurLePlusProche($panne);

        if (! $chauffeur) {
            throw new \RuntimeException('Aucun chauffeur disponible pour une mission de secours.');
        }

        $bus = $this->busDisponible($chauffeur);

        if (! $bus) {
            throw new \RuntimeException('Aucun bus disponible pour la mission de secours.');
        }

        return DB::transaction(function () use ($panne, $chauffeur, $bus, $mode, $operateur) {
            $mission = MissionSecours::create([
                'panne_id' => $panne->id,
                'chauffeur_id' => $chauffeur->id,
                'bus_id' => $bus->id,
                'statut' => StatutMissionSecours::Affectee,
                'affectee_le' => now(),
                'mode_affectation' => $mode,
                'affectee_par' => $operateur?->id,
            ]);

            $panne->update(['statut' => StatutPanne::PriseEnCharge]);

            $this->notifications->notifierMissionSecours($mission);

            // Le vehicule de remplacement est connu : les etudiants peuvent
            // decider d'attendre plutot que de partir a pied.
            $this->notifications->notifierSecoursEnRoute(
                $mission->load('bus', 'panne.tournee.parcours.etapes'),
            );

            return $mission;
        });
    }

    public function accepterMission(MissionSecours $mission): MissionSecours
    {
        $mission->update([
            'statut' => StatutMissionSecours::Acceptee,
            'acceptee_le' => now(),
        ]);

        return $mission->refresh();
    }

    public function demarrerMission(MissionSecours $mission): MissionSecours
    {
        $mission->update(['statut' => StatutMissionSecours::EnRoute]);
        $mission->bus->update(['statut' => StatutBus::EnService]);

        return $mission->refresh();
    }

    /**
     * Cloture la mission. Le controle croise (3.4) impose que la panne soit confirmee
     * et la prise en charge des passagers attestee pour ouvrir droit a la prime.
     */
    public function terminerMission(
        MissionSecours $mission,
        int $passagersRecuperes,
        bool $panneConfirmee = true,
    ): MissionSecours {
        return DB::transaction(function () use ($mission, $passagersRecuperes, $panneConfirmee) {
            $mission->update([
                'statut' => StatutMissionSecours::Terminee,
                'terminee_le' => now(),
                'passagers_recuperes' => $passagersRecuperes,
                'panne_confirmee' => $panneConfirmee,
                'prise_en_charge_confirmee' => $passagersRecuperes > 0,
            ]);

            $mission->panne->update([
                'statut' => StatutPanne::Resolue,
                'resolue_le' => now(),
            ]);

            $mission->bus->update(['statut' => StatutBus::Disponible]);

            $this->primes->crediterPrimeSecours($mission->refresh());

            return $mission->refresh();
        });
    }

    /** Chauffeur volontaire le plus proche du lieu de la panne, hors chauffeur en panne. */
    private function chauffeurLePlusProche(Panne $panne): ?Chauffeur
    {
        $candidats = Chauffeur::query()
            ->where('disponible_secours', true)
            ->where('id', '!=', $panne->chauffeur_id)
            ->whereHas('user', fn ($q) => $q->where('actif_bus', true))
            ->whereDoesntHave('missionsSecours', fn ($q) => $q->whereIn('statut', [
                StatutMissionSecours::Affectee,
                StatutMissionSecours::Acceptee,
                StatutMissionSecours::EnRoute,
            ]))
            ->with('affectations.bus.dernierePosition')
            ->get();

        if ($panne->latitude === null || $panne->longitude === null) {
            return $candidats->first();
        }

        return $candidats
            ->map(function (Chauffeur $c) use ($panne) {
                $position = $c->affectations
                    ->sortByDesc('date_service')
                    ->first()?->bus?->dernierePosition;

                $c->distance_panne = $position
                    ? $this->geo->distance(
                        $position->latitude, $position->longitude,
                        $panne->latitude, $panne->longitude,
                    )
                    : PHP_FLOAT_MAX;

                return $c;
            })
            ->sortBy('distance_panne')
            ->first();
    }

    private function busDisponible(Chauffeur $chauffeur): ?Bus
    {
        // Priorite au bus deja affecte au chauffeur aujourd'hui, sinon n'importe quel bus libre.
        $busAffecte = $chauffeur->affectations()
            ->whereDate('date_service', today())
            ->with('bus')
            ->first()?->bus;

        if ($busAffecte && $busAffecte->statut === StatutBus::Disponible) {
            return $busAffecte;
        }

        return Bus::where('statut', StatutBus::Disponible)->where('actif', true)->first();
    }
}
