<?php

namespace App\Services\Kpay;

use App\Enums\StatutAbonnement;
use App\Exceptions\KpayException;
use App\Models\Abonnement;
use App\Models\Chauffeur;
use App\Models\CloturePaie;
use App\Models\RetraitChauffeur;
use App\Models\TransactionKpay;
use App\Services\AbonnementService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestration des flux d'argent INSAM BUS :
 *  - deposit : encaissement de l'abonnement etudiant (3.1) ;
 *  - payout  : versement des primes chauffeurs a la cloture de paie (3.3 / 3.5).
 */
class KpayService
{
    public function __construct(
        private readonly KpayClient $client,
        private readonly KpayConfig $config,
    ) {}

    /**
     * Encaisse un abonnement : declenche un push USSD sur le telephone de l'etudiant.
     */
    public function encaisserAbonnement(Abonnement $abonnement, ?string $telephone = null, ?string $provider = null): TransactionKpay
    {
        // Un pass actif peut porter une recharge : ce sont ses trajets neufs
        // qui attendent d'etre regles, pas le pass lui-meme.
        $montant = $abonnement->montantARegler();

        if ($montant <= 0) {
            throw new KpayException('Cet abonnement n’est pas en attente de paiement.');
        }

        $etudiant = $abonnement->etudiant()->with('user')->first();
        $telephone = $this->normaliserTelephone($telephone ?? $etudiant->user->telephone_bus);

        $transaction = $this->creerTransaction(
            type: 'deposit',
            montant: $montant,
            telephone: $telephone,
            provider: $provider ?? $this->config->providerDefaut(),
            payable: $abonnement,
            description: "Abonnement {$abonnement->tarif->libelle} — INSAM BUS",
            metadonnees: [
                'abonnement_id' => $abonnement->id,
                'etudiant_id' => $etudiant->id,
                'matricule' => $etudiant->matricule_insam,
            ],
        );

        try {
            $reponse = $this->client->initierPaiement([
                'amount' => $transaction->montant,
                'provider' => $transaction->provider,
                'phoneNumber' => $transaction->telephone,
                'externalId' => $transaction->external_id,
                'description' => $transaction->description,
                'customerName' => $etudiant->user->nom_complet,
                'customerEmail' => $etudiant->user->email,
                'metadata' => $transaction->metadonnees,
            ]);
        } catch (KpayException $e) {
            $transaction->update([
                'statut' => 'FAILED',
                'motif_echec' => $e->getMessage(),
                'echouee_le' => now(),
            ]);

            throw $e;
        }

        return $this->appliquerReponse($transaction, $reponse);
    }

    /**
     * Verse a un chauffeur le net de sa cloture de paie mensuelle.
     * Le montant part du wallet KPay vers son compte Mobile Money.
     */
    public function verserPrimes(CloturePaie $cloture, ?string $telephone = null, ?string $provider = null): TransactionKpay
    {
        if ($cloture->net_a_payer_fcfa <= 0) {
            throw new KpayException('Le net à payer de cette période est nul ou négatif.');
        }

        $minimum = $this->config->montantMinimumPayout();

        if ($cloture->net_a_payer_fcfa < $minimum) {
            throw new KpayException(
                "Le net à payer ({$cloture->net_a_payer_fcfa} FCFA) est inférieur au minimum de retrait ({$minimum} FCFA).",
            );
        }

        // Un seul versement abouti ou en cours par cloture.
        $existante = TransactionKpay::where('payable_type', CloturePaie::class)
            ->where('payable_id', $cloture->id)
            ->whereIn('statut', ['PENDING', 'PROCESSING', 'COMPLETED'])
            ->first();

        if ($existante) {
            throw new KpayException(
                'Un versement est déjà en cours ou abouti pour cette période.',
            );
        }

        $chauffeur = $cloture->chauffeur()->with('user')->first();
        $telephone = $this->normaliserTelephone($telephone ?? $chauffeur->user->telephone_bus);

        $transaction = $this->creerTransaction(
            type: 'payout',
            montant: $cloture->net_a_payer_fcfa,
            telephone: $telephone,
            provider: $provider ?? $this->config->providerDefaut(),
            payable: $cloture,
            description: "Primes INSAM BUS — période {$cloture->periode}",
            metadonnees: [
                'cloture_id' => $cloture->id,
                'chauffeur_id' => $chauffeur->id,
                'matricule' => $chauffeur->matricule,
                'periode' => $cloture->periode,
            ],
        );

        try {
            $reponse = $this->client->initierRetrait([
                'amount' => $transaction->montant,
                'provider' => $transaction->provider,
                'phoneNumber' => $transaction->telephone,
                'externalId' => $transaction->external_id,
                'description' => $transaction->description,
                'metadata' => $transaction->metadonnees,
            ]);
        } catch (KpayException $e) {
            $transaction->update([
                'statut' => 'FAILED',
                'motif_echec' => $e->getMessage(),
                'echouee_le' => now(),
            ]);

            throw $e;
        }

        return $this->appliquerReponse($transaction, $reponse);
    }

    /**
     * Verse au chauffeur le montant qu'il retire de son wallet.
     *
     * Le solde a deja ete immobilise cote wallet : cet appel ne fait que
     * demander le transfert a l'operateur. L'issue arrive par webhook.
     */
    public function verserRetrait(RetraitChauffeur $retrait, ?string $provider = null): TransactionKpay
    {
        $chauffeur = $retrait->chauffeur()->with('user')->first();
        $telephone = $this->normaliserTelephone($retrait->telephone);

        $transaction = $this->creerTransaction(
            type: 'payout',
            montant: $retrait->montant_fcfa,
            telephone: $telephone,
            provider: $retrait->provider ?? $provider ?? $this->config->providerDefaut(),
            payable: $retrait,
            description: 'Retrait wallet INSAM BUS',
            metadonnees: [
                'retrait_id' => $retrait->id,
                'chauffeur_id' => $chauffeur->id,
                'matricule' => $chauffeur->matricule,
            ],
        );

        $retrait->update([
            'statut' => 'en_cours',
            'transaction_kpay_id' => $transaction->id,
        ]);

        try {
            $reponse = $this->client->initierRetrait([
                'amount' => $transaction->montant,
                'provider' => $transaction->provider,
                'phoneNumber' => $transaction->telephone,
                'externalId' => $transaction->external_id,
                'description' => $transaction->description,
                'metadata' => $transaction->metadonnees,
            ]);
        } catch (KpayException $e) {
            $transaction->update([
                'statut' => 'FAILED',
                'motif_echec' => $e->getMessage(),
                'echouee_le' => now(),
            ]);

            throw $e;
        }

        return $this->appliquerReponse($transaction, $reponse);
    }

    /** Interroge KPay pour rafraichir une transaction non terminale (complement du webhook). */
    public function rafraichir(TransactionKpay $transaction): TransactionKpay
    {
        if ($transaction->estTerminale() || blank($transaction->kpay_id)) {
            return $transaction;
        }

        $reponse = $this->client->consulterPaiement($transaction->kpay_id);

        return $this->appliquerReponse($transaction, $reponse);
    }

    /**
     * Applique un changement de statut a la transaction et repercute
     * la consequence metier (abonnement actif, primes marquees payees).
     */
    public function appliquerStatut(TransactionKpay $transaction, string $statut, array $charge = []): TransactionKpay
    {
        return DB::transaction(function () use ($transaction, $statut, $charge) {
            $ancien = $transaction->statut;

            $transaction->update([
                'statut' => $statut,
                'motif_echec' => $charge['failureReason'] ?? $transaction->motif_echec,
                'completee_le' => $statut === 'COMPLETED'
                    ? ($charge['completedAt'] ?? now())
                    : $transaction->completee_le,
                'echouee_le' => in_array($statut, ['FAILED', 'CANCELLED'], true)
                    ? ($charge['failedAt'] ?? now())
                    : $transaction->echouee_le,
            ]);

            // Idempotence : la consequence metier n'est jouee qu'au passage en COMPLETED.
            if ($statut === 'COMPLETED' && $ancien !== 'COMPLETED') {
                $this->appliquerReussite($transaction->refresh());
            }

            // Un versement qui n'aboutit pas doit rendre au chauffeur ce qui
            // avait quitte son solde : sans cela, l'argent resterait immobilise.
            if (in_array($statut, ['FAILED', 'CANCELLED'], true) && ! in_array($ancien, ['FAILED', 'CANCELLED'], true)) {
                $this->appliquerEchec($transaction->refresh(), $charge);
            }

            return $transaction->refresh();
        });
    }

    /**
     * Remboursement abouti : la contrepartie cesse d'ouvrir des droits.
     *
     * Un abonnement rembourse redevient inutilisable, sans quoi l'etudiant
     * garderait un pass valide pour un trajet qui lui a ete restitue.
     */
    public function appliquerRemboursement(TransactionKpay $transaction, array $charge = []): TransactionKpay
    {
        return DB::transaction(function () use ($transaction, $charge) {
            // Un webhook peut arriver deux fois : ne rien rejouer.
            if ($transaction->statut === 'REFUNDED') {
                return $transaction;
            }

            $transaction->update([
                'statut' => 'REFUNDED',
                'motif_echec' => $charge['reason'] ?? $transaction->motif_echec,
            ]);

            $cible = $transaction->payable;

            if ($cible instanceof Abonnement) {
                $cible->update([
                    'statut' => StatutAbonnement::Annule,
                    'trajets_restants' => 0,
                ]);
            }

            return $transaction->refresh();
        });
    }

    /** Consequence metier d'un encaissement ou d'un versement reussi. */
    private function appliquerReussite(TransactionKpay $transaction): void
    {
        $cible = $transaction->payable;

        if ($cible instanceof Abonnement) {
            // L'abonnement devient utilisable des l'encaissement confirme.
            // La regle vit dans AbonnementService, qui sait aussi crediter
            // les trajets d'une recharge : la dupliquer ici les perdrait.
            app(AbonnementService::class)->confirmerPaiement($cible, $transaction->reference);

            return;
        }

        if ($cible instanceof CloturePaie) {
            $cible->update(['statut' => 'payee']);

            return;
        }

        if ($cible instanceof RetraitChauffeur) {
            // Les fonds sont partis : la somme immobilisee quitte le wallet.
            app(WalletService::class)->confirmerRetrait($cible);
        }
    }

    /** Consequence metier d'un versement qui n'a pas abouti. */
    private function appliquerEchec(TransactionKpay $transaction, array $charge = []): void
    {
        $cible = $transaction->payable;

        if ($cible instanceof RetraitChauffeur) {
            app(WalletService::class)->echouerRetrait(
                $cible,
                $charge['failureReason'] ?? $transaction->motif_echec,
            );
        }
    }

    private function creerTransaction(
        string $type,
        int $montant,
        string $telephone,
        string $provider,
        Model $payable,
        string $description,
        array $metadonnees = [],
    ): TransactionKpay {
        return TransactionKpay::create([
            'type' => $type,
            // Prefixe lisible pour la reconciliation cote KPay.
            'external_id' => strtoupper($type === 'payout' ? 'IB-PAY-' : 'IB-SUB-').Str::upper(Str::random(10)),
            'statut' => 'PENDING',
            'montant' => $montant,
            'devise' => $this->config->devise(),
            'provider' => $provider,
            'telephone' => $telephone,
            'est_test' => $this->config->estModeTest(),
            'payable_type' => $payable::class,
            'payable_id' => $payable->getKey(),
            'description' => $description,
            'metadonnees' => $metadonnees,
        ]);
    }

    /** Reporte la reponse KPay sur la transaction locale. */
    private function appliquerReponse(TransactionKpay $transaction, array $reponse): TransactionKpay
    {
        $transaction->update([
            'kpay_id' => $reponse['id'] ?? $transaction->kpay_id,
            'reference' => $reponse['reference'] ?? $transaction->reference,
            'provider_reference' => $reponse['providerReference'] ?? $transaction->provider_reference,
            'montant_net' => $reponse['netAmount'] ?? $transaction->montant_net,
            'frais' => $reponse['feeAmount'] ?? $transaction->frais,
            'devise' => $reponse['currency'] ?? $transaction->devise,
            'provider' => $reponse['provider'] ?? $transaction->provider,
            'pays' => $reponse['country'] ?? $transaction->pays,
            'est_test' => $reponse['isTest'] ?? $transaction->est_test,
            'reponse_brute' => $reponse,
        ]);

        $statut = $reponse['status'] ?? null;

        if ($statut && $statut !== $transaction->statut) {
            return $this->appliquerStatut($transaction->refresh(), $statut, $reponse);
        }

        return $transaction->refresh();
    }

    /** Format international attendu par KPay : sans +, sans 0 initial. */
    private function normaliserTelephone(string $telephone): string
    {
        return ltrim(preg_replace('/[^0-9]/', '', $telephone), '0');
    }
}
