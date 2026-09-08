<?php

namespace App\Services;

use App\Exceptions\WalletException;
use App\Models\Abonnement;
use App\Models\Chauffeur;
use App\Models\MouvementWallet;
use App\Models\RetraitChauffeur;
use App\Models\Trajet;
use App\Models\WalletChauffeur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Wallet du chauffeur : recette encaissee au fil des scans, retirable a tout
 * moment sans attendre une cloture mensuelle.
 *
 * La valeur d'un trajet est le prix du pass divise par le nombre de trajets
 * qu'il ouvre : un pass semaine a 2000 FCFA pour 10 trajets reverse 200 FCFA
 * a chaque montee, si bien que le total reverse egale exactement ce que
 * l'etudiant a paye.
 */
class WalletService
{
    /** Montant minimal d'un retrait, aligne sur le minimum KPay en zone Cameroun. */
    public const RETRAIT_MINIMUM = 100;

    /** Wallet du chauffeur, cree au premier acces. */
    public function wallet(Chauffeur $chauffeur): WalletChauffeur
    {
        return WalletChauffeur::firstOrCreate(['chauffeur_id' => $chauffeur->id]);
    }

    /**
     * Valeur reversee pour un trajet consomme sur ce pass.
     *
     * Le calcul retient le nombre de trajets ouverts a la souscription, non
     * ceux qui restent : sinon la valeur d'un trajet grimperait a mesure que
     * le pass se vide.
     */
    public function valeurTrajet(Abonnement $abonnement): int
    {
        $total = $abonnement->tarif?->trajetsTotal() ?? 0;

        if ($total <= 0) {
            return 0;
        }

        // Division entiere : le reliquat des arrondis reste a l'application
        // plutot que de reverser plus que le prix paye.
        return intdiv($abonnement->montant_paye_fcfa, $total);
    }

    /**
     * Credite le chauffeur pour un trajet qu'il vient de valider.
     *
     * Sans chauffeur identifiable — un scan fait depuis le back-office, par
     * exemple — rien n'est credite : l'argent n'aurait pas de destinataire.
     */
    public function crediterTrajet(Trajet $trajet, ?Chauffeur $chauffeur): ?MouvementWallet
    {
        if (! $chauffeur) {
            return null;
        }

        $abonnement = $trajet->abonnement;

        if (! $abonnement) {
            return null;
        }

        $montant = $this->valeurTrajet($abonnement);

        if ($montant <= 0) {
            return null;
        }

        return $this->crediter(
            $chauffeur,
            $montant,
            'Trajet — '.($abonnement->tarif?->libelle ?? 'pass étudiant'),
            'trajet',
            $trajet,
        );
    }

    /** Ajoute une somme au wallet et journalise le mouvement. */
    public function crediter(
        Chauffeur $chauffeur,
        int $montant,
        string $libelle,
        string $type = 'trajet',
        ?Model $origine = null,
    ): MouvementWallet {
        if ($montant <= 0) {
            throw new WalletException('Le montant à créditer doit être positif.');
        }

        return DB::transaction(function () use ($chauffeur, $montant, $libelle, $type, $origine) {
            // Verrou de ligne : deux scans simultanes ne doivent pas lire le
            // meme solde et en ecraser un. Le wallet est cree avant d'etre
            // verrouille — on ne verrouille pas une ligne inexistante.
            $wallet = $this->walletVerrouille($chauffeur);

            $wallet->increment('solde_fcfa', $montant);
            $wallet->increment('total_percu_fcfa', $montant);

            return $this->journaliser($wallet->refresh(), $type, $montant, $libelle, $origine);
        });
    }

    /**
     * Demande de retrait : la somme quitte le solde et reste immobilisee
     * jusqu'a l'issue de l'operation.
     *
     * @throws WalletException
     */
    public function demanderRetrait(
        Chauffeur $chauffeur,
        int $montant,
        string $telephone,
        ?string $provider = null,
    ): RetraitChauffeur {
        if ($montant < self::RETRAIT_MINIMUM) {
            throw new WalletException(
                'Le retrait minimum est de '.self::RETRAIT_MINIMUM.' FCFA.',
                'montant_insuffisant',
            );
        }

        return DB::transaction(function () use ($chauffeur, $montant, $telephone, $provider) {
            $wallet = $this->walletVerrouille($chauffeur);

            if ($wallet->solde_fcfa < $montant) {
                throw new WalletException(
                    'Solde insuffisant : vous disposez de '.$wallet->solde_fcfa.' FCFA.',
                    'solde_insuffisant',
                    ['disponible' => $wallet->solde_fcfa],
                );
            }

            // Un seul retrait a la fois : deux demandes concurrentes
            // videraient le wallet deux fois avant leur confirmation.
            $enCours = RetraitChauffeur::where('wallet_id', $wallet->id)
                ->whereIn('statut', ['en_attente', 'en_cours'])
                ->exists();

            if ($enCours) {
                throw new WalletException(
                    'Un retrait est déjà en cours. Attendez son issue avant d’en demander un autre.',
                    'retrait_en_cours',
                );
            }

            $wallet->decrement('solde_fcfa', $montant);
            $wallet->increment('solde_reserve_fcfa', $montant);

            $retrait = RetraitChauffeur::create([
                'wallet_id' => $wallet->id,
                'chauffeur_id' => $chauffeur->id,
                'montant_fcfa' => $montant,
                'telephone' => $telephone,
                'provider' => $provider,
                'statut' => 'en_attente',
                'demande_le' => now(),
            ]);

            $this->journaliser(
                $wallet->refresh(),
                'retrait',
                -$montant,
                'Retrait vers '.$telephone,
                $retrait,
            );

            return $retrait;
        });
    }

    /** Le retrait a abouti : la somme immobilisee quitte definitivement le wallet. */
    public function confirmerRetrait(RetraitChauffeur $retrait): RetraitChauffeur
    {
        return DB::transaction(function () use ($retrait) {
            if ($retrait->estTerminal()) {
                return $retrait;
            }

            $wallet = WalletChauffeur::whereKey($retrait->wallet_id)->lockForUpdate()->first();

            $wallet->decrement('solde_reserve_fcfa', $retrait->montant_fcfa);
            $wallet->increment('total_retire_fcfa', $retrait->montant_fcfa);

            $retrait->update(['statut' => 'paye', 'traite_le' => now()]);

            return $retrait->refresh();
        });
    }

    /** Le retrait a echoue : la somme immobilisee retourne au solde. */
    public function echouerRetrait(RetraitChauffeur $retrait, ?string $motif = null): RetraitChauffeur
    {
        return DB::transaction(function () use ($retrait, $motif) {
            if ($retrait->estTerminal()) {
                return $retrait;
            }

            $wallet = WalletChauffeur::whereKey($retrait->wallet_id)->lockForUpdate()->first();

            $wallet->decrement('solde_reserve_fcfa', $retrait->montant_fcfa);
            $wallet->increment('solde_fcfa', $retrait->montant_fcfa);

            $retrait->update([
                'statut' => 'echoue',
                'motif_echec' => $motif,
                'traite_le' => now(),
            ]);

            $this->journaliser(
                $wallet->refresh(),
                'retrait_annule',
                $retrait->montant_fcfa,
                'Retrait non abouti, somme restituée',
                $retrait,
            );

            return $retrait->refresh();
        });
    }

    /** Wallet du chauffeur, cree si besoin puis verrouille pour ecriture. */
    private function walletVerrouille(Chauffeur $chauffeur): WalletChauffeur
    {
        $this->wallet($chauffeur);

        return WalletChauffeur::where('chauffeur_id', $chauffeur->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function journaliser(
        WalletChauffeur $wallet,
        string $type,
        int $montant,
        string $libelle,
        ?Model $origine = null,
    ): MouvementWallet {
        return MouvementWallet::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'montant_fcfa' => $montant,
            'libelle' => $libelle,
            'origine_type' => $origine?->getMorphClass(),
            'origine_id' => $origine?->getKey(),
            'solde_apres_fcfa' => $wallet->solde_fcfa,
        ]);
    }
}
