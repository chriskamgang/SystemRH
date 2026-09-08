<?php

namespace App\Services;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAbonnement;
use App\Events\AlerteDiffusee;
use App\Jobs\EnvoyerPush;
use App\Models\Abonnement;
use App\Models\Etudiant;
use App\Models\Lieu;
use App\Models\MissionSecours;
use App\Models\NotificationApp;
use App\Models\Panne;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Notifications d'information (3.1) et alertes de supervision (3.5).
 */
class NotificationService
{
    /** Previent les etudiants desservis que leur bus vient de quitter le depot. */
    public function notifierDepartBus(Tournee $tournee): void
    {
        $destination = $tournee->parcours?->lieuTerminus()?->nom
            ?? $tournee->lieu?->nom
            ?? 'sa destination';

        $this->envoyer(
            $this->etudiantsDesservis($tournee),
            'depart_bus',
            'Votre bus est en route',
            "Le bus {$tournee->affectation->bus->immatriculation} vient de partir vers « {$destination} ».",
            ['tournee_id' => $tournee->id, 'bus_id' => $tournee->affectation->bus_id],
        );
    }

    /** Previent les etudiants d'un retard ou d'un changement de vehicule. */
    public function notifierRetardOuChangement(Tournee $tournee, string $message): void
    {
        $this->envoyer(
            $this->etudiantsDesservis($tournee),
            'retard',
            'Information sur votre trajet',
            $message,
            ['tournee_id' => $tournee->id],
        );
    }

    /**
     * Etudiants concernes par un tour.
     *
     * Le parcours dessert plusieurs lieux : tous ceux qui y montent sont
     * prevenus, pas seulement ceux du premier arret.
     *
     * Seuls les porteurs d'un pass valide et non epuise sont alertes : une
     * information de trajet n'a de sens que pour qui peut monter a bord.
     */
    private function etudiantsDesservis(Tournee $tournee): Collection
    {
        $lieux = $tournee->parcours
            ? $tournee->parcours->etapes()->pluck('lieu_id')
            : collect([$tournee->lieu_id])->filter();

        if ($lieux->isEmpty()) {
            return collect();
        }

        return Etudiant::whereIn('lieu_ramassage_id', $lieux)
            ->whereHas('abonnements', fn ($q) => $q
                ->where('statut', StatutAbonnement::Actif)
                ->whereDate('date_debut', '<=', today())
                ->whereDate('date_fin', '>=', today())
                // Un forfait de jours ne compte pas les trajets : trajets_restants
                // vaut null et le pass reste valide jusqu'a sa date de fin.
                ->where(fn ($q) => $q
                    ->whereNull('trajets_restants')
                    ->orWhere('trajets_restants', '>', 0)))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();
    }

    /**
     * Previent les etudiants que le bus qu'ils attendent est en panne (3.1).
     *
     * Ils sont les premiers concernes : ils patientent a l'arret sans savoir
     * pourquoi le bus ne vient pas. L'alerte part donc des la declaration,
     * sans attendre qu'un secours soit affecte — l'attente informee vaut
     * mieux que le silence, et un second message suivra des que le bus de
     * remplacement sera connu.
     */
    public function notifierEtudiantsPanne(Panne $panne): void
    {
        $tournee = $panne->tournee;

        if (! $tournee) {
            return;
        }

        $this->envoyer(
            $this->etudiantsDesservis($tournee),
            'breakdown',
            'Incident sur votre trajet',
            "Le bus {$panne->bus->immatriculation} est immobilisé. "
                .'La régulation cherche un véhicule de remplacement.',
            ['panne_id' => $panne->id, 'tournee_id' => $tournee->id],
        );
    }

    /**
     * Rassure les etudiants une fois le bus de secours connu (3.1).
     *
     * C'est la seule information qui leur permet de decider s'ils attendent
     * ou s'ils partent a pied.
     */
    public function notifierSecoursEnRoute(MissionSecours $mission): void
    {
        $tournee = $mission->panne->tournee;

        if (! $tournee) {
            return;
        }

        $immatriculation = $mission->bus?->immatriculation;

        $this->envoyer(
            $this->etudiantsDesservis($tournee),
            'changement_vehicule',
            'Un autre bus arrive',
            $immatriculation
                ? "Le bus {$immatriculation} prend le relais et se dirige vers votre arrêt."
                : 'Un bus de remplacement se dirige vers votre arrêt.',
            [
                'mission_id' => $mission->id,
                'panne_id' => $mission->panne_id,
                'tournee_id' => $tournee->id,
            ],
        );
    }

    /**
     * Previent les etudiants d'un retard declare par le chauffeur (US-04).
     *
     * Le motif vient du chauffeur lui-meme : lui seul sait s'il est pris
     * dans un embouteillage ou retenu au depot.
     */
    public function notifierRetardDeclare(Tournee $tournee, int $minutes, ?string $motif = null): void
    {
        $message = "Votre bus a environ {$minutes} minutes de retard.";

        if (filled($motif)) {
            $message .= " Motif : {$motif}.";
        }

        $this->envoyer(
            $this->etudiantsDesservis($tournee),
            'retard',
            'Retard sur votre trajet',
            $message,
            [
                'tournee_id' => $tournee->id,
                'minutes_retard' => $minutes,
            ],
        );
    }

    /**
     * Rappelle a un etudiant que son pass arrive a echeance (3.1).
     *
     * Un pass qui expire en silence, c'est un renouvellement perdu et un
     * etudiant refuse a la montee sans comprendre pourquoi.
     */
    public function notifierPassBientotExpire(Abonnement $abonnement): void
    {
        $user = $abonnement->etudiant?->user;

        if (! $user) {
            return;
        }

        $jours = (int) today()->diffInDays($abonnement->date_fin, false);

        $echeance = match (true) {
            $jours <= 0 => 'expire aujourd’hui',
            $jours === 1 => 'expire demain',
            default => "expire dans {$jours} jours",
        };

        $this->envoyer(
            collect([$user]),
            'pass_expire',
            'Votre pass arrive à échéance',
            "Votre pass {$echeance}. Renouvelez-le pour continuer à monter à bord.",
            [
                'abonnement_id' => $abonnement->id,
                'date_fin' => $abonnement->date_fin?->toDateString(),
                'jours_restants' => $jours,
            ],
        );
    }

    /** Remonte une panne aux gestionnaires de flotte. */
    public function alerterGestionnairesPanne(Panne $panne): void
    {
        $gestionnaires = User::whereIn('role', [RoleUtilisateur::Gestionnaire, RoleUtilisateur::Admin])
            ->where('actif', true)
            ->get();

        $this->envoyer(
            $gestionnaires,
            'panne',
            'Panne déclarée',
            "Le bus {$panne->bus->immatriculation} est en panne"
                .($panne->passagers_immobilises ? " avec {$panne->passagers_immobilises} passagers immobilisés." : '.'),
            ['panne_id' => $panne->id, 'bus_id' => $panne->bus_id],
        );
    }

    /** Notifie le chauffeur retenu pour une mission de secours. */
    public function notifierMissionSecours(MissionSecours $mission): void
    {
        $user = $mission->chauffeur->user;

        if (! $user) {
            return;
        }

        $this->envoyer(
            collect([$user]),
            'mission_secours',
            'Mission de secours assignée',
            "Une mission de secours vous est affectée pour le bus {$mission->panne->bus->immatriculation}.",
            ['mission_id' => $mission->id, 'panne_id' => $mission->panne_id],
        );
    }

    /** @param Collection<int, User> $destinataires */
    /**
     * Previent les etudiants d'un arret que leur bus approche (CDC 3.1).
     *
     * Declenchee par la geofence serveur, a partir des positions remontees
     * par le chauffeur. L'idempotence est assuree en amont par
     * ApprocheArret : ce service ne fait qu'envoyer.
     *
     * Seuls les etudiants montant a CE lieu sont prevenus — les autres
     * arrets du parcours auront leur propre alerte quand le bus s'en
     * approchera a son tour.
     */
    public function notifierBusApproche(Tournee $tournee, Lieu $lieu, int $distanceMetres, ?int $minutes): void
    {
        $immatriculation = $tournee->affectation->bus->immatriculation;

        // Une estimation absente ne doit pas produire « dans 0 min » : on
        // se rabat alors sur la distance, qui est toujours connue ici.
        $delai = $minutes !== null && $minutes > 0
            ? "dans environ {$minutes} min"
            : "a moins de {$distanceMetres} m";

        $this->envoyer(
            $this->etudiantsDuLieu($lieu),
            'bus_approche',
            'Votre bus arrive',
            "Le bus {$immatriculation} approche de « {$lieu->nom} », {$delai}. Preparez-vous a monter.",
            [
                'tournee_id' => $tournee->id,
                'lieu_id' => $lieu->id,
                'bus_id' => $tournee->affectation->bus_id,
                'distance_metres' => $distanceMetres,
                'minutes_estimees' => $minutes,
            ],
        );
    }

    /**
     * Etudiants rattachés à un seul lieu de ramassage, pass valide.
     *
     * Meme filtre d'abonnement que etudiantsDesservis : prevenir qui ne
     * peut pas monter n'aurait pas de sens.
     */
    private function etudiantsDuLieu(Lieu $lieu): Collection
    {
        return Etudiant::where('lieu_ramassage_id', $lieu->id)
            ->whereHas('abonnements', fn ($q) => $q
                ->where('statut', StatutAbonnement::Actif)
                ->whereDate('date_debut', '<=', today())
                ->whereDate('date_fin', '>=', today())
                ->where(fn ($q) => $q
                    ->whereNull('trajets_restants')
                    ->orWhere('trajets_restants', '>', 0)))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();
    }

    private function envoyer(Collection $destinataires, string $type, string $titre, string $message, array $donnees = []): void
    {
        foreach ($destinataires as $user) {
            $notification = NotificationApp::create([
                'user_id' => $user->id,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
                'donnees' => $donnees,
            ]);

            AlerteDiffusee::dispatch($notification);

            // Le temps reel via Reverb ne touche que les applications
            // ouvertes ; le push FCM atteint aussi celles qui dorment.
            EnvoyerPush::dispatch($notification->id);
        }
    }
}
