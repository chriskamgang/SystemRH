<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\AbonnementResource;
use App\Http\Resources\Bus\TarifResource;
use App\Enums\StatutAbonnement;
use App\Models\Abonnement;
use App\Models\Tarif;
use App\Exceptions\KpayException;
use App\Models\TransactionKpay;
use App\Services\AbonnementService;
use App\Services\BilletterieService;
use App\Services\Kpay\KpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Abonnements et tarification incitative (section 3.1).
 */
class AbonnementController extends Controller
{
    public function __construct(
        private readonly AbonnementService $abonnements,
        private readonly KpayService $kpay,
        private readonly BilletterieService $billetterie,
    ) {}

    /** Grille tarifaire en vigueur. */
    public function tarifs(): JsonResponse
    {
        return response()->json([
            'data' => TarifResource::collection(Tarif::where('actif', true)->get()),
        ]);
    }

    /** Abonnements de l'etudiant connecte. */
    public function index(Request $request): JsonResponse
    {
        $etudiant = $this->etudiant($request);

        $abonnements = $etudiant->abonnements()
            ->with('tarif')
            ->latest('date_debut')
            ->paginate(20);

        return response()->json($abonnements);
    }

    /** Souscription d'un ticket ou d'un pass semaine. */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'tarif_id' => ['required', 'integer', 'exists:tarifs,id'],
            'date_debut' => ['nullable', 'date', 'after_or_equal:today'],
            'moyen_paiement' => ['nullable', 'string', 'in:especes,mobile_money,carte'],
            'reference_paiement' => ['nullable', 'string', 'max:255'],
        ]);

        $etudiant = $this->etudiant($request);
        $tarif = Tarif::findOrFail($donnees['tarif_id']);

        try {
            $abonnement = $this->abonnements->souscrire(
                $etudiant,
                $tarif,
                isset($donnees['date_debut'])
                    ? \Carbon\CarbonImmutable::parse($donnees['date_debut'])
                    : null,
                $donnees['moyen_paiement'] ?? null,
                $donnees['reference_paiement'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Abonnement créé, en attente de paiement.',
            'abonnement' => new AbonnementResource($abonnement->load('tarif')),
        ], 201);
    }

    /** Confirmation du paiement (webhook mobile money ou saisie manuelle). */
    public function confirmerPaiement(Request $request, Abonnement $abonnement): JsonResponse
    {
        $etudiant = $this->etudiant($request);

        abort_unless($abonnement->etudiant_id === $etudiant->id, 403);

        $donnees = $request->validate([
            'reference_paiement' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $abonnement = $this->abonnements->confirmerPaiement(
                $abonnement,
                $donnees['reference_paiement'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Paiement confirmé, abonnement actif.',
            'abonnement' => new AbonnementResource($abonnement->load('tarif')),
        ]);
    }

    /**
     * Pass dont l'etudiant dispose actuellement.
     *
     * Il peut en detenir plusieurs de natures differentes : un forfait
     * semaine et un ticket d'appoint ne se cumulent pas, ils coexistent.
     * L'ecran les presente tous ; `abonnement` reste servi pour les
     * clients qui n'en attendent qu'un.
     */
    public function actif(Request $request): JsonResponse
    {
        $etudiant = $this->etudiant($request);
        $utilisables = $this->billetterie->passUtilisables($etudiant);

        // Un pass actif portant une recharge impayee doit rester visible :
        // il est utilisable, et son reglement reste a faire.
        $aRegler = $etudiant->abonnements()
            ->with('tarif')
            ->where('statut', StatutAbonnement::EnAttente)
            ->whereDate('date_fin', '>=', today())
            ->orderBy('date_fin')
            ->get();

        $tous = $utilisables->concat($aRegler)->unique('id')->values();

        return response()->json([
            'pass' => AbonnementResource::collection($tous),
            'total_trajets' => $utilisables->sum(
                fn ($a) => $a->trajets_restants ?? 0,
            ),
            'abonnement' => $tous->first()
                ? new AbonnementResource($tous->first())
                : null,
        ]);
    }

    /**
     * Paiement Mobile Money : declenche un push USSD sur le telephone de l'etudiant.
     * Le statut definitif arrive par webhook KPay.
     */
    public function payerParMobileMoney(Request $request, Abonnement $abonnement): JsonResponse
    {
        $etudiant = $this->etudiant($request);

        abort_unless($abonnement->etudiant_id === $etudiant->id, 403);

        $donnees = $request->validate([
            'telephone' => ['nullable', 'string', 'max:20'],
            'provider' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $transaction = $this->kpay->encaisserAbonnement(
                $abonnement->load('tarif'),
                $donnees['telephone'] ?? null,
                $donnees['provider'] ?? null,
            );
        } catch (KpayException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Demande de paiement envoyée. Validez-la sur votre téléphone.',
            'transaction' => [
                'external_id' => $transaction->external_id,
                'reference' => $transaction->reference,
                'statut' => $transaction->statut,
                'montant' => $transaction->montant,
                'devise' => $transaction->devise,
            ],
        ], 201);
    }

    /** Consultation du statut du paiement, en complement du webhook. */
    public function statutPaiement(Request $request, Abonnement $abonnement): JsonResponse
    {
        $etudiant = $this->etudiant($request);

        abort_unless($abonnement->etudiant_id === $etudiant->id, 403);

        $transaction = TransactionKpay::where('payable_type', Abonnement::class)
            ->where('payable_id', $abonnement->id)
            ->latest('id')
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Aucun paiement n’a été initié.'], 404);
        }

        try {
            $transaction = $this->kpay->rafraichir($transaction);
        } catch (KpayException) {
            // Le statut local reste la meilleure information disponible.
        }

        return response()->json([
            'statut' => $transaction->statut,
            'reference' => $transaction->reference,
            'motif_echec' => $transaction->motif_echec,
            'abonnement' => new AbonnementResource($abonnement->refresh()->load('tarif')),
        ]);
    }

    /**
     * Jeton QR a afficher sur le pass.
     *
     * Il tourne toutes les 30 secondes : une capture d'ecran transmise a un
     * camarade est sans valeur passe ce delai.
     */
    public function jetonQr(Request $request): JsonResponse
    {
        $etudiant = $this->etudiant($request);
        $utilisables = $this->billetterie->passUtilisables($etudiant);

        if ($utilisables->isEmpty()) {
            return response()->json([
                'message' => 'Aucun pass valide : souscrivez une formule pour embarquer.',
                'jeton' => null,
                'pass' => [],
            ], 422);
        }

        $expiration = $this->billetterie->expiration()->toIso8601String();

        // Un QR par pass : l'etudiant qui en detient plusieurs presente
        // celui qu'il veut voir debite, et le scan ne peut pas se tromper.
        $pass = $utilisables->map(fn ($abonnement) => [
            'jeton' => $this->billetterie->genererJeton($etudiant, $abonnement),
            'abonnement' => new AbonnementResource($abonnement),
        ]);

        return response()->json([
            'expire_le' => $expiration,
            'validite_secondes' => BilletterieService::VALIDITE_SECONDES,
            'pass' => $pass,

            // Compatibilite : les clients d'avant le choix du pass ne lisent
            // qu'un jeton unique, celui du titre le plus proche de l'echeance.
            'jeton' => $pass->first()['jeton'],
            'abonnement' => $pass->first()['abonnement'],
        ]);
    }

    private function etudiant(Request $request)
    {
        return $request->user()->etudiant
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil étudiant.');
    }
}
