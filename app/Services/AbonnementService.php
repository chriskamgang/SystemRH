<?php

namespace App\Services;

use App\Enums\StatutAbonnement;
use App\Models\Abonnement;
use App\Models\Etudiant;
use App\Models\Tarif;
use App\Models\TransactionKpay;
use App\Models\Tournee;
use App\Models\Trajet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Abonnements et tarification incitative (section 3.1).
 * Ticket unitaire, ou pass semaine lundi-vendredi a tarif preferentiel.
 */
class AbonnementService
{
    public const TICKET_UNITAIRE = 'ticket_unitaire';
    public const PASS_SEMAINE = 'pass_semaine';

    /** Souscrit un abonnement ; la periode de validite decoule du tarif choisi. */
    public function souscrire(
        Etudiant $etudiant,
        Tarif $tarif,
        ?CarbonImmutable $debut = null,
        ?string $moyenPaiement = null,
        ?string $reference = null,
    ): Abonnement {
        $debut ??= CarbonImmutable::today();

        // Un pass semaine demarre toujours au lundi de la semaine visee (lundi -> vendredi).
        if ($tarif->code === self::PASS_SEMAINE) {
            $debut = $debut->startOfWeek(CarbonImmutable::MONDAY);
            $fin = $debut->addDays(4);
        } else {
            $fin = $debut->addDays(max(0, $tarif->jours_couverts - 1));
        }

        // Une souscription restee en attente n'a rien coute : c'est la trace
        // d'un achat interrompu. La reconduire bloquerait l'etudiant sur un
        // essai rate, donc on l'abandonne au profit du nouveau — mais
        // seulement de meme nature : un pass semaine impaye ne doit pas
        // disparaitre parce qu'un ticket a l'unite vient d'etre pris.
        $this->abandonnerSouscriptionsInachevees($etudiant, $tarif, $debut, $fin);

        // Deux pass de meme nature sur la meme periode n'en font qu'un : les
        // trajets s'additionnent sur le titre existant plutot que d'ouvrir un
        // doublon que l'etudiant devrait choisir a la montee.
        $cumulable = $this->passCumulable($etudiant, $tarif, $debut, $fin);

        if ($cumulable) {
            return $this->cumuler($cumulable, $tarif);
        }

        return Abonnement::create([
            'etudiant_id' => $etudiant->id,
            'tarif_id' => $tarif->id,
            'date_debut' => $debut->toDateString(),
            'date_fin' => $fin->toDateString(),
            'montant_paye_fcfa' => $tarif->montant_fcfa * $tarif->jours_couverts,
            'trajets_restants' => $tarif->trajetsTotal(),
            'statut' => StatutAbonnement::EnAttente,
            'moyen_paiement' => $moyenPaiement,
            'reference_paiement' => $reference,
        ]);
    }

    /**
     * Abandonne les souscriptions restees en attente sur la periode visee.
     *
     * Un paiement peut etre en cours d'aboutissement chez KPay au moment ou
     * l'etudiant resouscrit : ceux-la sont epargnes, sans quoi on annulerait
     * un abonnement que l'operateur s'apprete a confirmer.
     *
     * @return int Nombre de souscriptions abandonnees.
     */
    private function abandonnerSouscriptionsInachevees(
        Etudiant $etudiant,
        Tarif $tarif,
        CarbonImmutable $debut,
        CarbonImmutable $fin,
    ): int {
        $inachevees = $etudiant->abonnements()
            ->where('statut', StatutAbonnement::EnAttente)
            ->where('tarif_id', $tarif->id)
            ->whereDate('date_fin', '>=', $debut->toDateString())
            ->whereDate('date_debut', '<=', $fin->toDateString())
            ->get()
            ->reject(fn (Abonnement $a) => $this->paiementEnCours($a));

        foreach ($inachevees as $abonnement) {
            $abonnement->update(['statut' => StatutAbonnement::Annule]);
        }

        return $inachevees->count();
    }

    /** Un encaissement non terminal court-il encore sur cet abonnement ? */
    private function paiementEnCours(Abonnement $abonnement): bool
    {
        return TransactionKpay::where('payable_type', Abonnement::class)
            ->where('payable_id', $abonnement->id)
            ->whereIn('statut', ['PENDING', 'PROCESSING', 'COMPLETED'])
            ->exists();
    }

    /**
     * Pass de meme nature deja paye et couvrant la meme periode, s'il existe.
     *
     * Seul un pass paye se prete au cumul : un pass en attente vient d'etre
     * abandonne, et un pass epuise ou expire n'a plus a etre rallonge.
     */
    private function passCumulable(
        Etudiant $etudiant,
        Tarif $tarif,
        CarbonImmutable $debut,
        CarbonImmutable $fin,
    ): ?Abonnement {
        return $etudiant->abonnements()
            ->where('statut', StatutAbonnement::Actif)
            ->where('tarif_id', $tarif->id)
            ->whereDate('date_debut', '<=', $fin->toDateString())
            ->whereDate('date_fin', '>=', $debut->toDateString())
            ->latest('id')
            ->first();
    }

    /**
     * Recharge un pass existant de meme nature.
     *
     * Les trajets ajoutes attendent leur reglement a part : le pass reste
     * actif, donc utilisable pour ce que l'etudiant a deja paye, et les
     * trajets en attente ne le rejoignent qu'au paiement confirme.
     */
    private function cumuler(Abonnement $abonnement, Tarif $tarif): Abonnement
    {
        $abonnement->update([
            'trajets_en_attente' => $abonnement->trajets_en_attente + $tarif->trajetsTotal(),
            'montant_du_fcfa' => $abonnement->montant_du_fcfa + $tarif->montant_fcfa * $tarif->jours_couverts,
        ]);

        return $abonnement->refresh();
    }

    /** Confirme le paiement et active l'abonnement. */
    public function confirmerPaiement(Abonnement $abonnement, ?string $reference = null): Abonnement
    {
        // Un pass actif portant une recharge attend lui aussi un reglement :
        // ce n'est pas le pass qui est en attente, ce sont ses trajets neufs.
        $recharge = $abonnement->statut === StatutAbonnement::Actif
            && $abonnement->trajets_en_attente > 0;

        if ($abonnement->statut !== StatutAbonnement::EnAttente && ! $recharge) {
            throw new RuntimeException('Cet abonnement n’est plus en attente de paiement.');
        }

        $abonnement->update([
            'statut' => StatutAbonnement::Actif,
            // Les trajets recharges rejoignent le compteur utilisable.
            'trajets_restants' => $abonnement->trajets_restants === null
                ? null
                : $abonnement->trajets_restants + $abonnement->trajets_en_attente,
            'trajets_en_attente' => 0,
            'montant_paye_fcfa' => $abonnement->montant_paye_fcfa + $abonnement->montant_du_fcfa,
            'montant_du_fcfa' => 0,
            'reference_paiement' => $reference ?? $abonnement->reference_paiement,
            'paye_le' => now(),
        ]);

        return $abonnement->refresh();
    }

    /**
     * Enregistre l'embarquement d'un etudiant sur un tour et decompte un trajet.
     * Sert de contrepartie individuelle a l'effectif saisi par le chauffeur.
     */
    public function enregistrerEmbarquement(Etudiant $etudiant, Tournee $tournee): Trajet
    {
        $abonnement = $etudiant->abonnementActif();

        if (! $abonnement || ! $abonnement->estUtilisable()) {
            throw new RuntimeException('Aucun abonnement valide pour effectuer ce trajet.');
        }

        return DB::transaction(function () use ($etudiant, $tournee, $abonnement) {
            $trajet = Trajet::firstOrCreate(
                ['etudiant_id' => $etudiant->id, 'tournee_id' => $tournee->id],
                ['abonnement_id' => $abonnement->id, 'embarque_le' => now()],
            );

            if ($trajet->wasRecentlyCreated && $abonnement->trajets_restants !== null) {
                $abonnement->decrement('trajets_restants');
            }

            return $trajet;
        });
    }

    /**
     * Previent les etudiants dont le pass arrive a echeance (3.1).
     *
     * Le rappel ne part qu'une fois par abonnement : sans ce garde-fou, la
     * tache quotidienne repeterait le meme message chaque jour du seuil.
     * Renvoie le nombre d'etudiants prevenus.
     */
    public function rappelerPassBientotExpires(NotificationService $notifications, int $seuilJours = 2): int
    {
        $echeance = today()->addDays($seuilJours);

        $abonnements = Abonnement::where('statut', StatutAbonnement::Actif)
            ->whereNull('rappel_expiration_envoye_le')
            ->whereDate('date_fin', '>=', today())
            ->whereDate('date_fin', '<=', $echeance)
            ->with('etudiant.user')
            ->get();

        foreach ($abonnements as $abonnement) {
            $notifications->notifierPassBientotExpire($abonnement);
            $abonnement->forceFill(['rappel_expiration_envoye_le' => now()])->save();
        }

        return $abonnements->count();
    }

    /** Bascule en "expire" les abonnements dont la date de fin est depassee. */
    public function expirerAbonnementsEchus(): int
    {
        return Abonnement::where('statut', StatutAbonnement::Actif)
            ->whereDate('date_fin', '<', today())
            ->update(['statut' => StatutAbonnement::Expire]);
    }
}
