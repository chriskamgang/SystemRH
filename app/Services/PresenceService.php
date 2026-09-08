<?php

namespace App\Services;

use App\Enums\StatutTournee;
use App\Events\PositionBusMiseAJour;
use App\Events\PresenceChauffeurMiseAJour;
use App\Models\Affectation;
use App\Models\ApprocheArret;
use App\Models\Chauffeur;
use App\Models\Lieu;
use App\Models\Position;
use App\Models\PresenceChauffeur;
use App\Models\Tournee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Presence en ligne des chauffeurs et diffusion de leur position (CDC 3.1).
 *
 * Le chauffeur passe en ligne des l'ouverture de l'application, sans
 * attendre d'avoir demarre un tour : l'etudiant voit alors le bus stationne
 * au depot aussi bien que le bus en circulation.
 *
 * Deux consequences suivent chaque position recue :
 *   - elle est diffusee aux etudiants qui suivent la carte ;
 *   - elle declenche, si un tour est ouvert, l'alerte de proximite aux
 *     etudiants de l'arret approche.
 */
class PresenceService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Distance sous laquelle les etudiants d'un arret sont prevenus.
     *
     * Cinq cents metres laissent le temps de descendre attendre le bus sans
     * prevenir si tot que l'information devienne inutile.
     */
    public const RAYON_APPROCHE_METRES = 500;

    /**
     * Vitesse de repli pour l'estimation, en km/h.
     *
     * Un bus a l'arret annonce 0 km/h : diviser par cette valeur donnerait
     * une infinite de minutes. On retient alors une moyenne urbaine.
     */
    private const VITESSE_REPLI_KMH = 25;

    /**
     * Enregistre un signe de vie et, si elle est fournie, la position.
     *
     * Appelee a l'ouverture de l'application puis a chaque battement. Le bus
     * et le tour sont deduits de l'affectation du jour : le client n'a pas a
     * les connaitre, et ne peut donc pas se declarer sur le bus d'un autre.
     */
    public function pointerPresence(
        Chauffeur $chauffeur,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $vitesse = null,
        ?int $cap = null,
    ): PresenceChauffeur {
        $affectation = $this->affectationDuJour($chauffeur);
        $tournee = $affectation ? $this->tourneeOuverte($affectation) : null;

        $attributs = [
            'bus_id' => $affectation?->bus_id,
            'tournee_id' => $tournee?->id,
            'vitesse_kmh' => $vitesse,
            'cap_degres' => $cap,
            'vu_le' => now(),
        ];

        // Une position absente n'efface pas la precedente : le chauffeur
        // reste visible la ou on l'a vu en dernier, plutot que de
        // disparaitre de la carte le temps d'un point GPS manque.
        if ($latitude !== null && $longitude !== null) {
            $attributs['latitude'] = $latitude;
            $attributs['longitude'] = $longitude;
        }

        $presence = PresenceChauffeur::updateOrCreate(
            ['chauffeur_id' => $chauffeur->id],
            $attributs,
        );

        $presence->setRelation('bus', $affectation?->bus);

        if ($latitude !== null && $longitude !== null) {
            // Un tour ouvert continue d'alimenter l'historique des
            // positions : c'est lui qui porte la trace d'exploitation.
            if ($tournee) {
                $this->enregistrerPositionDeTournee($tournee, $latitude, $longitude, $vitesse, $cap);
                $this->verifierApproches($tournee, $latitude, $longitude, $vitesse);
            }

            PresenceChauffeurMiseAJour::dispatch($presence);
        }

        return $presence;
    }

    /** Retire le chauffeur de la carte des etudiants. */
    public function quitter(Chauffeur $chauffeur): void
    {
        $presence = $chauffeur->presence;

        if (! $presence) {
            return;
        }

        $presence->setRelation('bus', $presence->bus);

        // Le passage hors ligne est diffuse avant l'effacement : sans cela,
        // le bus resterait affiche jusqu'a expiration du delai de grace.
        PresenceChauffeurMiseAJour::dispatch($presence, horsLigne: true);

        $presence->delete();
    }

    /**
     * Bus actuellement en ligne, avec leur derniere position.
     *
     * Alimente la carte des etudiants : tous les bus du reseau y figurent,
     * stationnes comme en circulation.
     *
     * @return Collection<int, PresenceChauffeur>
     */
    public function busEnLigne(): Collection
    {
        return PresenceChauffeur::enLigne()
            ->with([
                'bus',
                'tournee.affectation.ligne',
                'tournee.parcours.etapes.lieu',
            ])
            ->get();
    }

    // --- Geofence d'approche ---------------------------------------------

    /**
     * Previent les etudiants des arrets que le bus vient d'approcher.
     *
     * Seuls les arrets non encore desservis comptent : une fois le bus
     * passe, prevenir n'aurait plus de sens. L'unicite en base garantit
     * qu'un arret n'alerte qu'une fois par tour, meme si deux pings
     * arrivent en meme temps.
     */
    private function verifierApproches(
        Tournee $tournee,
        float $latitude,
        float $longitude,
        ?int $vitesse,
    ): void {
        // Un tour termine ou annule ne dessert plus personne.
        if (in_array($tournee->statut, [StatutTournee::Termine, StatutTournee::Annule], true)) {
            return;
        }

        foreach ($this->lieuxAPrevenir($tournee) as $lieu) {
            $distance = $this->geo->distanceLieu($lieu, $latitude, $longitude);

            if ($distance > self::RAYON_APPROCHE_METRES) {
                continue;
            }

            $this->declencherApproche($tournee, $lieu, (int) round($distance), $vitesse);
        }
    }

    /**
     * Lieux du parcours restant a desservir, alerte non encore envoyee.
     *
     * @return Collection<int, Lieu>
     */
    private function lieuxAPrevenir(Tournee $tournee): Collection
    {
        $parcours = $tournee->parcours;

        $lieux = $parcours
            ? $parcours->etapes()
                ->with('lieu')
                // Le terminus est un campus : personne n'y attend le bus.
                ->where('est_terminus', false)
                ->get()
                ->pluck('lieu')
                ->filter()
            : collect([$tournee->lieu])->filter();

        if ($lieux->isEmpty()) {
            return collect();
        }

        $dejaPrevenus = ApprocheArret::where('tournee_id', $tournee->id)
            ->pluck('lieu_id')
            ->all();

        return $lieux->reject(fn (Lieu $lieu) => in_array($lieu->id, $dejaPrevenus, true))->values();
    }

    /** Pose le verrou d'idempotence puis envoie l'alerte. */
    private function declencherApproche(
        Tournee $tournee,
        Lieu $lieu,
        int $distance,
        ?int $vitesse,
    ): void {
        try {
            ApprocheArret::create([
                'tournee_id' => $tournee->id,
                'lieu_id' => $lieu->id,
                'distance_metres' => $distance,
                'notifie_le' => now(),
            ]);
        } catch (QueryException) {
            // Un ping concurrent a pose le verrou en premier : l'alerte est
            // deja partie, il n'y a rien a faire.
            return;
        }

        $this->notifications->notifierBusApproche(
            $tournee,
            $lieu,
            $distance,
            $this->minutesEstimees($distance, $vitesse),
        );
    }

    /** Minutes avant l'arrivee, a la vitesse constatee. */
    private function minutesEstimees(int $distanceMetres, ?int $vitesse): ?int
    {
        $kmh = max($vitesse ?: self::VITESSE_REPLI_KMH, 10);

        return (int) ceil($distanceMetres / 1000 / $kmh * 60);
    }

    // --- Aides -----------------------------------------------------------

    /**
     * Historise la position sous le tour ouvert.
     *
     * Reprend la diffusion de PositionBusMiseAJour pour ne rien changer aux
     * clients qui ecoutent deja `bus.{id}` avec l'evenement `position.maj`.
     */
    private function enregistrerPositionDeTournee(
        Tournee $tournee,
        float $latitude,
        float $longitude,
        ?int $vitesse,
        ?int $cap,
    ): void {
        $position = Position::create([
            'tournee_id' => $tournee->id,
            'bus_id' => $tournee->affectation->bus_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'vitesse_kmh' => $vitesse,
            'cap_degres' => $cap,
            'releve_le' => now(),
        ]);

        PositionBusMiseAJour::dispatch($position);
    }

    /** Affectation du chauffeur pour aujourd'hui, s'il en a une. */
    private function affectationDuJour(Chauffeur $chauffeur): ?Affectation
    {
        return Affectation::where('chauffeur_id', $chauffeur->id)
            ->whereDate('date_service', today())
            ->with('bus', 'ligne')
            ->first();
    }

    /** Tour en cours sur cette affectation, s'il y en a un d'ouvert. */
    private function tourneeOuverte(Affectation $affectation): ?Tournee
    {
        return Tournee::where('affectation_id', $affectation->id)
            ->whereIn('statut', [
                StatutTournee::EnAttente,
                StatutTournee::Embarquement,
                StatutTournee::EnTransit,
            ])
            ->with('affectation.bus', 'parcours.etapes.lieu', 'lieu')
            ->latest('id')
            ->first();
    }
}
