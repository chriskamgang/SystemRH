<?php

namespace App\Http\Controllers\Bus;

use App\Enums\SensParcours;
use App\Enums\StatutTournee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bus\EffectifRequest;
use App\Http\Requests\Bus\PointageRequest;
use App\Http\Requests\Bus\PositionRequest;
use App\Http\Resources\Bus\AffectationResource;
use App\Http\Resources\Bus\TourneeResource;
use App\Models\Tournee;
use App\Services\NotificationService;
use App\Services\PointageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Espace chauffeur : cycle de pointage des tours (section 3.2).
 */
class PointageController extends Controller
{
    public function __construct(
        private readonly PointageService $pointages,
        private readonly NotificationService $notifications,
    ) {}

    /** Affectation du jour du chauffeur connecte, avec le tour en cours s'il existe. */
    public function serviceDuJour(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $affectation = $chauffeur->affectations()
            ->whereDate('date_service', today())
            ->with(['bus', 'ligne.parcours.etapes.lieu'])
            ->first();

        if (! $affectation) {
            return response()->json([
                'message' => 'Aucun service ne vous est affecté pour aujourd’hui.',
                'affectation' => null,
                'tournee_en_cours' => null,
            ]);
        }

        $enCours = $affectation->tournees()
            ->whereNotIn('statut', [StatutTournee::Termine, StatutTournee::Annule])
            ->with('lieu', 'parcours.etapes.lieu')
            ->first();

        return response()->json([
            'affectation' => new AffectationResource($affectation),
            'tours_realises' => $affectation->toursValides(),
            'tours_prevus' => $affectation->tours_prevus,
            'tournee_en_cours' => $enCours ? new TourneeResource($enCours) : null,
        ]);
    }

    /** Etape 1 : demarrage du service, ouvre un nouveau tour. */
    public function demarrer(PointageRequest $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $affectation = $chauffeur->affectations()
            ->whereDate('date_service', today())
            ->with(['bus', 'ligne.parcours.etapes.lieu'])
            ->firstOrFail();

        $sens = $request->validated('sens');

        $tournee = $this->pointages->demarrerService(
            $affectation,
            $request->validated('latitude'),
            $request->validated('longitude'),
            $sens ? SensParcours::from($sens) : null,
        );

        // Les etudiants de l'arret desservi sont prevenus du depart (3.1).
        $this->notifications->notifierDepartBus(
            $tournee->load('affectation.bus', 'lieu', 'parcours.etapes'),
        );

        return response()->json([
            'message' => 'Service démarré.',
            'tournee' => new TourneeResource(
                $tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne'),
            ),
        ], 201);
    }

    /** Etape 2 : arrivee au point de ramassage. */
    public function arriverAuPoint(PointageRequest $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $tournee = $this->pointages->arriverAuPoint(
            $tournee,
            $request->validated('latitude'),
            $request->validated('longitude'),
        );

        return response()->json([
            'message' => 'Arrivée au point enregistrée.',
            'tournee' => new TourneeResource($tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne')),
        ]);
    }

    /** Etape 3 : saisie de l'effectif reel embarque. */
    public function saisirEffectif(EffectifRequest $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $tournee = $this->pointages->saisirEffectif(
            $tournee,
            $request->validated('effectif'),
            $request->validated('latitude'),
            $request->validated('longitude'),
        );

        return response()->json([
            'message' => 'Effectif enregistré.',
            'tournee' => new TourneeResource($tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne')),
        ]);
    }

    /** Etape 4 : depart vers le campus. */
    public function partir(PointageRequest $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $tournee = $this->pointages->partirVersCampus(
            $tournee,
            $request->validated('latitude'),
            $request->validated('longitude'),
        );

        return response()->json([
            'message' => 'Départ enregistré, bus en transit.',
            'tournee' => new TourneeResource($tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne')),
        ]);
    }

    /** Etape 5 : arrivee au campus, cloture du tour. */
    public function terminer(PointageRequest $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $tournee = $this->pointages->terminerTour(
            $tournee,
            $request->validated('latitude'),
            $request->validated('longitude'),
        );

        return response()->json([
            'message' => 'Tour terminé.',
            'tournee' => new TourneeResource($tournee->load('lieu', 'parcours.etapes.lieu', 'affectation.bus', 'affectation.ligne')),
        ]);
    }

    /**
     * Signalement d'un retard par le chauffeur (US-04).
     *
     * Le CDC exige que l'etudiant soit prevenu d'un retard ; le declencheur
     * naturel est le chauffeur, seul a savoir s'il est pris dans un
     * embouteillage ou retenu au depot. Le back-office garde la main par
     * ailleurs pour un changement de vehicule.
     */
    public function signalerRetard(Request $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        if (in_array($tournee->statut, [StatutTournee::Termine, StatutTournee::Annule], true)) {
            return response()->json([
                'message' => 'Ce tour est clos : le retard ne concerne plus personne.',
                'raison' => 'tour_clos',
            ], 422);
        }

        $donnees = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:180'],
            'motif' => ['nullable', 'string', 'max:200'],
        ]);

        $this->notifications->notifierRetardDeclare(
            $tournee->load('parcours.etapes', 'affectation.bus'),
            $donnees['minutes'],
            $donnees['motif'] ?? null,
        );

        return response()->json([
            'message' => 'Les étudiants desservis ont été prévenus du retard.',
        ]);
    }

    /** Remontee periodique de la position GPS pendant le trajet. */
    public function remonterPosition(PositionRequest $request, Tournee $tournee): JsonResponse
    {
        $this->autoriser($request, $tournee);

        $this->pointages->remonterPosition(
            $tournee,
            $request->validated('latitude'),
            $request->validated('longitude'),
            $request->validated('vitesse_kmh'),
            $request->validated('cap_degres'),
        );

        return response()->json(['message' => 'Position enregistrée.'], 202);
    }

    private function chauffeur(Request $request)
    {
        return $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');
    }

    /** Un chauffeur ne peut pointer que ses propres tours. */
    private function autoriser(Request $request, Tournee $tournee): void
    {
        $chauffeur = $this->chauffeur($request);

        abort_unless(
            $tournee->affectation->chauffeur_id === $chauffeur->id,
            403,
            'Ce tour ne vous est pas affecté.',
        );
    }
}
