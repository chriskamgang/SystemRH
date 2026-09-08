<?php

namespace App\Http\Controllers\Bus;

use App\Exceptions\KpayException;
use App\Exceptions\WalletException;
use App\Http\Controllers\Controller;
use App\Models\RetraitChauffeur;
use App\Services\Kpay\KpayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wallet du chauffeur : solde alimente par les trajets scannes, et retrait
 * vers son compte Mobile Money quand il le decide.
 */
class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly KpayService $kpay,
    ) {}

    /** Solde, cumuls et derniers mouvements. */
    public function index(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);
        $wallet = $this->wallets->wallet($chauffeur);

        return response()->json([
            'solde_fcfa' => $wallet->solde_fcfa,
            'solde_reserve_fcfa' => $wallet->solde_reserve_fcfa,
            'total_percu_fcfa' => $wallet->total_percu_fcfa,
            'total_retire_fcfa' => $wallet->total_retire_fcfa,
            'retrait_minimum_fcfa' => WalletService::RETRAIT_MINIMUM,
            'retrait_en_cours' => $wallet->aUnRetraitEnCours(),
            'mouvements' => $wallet->mouvements()->take(30)->get()->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'libelle' => $m->libelle,
                'montant_fcfa' => $m->montant_fcfa,
                'solde_apres_fcfa' => $m->solde_apres_fcfa,
                'date' => $m->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /** Historique pagine des retraits. */
    public function retraits(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        return response()->json(
            RetraitChauffeur::where('chauffeur_id', $chauffeur->id)
                ->latest('id')
                ->paginate(20),
        );
    }

    /**
     * Demande de retrait.
     *
     * La somme quitte le solde immediatement et reste immobilisee jusqu'a
     * l'issue de l'operation : un echec la restitue.
     */
    public function retirer(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $donnees = $request->validate([
            'montant' => ['required', 'integer', 'min:'.WalletService::RETRAIT_MINIMUM],
            'telephone' => ['nullable', 'string', 'max:20'],
            'provider' => ['nullable', 'string', 'max:40'],
        ]);

        $telephone = $donnees['telephone'] ?? $chauffeur->user?->telephone_bus;

        if (blank($telephone)) {
            return response()->json([
                'message' => 'Aucun numéro Mobile Money n’est enregistré sur votre compte.',
                'raison' => 'telephone_manquant',
            ], 422);
        }

        try {
            $retrait = $this->wallets->demanderRetrait(
                $chauffeur,
                $donnees['montant'],
                $telephone,
                $donnees['provider'] ?? null,
            );
        } catch (WalletException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'raison' => $e->raison,
                'contexte' => $e->contexte,
            ], 422);
        }

        // Le versement part vers KPay ; son issue arrive par webhook.
        try {
            $transaction = $this->kpay->verserRetrait($retrait);
        } catch (KpayException $e) {
            // L'appel a echoue : la somme immobilisee revient au solde,
            // sans quoi elle resterait bloquee sans contrepartie.
            $this->wallets->echouerRetrait($retrait, $e->getMessage());

            return response()->json([
                'message' => $e->getMessage(),
                'raison' => 'versement_refuse',
            ], 422);
        }

        return response()->json([
            'message' => 'Retrait demandé. Vous recevrez les fonds sur votre Mobile Money.',
            'retrait' => [
                'id' => $retrait->id,
                'montant_fcfa' => $retrait->montant_fcfa,
                'statut' => $retrait->refresh()->statut,
                'telephone' => $retrait->telephone,
                'reference' => $transaction->reference,
            ],
            'solde_fcfa' => $this->wallets->wallet($chauffeur)->solde_fcfa,
        ], 201);
    }

    private function chauffeur(Request $request)
    {
        return $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');
    }
}
