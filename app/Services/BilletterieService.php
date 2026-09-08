<?php

namespace App\Services;

use App\Enums\StatutAbonnement;
use App\Enums\StatutTournee;
use App\Exceptions\BilletterieException;
use App\Models\Abonnement;
use App\Models\Etudiant;
use App\Models\Tournee;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Billettique : le pass de l'etudiant s'affiche en QR, le chauffeur le scanne
 * a la montee, et le scan consomme un trajet (3.1 / 3.2).
 *
 * Le QR ne porte aucune donnee secrete : un identifiant d'etudiant, un
 * horodatage et une signature HMAC. Il tourne toutes les 30 secondes, ce qui
 * rend inutile une capture d'ecran transmise a un camarade.
 */
class BilletterieService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    /** Duree de vie d'un jeton QR. Assez court pour interdire le partage. */
    public const VALIDITE_SECONDES = 30;

    /** Tolerance d'horloge acceptee de part et d'autre du creneau. */
    private const DERIVE_TOLEREE = 15;

    /** Jeton designant un abonnement precis. */
    private const VERSION = 'IB2';

    /** Ancien jeton, sans abonnement designe : encore lu, plus emis. */
    private const VERSION_HERITEE = 'IB1';

    /**
     * Jeton a encoder dans le QR affiche par l'application etudiante.
     * Forme : IB2.<etudiant_id>.<abonnement_id>.<creneau>.<signature>
     *
     * Le pass est nomme dans le jeton : l'etudiant qui en detient plusieurs
     * montre celui qu'il veut voir debite, et le scan ne peut plus se
     * tromper de titre.
     */
    public function genererJeton(Etudiant $etudiant, Abonnement $abonnement, ?int $creneau = null): string
    {
        $creneau ??= $this->creneauCourant();
        $charge = self::VERSION.'.'.$etudiant->id.'.'.$abonnement->id.'.'.$creneau;

        return $charge.'.'.$this->signer($charge);
    }

    /** Instant d'expiration du jeton courant, pour l'animation de l'app. */
    public function expiration(?int $creneau = null): Carbon
    {
        $creneau ??= $this->creneauCourant();

        return Carbon::createFromTimestamp(($creneau + 1) * self::VALIDITE_SECONDES);
    }

    /**
     * Verifie un jeton scanne et rend l'etudiant, avec le pass qu'il designe.
     *
     * @return array{etudiant: Etudiant, abonnement_id: int|null}
     *
     * @throws BilletterieException
     */
    public function lireJeton(string $jeton): array
    {
        $parts = explode('.', trim($jeton));
        $version = $parts[0] ?? '';

        // IB2 nomme le pass a debiter ; IB1 ne portait que l'etudiant.
        $format = match (true) {
            $version === self::VERSION && count($parts) === 5 => 'v2',
            $version === self::VERSION_HERITEE && count($parts) === 4 => 'v1',
            default => null,
        };

        if ($format === null) {
            throw new BilletterieException(
                'Ce QR code n’est pas un pass INSAM BUS.',
                'jeton_illisible',
            );
        }

        if ($format === 'v2') {
            [, $etudiantId, $abonnementId, $creneau, $signature] = $parts;
            $charge = self::VERSION.'.'.$etudiantId.'.'.$abonnementId.'.'.$creneau;
        } else {
            [, $etudiantId, $creneau, $signature] = $parts;
            $abonnementId = null;
            $charge = self::VERSION_HERITEE.'.'.$etudiantId.'.'.$creneau;
        }

        if (! hash_equals($this->signer($charge), $signature)) {
            throw new BilletterieException(
                'Ce QR code a été falsifié.',
                'signature_invalide',
            );
        }

        // Le creneau precedent reste accepte : le scan ne doit pas echouer
        // parce que le code a tourne pendant que le chauffeur visait.
        $courant = $this->creneauCourant();
        $tolerance = (int) ceil(self::DERIVE_TOLEREE / self::VALIDITE_SECONDES) + 1;

        if (abs($courant - (int) $creneau) > $tolerance) {
            throw new BilletterieException(
                'Ce QR code a expiré. Demandez à l’étudiant de rafraîchir son pass.',
                'jeton_expire',
            );
        }

        $etudiant = Etudiant::with('user')->find($etudiantId);

        if (! $etudiant) {
            throw new BilletterieException(
                'Aucun étudiant ne correspond à ce pass.',
                'etudiant_inconnu',
            );
        }

        return [
            'etudiant' => $etudiant,
            'abonnement_id' => $abonnementId === null ? null : (int) $abonnementId,
        ];
    }

    /**
     * Scan complet : lit le QR, controle le pass et consomme un trajet.
     *
     * L'operation est idempotente — rescanner le meme etudiant sur le meme
     * tour ne debite pas une seconde fois.
     *
     * @throws BilletterieException
     */
    public function validerParJeton(string $jeton, Tournee $tournee, ?User $agent = null): array
    {
        $lu = $this->lireJeton($jeton);

        return $this->valider(
            $lu['etudiant'],
            $tournee,
            $agent,
            'qr',
            $lu['abonnement_id'],
        );
    }

    /**
     * Consomme un trajet du pass de l'etudiant pour ce tour.
     *
     * @return array{trajet: Trajet, abonnement: Abonnement, deja_valide: bool}
     *
     * @throws BilletterieException
     */
    public function valider(
        Etudiant $etudiant,
        Tournee $tournee,
        ?User $agent = null,
        string $mode = 'qr',
        ?int $abonnementDesigne = null,
    ): array {
        if (in_array($tournee->statut, [StatutTournee::Termine, StatutTournee::Annule], true)) {
            throw new BilletterieException(
                'Ce tour est clôturé : plus aucun embarquement ne peut y être rattaché.',
                'tournee_close',
            );
        }

        // Un scan deja enregistre rend la meme reponse sans rien redebiter.
        $existant = Trajet::where('etudiant_id', $etudiant->id)
            ->where('tournee_id', $tournee->id)
            ->first();

        if ($existant) {
            return [
                'trajet' => $existant,
                'abonnement' => $existant->abonnement,
                'deja_valide' => true,
            ];
        }

        $abonnement = $this->passUtilisable($etudiant, $abonnementDesigne);

        return DB::transaction(function () use ($etudiant, $tournee, $abonnement, $agent, $mode) {
            $trajet = Trajet::create([
                'etudiant_id' => $etudiant->id,
                'tournee_id' => $tournee->id,
                'abonnement_id' => $abonnement->id,
                'embarque_le' => now(),
                'mode_validation' => $mode,
                'valide_par' => $agent?->id,
            ]);

            // Un pass au forfait de jours ne porte pas de compteur de trajets.
            if ($abonnement->trajets_restants !== null) {
                $abonnement->decrement('trajets_restants');
            }

            // Le pass epuise sort du circuit : il ne doit plus donner droit
            // ni a un embarquement, ni aux alertes de trajet.
            $abonnement->refresh();

            if ($abonnement->trajets_restants !== null && $abonnement->trajets_restants <= 0) {
                $abonnement->update(['statut' => StatutAbonnement::Expire]);
            }

            $tournee->increment('embarquements_valides');
            $this->recalculerEcart($tournee->refresh());

            // La recette du trajet revient au chauffeur qui l'assure : elle
            // tombe dans son wallet des le scan, sans attendre de cloture.
            $this->wallets->crediterTrajet(
                $trajet,
                $agent?->chauffeur ?? $tournee->affectation->chauffeur,
            );

            return [
                'trajet' => $trajet,
                'abonnement' => $abonnement->refresh(),
                'deja_valide' => false,
            ];
        });
    }

    /**
     * Pass a debiter, ou le refus a afficher au chauffeur.
     *
     * L'etudiant qui detient plusieurs pass choisit celui qu'il presente :
     * le QR le designe, et c'est celui-la qui est debite. A defaut de
     * designation (ancien QR), on prend celui qui expire le plus tot, pour
     * ne pas laisser perimer un titre paye.
     *
     * @throws BilletterieException
     */
    public function passUtilisable(Etudiant $etudiant, ?int $abonnementDesigne = null): Abonnement
    {
        if ($abonnementDesigne !== null) {
            return $this->passDesigne($etudiant, $abonnementDesigne);
        }

        $abonnement = $this->passUtilisables($etudiant)->first();

        if ($abonnement) {
            return $abonnement;
        }

        // Un abonnement paye mais non encore actif merite un message distinct
        // d'une absence totale de pass : l'etudiant a fait sa part.
        $enAttente = $etudiant->abonnements()
            ->where('statut', StatutAbonnement::EnAttente)
            ->latest('id')
            ->first();

        if ($enAttente) {
            throw new BilletterieException(
                'Le pass de cet étudiant est en attente de paiement.',
                'pass_impaye',
                ['abonnement_id' => $enAttente->id],
            );
        }

        if ($abonnement && $abonnement->trajets_restants !== null && $abonnement->trajets_restants <= 0) {
            throw new BilletterieException(
                'Le pass de cet étudiant n’a plus de trajet disponible.',
                'pass_epuise',
            );
        }

        throw new BilletterieException(
            'Cet étudiant n’a aucun pass valide.',
            'pass_absent',
        );
    }

    /**
     * Pass valides aujourd'hui, du plus proche de l'echeance au plus lointain.
     *
     * @return \Illuminate\Support\Collection<int, Abonnement>
     */
    public function passUtilisables(Etudiant $etudiant): \Illuminate\Support\Collection
    {
        return $etudiant->abonnements()
            ->with('tarif')
            ->where('statut', StatutAbonnement::Actif)
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today())
            ->where(fn ($q) => $q->whereNull('trajets_restants')->orWhere('trajets_restants', '>', 0))
            ->orderBy('date_fin')
            ->get();
    }

    /**
     * Le pass que l'etudiant a choisi de presenter.
     *
     * @throws BilletterieException
     */
    private function passDesigne(Etudiant $etudiant, int $abonnementId): Abonnement
    {
        $abonnement = $etudiant->abonnements()->with('tarif')->find($abonnementId);

        if (! $abonnement) {
            throw new BilletterieException(
                'Ce pass n’appartient pas à cet étudiant.',
                'pass_etranger',
            );
        }

        if ($abonnement->estUtilisable()) {
            return $abonnement;
        }

        // Le refus nomme la cause : l'etudiant a peut-etre simplement
        // presente le mauvais titre parmi ceux qu'il detient.
        throw new BilletterieException(
            match (true) {
                $abonnement->statut === StatutAbonnement::EnAttente
                    => 'Ce pass est en attente de paiement.',
                $abonnement->trajets_restants !== null && $abonnement->trajets_restants <= 0
                    => 'Ce pass n’a plus de trajet disponible.',
                $abonnement->date_fin->lt(today()) => 'Ce pass a expiré.',
                $abonnement->date_debut->gt(today()) => 'Ce pass n’est pas encore valable.',
                default => 'Ce pass n’est pas utilisable.',
            },
            match (true) {
                $abonnement->statut === StatutAbonnement::EnAttente => 'pass_impaye',
                $abonnement->trajets_restants !== null && $abonnement->trajets_restants <= 0 => 'pass_epuise',
                default => 'pass_invalide',
            },
            ['abonnement_id' => $abonnement->id],
        );
    }

    /**
     * Ecart entre l'effectif compte et les tickets scannes : ce sont les
     * passagers montes sans ticket, que le chauffeur devra justifier.
     */
    public function recalculerEcart(Tournee $tournee): int
    {
        $valides = $tournee->trajets()->count();
        $compte = $tournee->effectif_embarque;

        $ecart = $compte === null ? 0 : max(0, $compte - $valides);

        $tournee->update([
            'embarquements_valides' => $valides,
            'passagers_sans_ticket' => $ecart,
        ]);

        return $ecart;
    }

    /** Numero du creneau de 30 s en cours. */
    private function creneauCourant(): int
    {
        return intdiv(now()->getTimestamp(), self::VALIDITE_SECONDES);
    }

    private function signer(string $charge): string
    {
        return substr(hash_hmac('sha256', $charge, config('app.key')), 0, 32);
    }
}
