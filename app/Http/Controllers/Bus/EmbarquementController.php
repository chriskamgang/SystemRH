<?php

namespace App\Http\Controllers\Bus;

use App\Enums\MotifEcartEffectif;
use App\Exceptions\BilletterieException;
use App\Exceptions\PointageInvalideException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\AbonnementResource;
use App\Http\Resources\Bus\TourneeResource;
use App\Models\Tournee;
use App\Services\BilletterieService;
use App\Services\PointageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controle des titres de transport a la montee (3.2).
 *
 * Le chauffeur scanne le QR du pass etudiant : le scan consomme un trajet et
 * alimente le comptage confronte a l'effectif qu'il declare.
 */
class EmbarquementController extends Controller
{
    public function __construct(
        private readonly BilletterieService $billetterie,
        private readonly PointageService $pointages,
    ) {}

    /** Scan d'un QR de pass etudiant sur le tour en cours. */
    public function scanner(Request $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $donnees = $request->validate([
            'jeton' => ['required', 'string', 'max:255'],
        ]);

        try {
            $resultat = $this->billetterie->validerParJeton(
                $donnees['jeton'],
                $tournee,
                $request->user(),
            );
        } catch (BilletterieException $e) {
            // 422 : le QR est lisible mais le droit au transport est refuse.
            return response()->json([
                'message' => $e->getMessage(),
                'raison' => $e->raison,
                'contexte' => $e->contexte,
            ], 422);
        }

        $etudiant = $resultat['trajet']->etudiant()->with('user')->first();

        return response()->json([
            'message' => $resultat['deja_valide']
                ? 'Cet étudiant a déjà été scanné sur ce tour.'
                : 'Embarquement validé.',
            'deja_valide' => $resultat['deja_valide'],
            'etudiant' => [
                'id' => $etudiant->id,
                'nom_complet' => $etudiant->user?->nom_complet,
                'matricule' => $etudiant->matricule_insam,
            ],
            'abonnement' => $resultat['abonnement']
                ? new AbonnementResource($resultat['abonnement']->load('tarif'))
                : null,
            'comptage' => $this->comptage($tournee->refresh()),
        ], $resultat['deja_valide'] ? 200 : 201);
    }

    /** Etat du comptage du tour : scannes, comptes, ecart. */
    public function comptageDuTour(Request $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $this->billetterie->recalculerEcart($tournee);

        return response()->json([
            'comptage' => $this->comptage($tournee->refresh()),
            'embarquements' => $tournee->trajets()
                ->with('etudiant.user')
                ->latest('embarque_le')
                ->get()
                ->map(fn ($trajet) => [
                    'id' => $trajet->id,
                    'etudiant_id' => $trajet->etudiant_id,
                    'nom_complet' => $trajet->etudiant?->user?->nom_complet,
                    'matricule' => $trajet->etudiant?->matricule_insam,
                    'embarque_le' => $trajet->embarque_le?->toIso8601String(),
                    'mode_validation' => $trajet->mode_validation,
                ]),
        ]);
    }

    /** Justification de l'ecart, prealable obligatoire au depart. */
    public function justifierEcart(Request $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'in:'.implode(',', array_column(MotifEcartEffectif::cases(), 'value'))],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $tournee = $this->pointages->justifierEcart(
                $tournee,
                MotifEcartEffectif::from($donnees['motif']),
                $donnees['commentaire'] ?? null,
            );
        } catch (PointageInvalideException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'raison' => $e->raison,
            ], 422);
        }

        return response()->json([
            'message' => 'Écart justifié, vous pouvez déclarer le départ.',
            'tournee' => new TourneeResource($tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne')),
            'comptage' => $this->comptage($tournee),
        ]);
    }

    /** Motifs d'ecart proposes au chauffeur. */
    public function motifs(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (MotifEcartEffectif $m) => [
                    'valeur' => $m->value,
                    'libelle' => $m->libelle(),
                    'exige_commentaire' => $m->exigeCommentaire(),
                ],
                MotifEcartEffectif::cases(),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function comptage(Tournee $tournee): array
    {
        return [
            'effectif_embarque' => $tournee->effectif_embarque,
            'embarquements_valides' => $tournee->embarquements_valides,
            'passagers_sans_ticket' => $tournee->passagers_sans_ticket,
            'motif_ecart' => $tournee->motif_ecart,
            'commentaire_ecart' => $tournee->commentaire_ecart,
            'ecart_justifie' => $tournee->passagers_sans_ticket === 0
                || filled($tournee->motif_ecart),
        ];
    }

    /** Un chauffeur ne pointe que ses propres tours. */
    private function autoriser(Request $request, Tournee $tournee): void
    {
        $chauffeur = $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');

        abort_unless($tournee->affectation->chauffeur_id === $chauffeur->id, 403);
    }
}
