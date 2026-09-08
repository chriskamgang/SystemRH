<?php

namespace App\Services;

use App\Enums\EtapePointage;
use App\Enums\MotifEcartEffectif;
use App\Enums\SensParcours;
use App\Enums\StatutAffectation;
use App\Enums\StatutBus;
use App\Enums\StatutTournee;
use App\Events\PositionBusMiseAJour;
use App\Events\TourneeMiseAJour;
use App\Exceptions\PointageInvalideException;
use App\Models\Affectation;
use App\Models\Parcours;
use App\Models\Pointage;
use App\Models\Tournee;
use Illuminate\Support\Facades\DB;

/**
 * Orchestre le cycle de pointage sequentiel decrit en 3.2 du cahier des charges,
 * et applique les regles de controle et d'anti-fraude de la section 3.4.
 */
class PointageService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly PrimeService $primes,
        private readonly BilletterieService $billetterie,
    ) {}

    /**
     * Etape 1 - Demarrage service : ouvre le tour suivant de l'affectation du jour
     * et met le bus en circulation.
     */
    public function demarrerService(
        Affectation $affectation,
        float $latitude,
        float $longitude,
        ?SensParcours $sens = null,
    ): Tournee {
        if ($affectation->statut === StatutAffectation::Annulee) {
            throw new PointageInvalideException(
                'Cette affectation a été annulée.',
                'affectation_annulee',
            );
        }

        $tourneeOuverte = $affectation->tournees()
            ->whereNotIn('statut', [StatutTournee::Termine, StatutTournee::Annule])
            ->first();

        if ($tourneeOuverte) {
            throw new PointageInvalideException(
                'Un tour est déjà en cours, terminez-le avant d’en démarrer un autre.',
                'tour_deja_en_cours',
                ['tournee_id' => $tourneeOuverte->id],
            );
        }

        $toursEffectues = $affectation->tournees()->where('statut', StatutTournee::Termine)->count();

        if ($toursEffectues >= $affectation->tours_prevus) {
            throw new PointageInvalideException(
                'Tous les tours prévus pour aujourd’hui ont été réalisés.',
                'tours_epuises',
                ['tours_prevus' => $affectation->tours_prevus],
            );
        }

        // Le sens depend du moment : le matin on ramasse vers le campus,
        // le soir on repart du campus vers les points de descente.
        $parcours = $sens
            ? $affectation->ligne->parcours()->where('sens', $sens)->where('actif', true)->first()
            : $this->parcoursDuMoment($affectation);

        if (! $parcours) {
            throw new PointageInvalideException(
                'Aucun parcours actif n’est défini sur cette ligne.',
                'parcours_introuvable',
            );
        }

        // Le tour dessert le premier lieu du parcours.
        $lieu = $parcours->lieuDepart();

        if (! $lieu) {
            throw new PointageInvalideException(
                'Ce parcours ne comporte aucun lieu desservi.',
                'lieu_introuvable',
                ['parcours_id' => $parcours->id],
            );
        }

        return DB::transaction(function () use ($affectation, $parcours, $lieu, $latitude, $longitude, $toursEffectues) {
            $tournee = $affectation->tournees()->create([
                'parcours_id' => $parcours->id,
                'lieu_id' => $lieu->id,
                'numero_tour' => $toursEffectues + 1,
                'statut' => StatutTournee::EnAttente,
                'demarre_le' => now(),
            ]);

            $this->enregistrerPointage($tournee, EtapePointage::Demarrage, $latitude, $longitude, dansZone: true);

            $affectation->update(['statut' => StatutAffectation::Active]);
            $affectation->bus->update(['statut' => StatutBus::EnService]);

            $this->enregistrerPosition($tournee, $latitude, $longitude);

            TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

            return $tournee;
        });
    }

    /**
     * Etape 2 - Arrive au point : passe le tour en embarquement.
     * Le bouton n'est valide que dans la zone de l'arret (regle 3.4).
     */
    public function arriverAuPoint(Tournee $tournee, float $latitude, float $longitude): Tournee
    {
        $this->assurerSequence($tournee, EtapePointage::ArrivePoint);
        $dansZone = $this->assurerZone($tournee, EtapePointage::ArrivePoint, $latitude, $longitude);

        return DB::transaction(function () use ($tournee, $latitude, $longitude, $dansZone) {
            $tournee->update([
                'statut' => StatutTournee::Embarquement,
                'arrive_point_le' => now(),
            ]);

            $this->enregistrerPointage($tournee, EtapePointage::ArrivePoint, $latitude, $longitude, $dansZone);
            $this->enregistrerPosition($tournee, $latitude, $longitude);

            TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

            return $tournee->refresh();
        });
    }

    /**
     * Etape 3 - Saisie de l'effectif reel embarque.
     * C'est la mesure anti-fraude du comptage (objectif "Comptage & Transparence").
     */
    public function saisirEffectif(Tournee $tournee, int $effectif, float $latitude, float $longitude): Tournee
    {
        $this->assurerSequence($tournee, EtapePointage::Effectif);

        $capacite = $tournee->affectation->bus->capacite;

        if ($effectif < 0 || $effectif > $capacite) {
            throw new PointageInvalideException(
                "L’effectif saisi doit être compris entre 0 et la capacité du bus ({$capacite} places).",
                'effectif_hors_capacite',
                ['capacite' => $capacite, 'effectif' => $effectif],
            );
        }

        return DB::transaction(function () use ($tournee, $effectif, $latitude, $longitude) {
            $tournee->update(['effectif_embarque' => $effectif]);

            // Confronte le comptage aux tickets scannes : la difference est le
            // nombre de passagers montes sans titre de transport.
            $this->billetterie->recalculerEcart($tournee->refresh());

            $pointage = $this->enregistrerPointage(
                $tournee, EtapePointage::Effectif, $latitude, $longitude, dansZone: true,
            );
            $pointage->update(['effectif' => $effectif]);

            TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

            return $tournee->refresh();
        });
    }

    /**
     * Justifie l'ecart entre l'effectif compte et les tickets scannes.
     * Sans cette justification, le depart reste bloque.
     */
    public function justifierEcart(
        Tournee $tournee,
        MotifEcartEffectif $motif,
        ?string $commentaire = null,
    ): Tournee {
        $ecart = $this->billetterie->recalculerEcart($tournee->refresh());

        if ($ecart === 0) {
            throw new PointageInvalideException(
                'Aucun écart à justifier sur ce tour.',
                'aucun_ecart',
            );
        }

        if ($motif->exigeCommentaire() && blank($commentaire)) {
            throw new PointageInvalideException(
                'Précisez le motif de l’écart.',
                'commentaire_requis',
            );
        }

        $tournee->update([
            'motif_ecart' => $motif->value,
            'commentaire_ecart' => $commentaire,
        ]);

        TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

        return $tournee->refresh();
    }

    /** Etape 4 - Depart vers le campus : le bus passe en transit. */
    public function partirVersCampus(Tournee $tournee, float $latitude, float $longitude): Tournee
    {
        $this->assurerSequence($tournee, EtapePointage::Depart);

        if ($tournee->effectif_embarque === null) {
            throw new PointageInvalideException(
                'Saisissez l’effectif embarqué avant de déclarer le départ.',
                'effectif_manquant',
            );
        }

        // L'ecart est recalcule au dernier moment : un scan a pu intervenir
        // apres la saisie de l'effectif.
        $ecart = $this->billetterie->recalculerEcart($tournee->refresh());

        if ($ecart > 0 && blank($tournee->motif_ecart)) {
            throw new PointageInvalideException(
                "{$ecart} passager".($ecart > 1 ? 's sont montés' : ' est monté')
                    .' sans ticket validé. Justifiez cet écart avant de partir.',
                'ecart_non_justifie',
                [
                    'effectif_embarque' => $tournee->effectif_embarque,
                    'embarquements_valides' => $tournee->embarquements_valides,
                    'passagers_sans_ticket' => $ecart,
                    'motifs' => array_map(
                        fn (MotifEcartEffectif $m) => ['valeur' => $m->value, 'libelle' => $m->libelle()],
                        MotifEcartEffectif::cases(),
                    ),
                ],
            );
        }

        $dansZone = $this->assurerZone($tournee, EtapePointage::Depart, $latitude, $longitude);

        return DB::transaction(function () use ($tournee, $latitude, $longitude, $dansZone) {
            $tournee->update([
                'statut' => StatutTournee::EnTransit,
                'depart_le' => now(),
            ]);

            $this->enregistrerPointage($tournee, EtapePointage::Depart, $latitude, $longitude, $dansZone);
            $this->enregistrerPosition($tournee, $latitude, $longitude);

            TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

            return $tournee->refresh();
        });
    }

    /**
     * Etape 5 - Arrivee au campus : cloture le tour, controle la coherence temporelle
     * du trajet (regle 3.4) puis credite la prime de tour.
     */
    public function terminerTour(Tournee $tournee, float $latitude, float $longitude): Tournee
    {
        $this->assurerSequence($tournee, EtapePointage::Termine);

        // Le controle porte ici sur le terminus du parcours : le campus le
        // matin, le dernier point de descente le soir.
        $terminus = $tournee->parcours?->lieuTerminus();
        $dansZone = true;

        if ($terminus) {
            $distance = $this->geo->distanceLieu($terminus, $latitude, $longitude);
            $dansZone = $distance <= $terminus->rayon_validation_metres;

            if (! $dansZone) {
                throw new PointageInvalideException(
                    "Vous devez être arrivé à « {$terminus->nom} » pour clôturer le tour.",
                    'hors_zone',
                    [
                        'distance_metres' => round($distance),
                        'rayon_metres' => $terminus->rayon_validation_metres,
                        'lieu' => $terminus->nom,
                    ],
                );
            }
        }

        return DB::transaction(function () use ($tournee, $latitude, $longitude, $dansZone) {
            $dureeReelle = $tournee->depart_le
                ? (int) round($tournee->depart_le->diffInSeconds(now()) / 60)
                : null;

            // Coherence temporelle : un tour cloture trop vite est signale comme anomalie.
            $dureeReference = $tournee->parcours?->duree_reference_minutes
                ?? $tournee->affectation->ligne->duree_trajet_minutes;
            $anomalie = $dureeReelle !== null && $dureeReelle < $dureeReference * 0.5;

            $tournee->update([
                'statut' => StatutTournee::Termine,
                'termine_le' => now(),
                'duree_reelle_minutes' => $dureeReelle,
                'anomalie_duree' => $anomalie,
                'note_anomalie' => $anomalie
                    ? "Tour clôturé en {$dureeReelle} min pour une durée de référence de {$dureeReference} min."
                    : null,
            ]);

            $this->enregistrerPointage($tournee, EtapePointage::Termine, $latitude, $longitude, $dansZone);
            $this->enregistrerPosition($tournee, $latitude, $longitude);

            $affectation = $tournee->affectation;
            $affectation->bus->update(['statut' => StatutBus::Disponible]);

            // Le dernier tour prevu cloture la journee de service.
            if ($affectation->tournees()->where('statut', StatutTournee::Termine)->count() >= $affectation->tours_prevus) {
                $affectation->update(['statut' => StatutAffectation::Terminee]);
            }

            // Un tour marque en anomalie n'ouvre pas droit a la prime tant qu'il n'est pas verifie.
            if (! $anomalie) {
                $this->primes->crediterPrimeTour($tournee->refresh());
            }

            TourneeMiseAJour::dispatch($tournee->fresh(['affectation.bus', 'lieu', 'parcours']));

            return $tournee->refresh();
        });
    }

    /** Remonte une position GPS envoyee par l'app chauffeur pendant le trajet. */
    public function remonterPosition(
        Tournee $tournee,
        float $latitude,
        float $longitude,
        ?int $vitesse = null,
        ?int $cap = null,
    ): void {
        $this->enregistrerPosition($tournee, $latitude, $longitude, $vitesse, $cap);
    }

    /** Verifie que l'etape precedente a bien ete pointee (cycle sequentiel 3.2). */
    private function assurerSequence(Tournee $tournee, EtapePointage $etape): void
    {
        if ($tournee->statut === StatutTournee::Termine) {
            throw new PointageInvalideException('Ce tour est déjà terminé.', 'tour_termine');
        }

        if ($tournee->statut === StatutTournee::Annule) {
            throw new PointageInvalideException('Ce tour a été annulé.', 'tour_annule');
        }

        if ($tournee->pointages()->where('etape', $etape)->exists()) {
            throw new PointageInvalideException(
                "L’étape « {$etape->libelle()} » a déjà été pointée.",
                'etape_deja_pointee',
            );
        }

        $precedente = $etape->precedente();

        if ($precedente && ! $tournee->pointages()->where('etape', $precedente)->exists()) {
            throw new PointageInvalideException(
                "Vous devez d’abord pointer l’étape « {$precedente->libelle()} ».",
                'sequence_rompue',
                ['etape_attendue' => $precedente->value],
            );
        }
    }

    /**
     * Sens le plus plausible a l'heure ou le chauffeur demarre.
     *
     * Le matin, le bus ramasse vers le campus ; l'apres-midi, il repart du
     * campus. Le chauffeur peut toujours imposer un sens explicite depuis
     * l'application si la journee s'organise autrement.
     */
    private function parcoursDuMoment(Affectation $affectation): ?Parcours
    {
        $sens = now()->hour < 14 ? SensParcours::Aller : SensParcours::Retour;

        $parcours = $affectation->ligne->parcours()
            ->where('sens', $sens)
            ->where('actif', true)
            ->first();

        // Une ligne peut n'avoir qu'un seul sens defini : on le prend
        // plutot que de refuser le demarrage.
        return $parcours ?? $affectation->ligne->parcours()
            ->where('actif', true)
            ->first();
    }

    /** Applique le controle de zone geographique quand l'etape l'exige (regle 3.4). */
    private function assurerZone(Tournee $tournee, EtapePointage $etape, float $latitude, float $longitude): bool
    {
        if (! $etape->exigeControleGeographique()) {
            return true;
        }

        $lieu = $tournee->lieu;

        if (! $lieu) {
            return true;
        }

        $distance = $this->geo->distanceLieu($lieu, $latitude, $longitude);

        if ($distance > $lieu->rayon_validation_metres) {
            throw new PointageInvalideException(
                "Vous êtes à {$this->formater($distance)} de « {$lieu->nom} ». "
                    .'Rapprochez-vous pour valider cette étape.',
                'hors_zone',
                [
                    'distance_metres' => round($distance),
                    'rayon_metres' => $lieu->rayon_validation_metres,
                    'lieu' => $lieu->nom,
                ],
            );
        }

        return true;
    }

    private function formater(float $metres): string
    {
        return $metres >= 1000
            ? round($metres / 1000, 1).' km'
            : round($metres).' m';
    }

    private function enregistrerPointage(
        Tournee $tournee,
        EtapePointage $etape,
        float $latitude,
        float $longitude,
        bool $dansZone,
    ): Pointage {
        $distance = $tournee->arret
            ? (int) round($this->geo->distanceArret($tournee->arret, $latitude, $longitude))
            : null;

        return $tournee->pointages()->create([
            'etape' => $etape,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_metres' => $distance,
            'dans_zone' => $dansZone,
            'pointe_le' => now(),
        ]);
    }

    private function enregistrerPosition(
        Tournee $tournee,
        float $latitude,
        float $longitude,
        ?int $vitesse = null,
        ?int $cap = null,
    ): void {
        $position = $tournee->positions()->create([
            'bus_id' => $tournee->affectation->bus_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'vitesse_kmh' => $vitesse,
            'cap_degres' => $cap,
            'releve_le' => now(),
        ]);

        PositionBusMiseAJour::dispatch($position);
    }
}
