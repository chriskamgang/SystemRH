<?php

namespace App\Http\Controllers\Bus;

use App\Enums\StatutMissionSecours;
use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\MissionSecoursResource;
use App\Http\Resources\Bus\PanneResource;
use App\Models\MissionSecours;
use App\Models\Tournee;
use App\Services\SecoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Declaration de panne et missions de secours cote chauffeur (section 3.3).
 */
class SecoursController extends Controller
{
    public function __construct(private readonly SecoursService $secours) {}

    /** Le chauffeur en difficulte declare une panne : alerte immediate au back-office. */
    public function declarerPanne(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $donnees = $request->validate([
            'tournee_id' => ['nullable', 'integer', 'exists:tournees,id'],
            'type_panne' => ['nullable', 'string', 'in:mecanique,pneu,carburant,accident,autre'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'passagers_immobilises' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $tournee = isset($donnees['tournee_id'])
            ? Tournee::with('affectation')->find($donnees['tournee_id'])
            : null;

        // Le bus concerne est celui du tour en cours, sinon celui affecte au chauffeur aujourd'hui.
        $bus = $tournee?->affectation->bus
            ?? $chauffeur->affectations()->whereDate('date_service', today())->with('bus')->first()?->bus;

        if (! $bus) {
            return response()->json([
                'message' => 'Aucun bus ne vous est affecté aujourd’hui.',
            ], 422);
        }

        $panne = $this->secours->declarerPanne($chauffeur, $bus, $tournee, $donnees);

        return response()->json([
            'message' => 'Panne déclarée, le back-office est alerté.',
            'panne' => new PanneResource($panne->load('bus')),
        ], 201);
    }

    /** Missions de secours affectees au chauffeur connecte. */
    public function mesMissions(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $missions = $chauffeur->missionsSecours()
            ->with('panne.bus', 'bus')
            ->latest('affectee_le')
            ->paginate(20);

        return response()->json($missions);
    }

    public function accepter(Request $request, MissionSecours $mission): JsonResponse
    {
        $this->autoriser($request, $mission);

        if ($mission->statut !== StatutMissionSecours::Affectee) {
            return response()->json(['message' => 'Cette mission n’est plus en attente d’acceptation.'], 422);
        }

        return response()->json([
            'message' => 'Mission acceptée.',
            'mission' => new MissionSecoursResource(
                $this->secours->accepterMission($mission)->load('panne.bus', 'bus'),
            ),
        ]);
    }

    public function demarrer(Request $request, MissionSecours $mission): JsonResponse
    {
        $this->autoriser($request, $mission);

        if ($mission->statut !== StatutMissionSecours::Acceptee) {
            return response()->json(['message' => 'Acceptez la mission avant de la démarrer.'], 422);
        }

        return response()->json([
            'message' => 'Mission démarrée.',
            'mission' => new MissionSecoursResource(
                $this->secours->demarrerMission($mission)->load('panne.bus', 'bus'),
            ),
        ]);
    }

    /** Cloture de la mission : le nombre de passagers recuperes conditionne la prime (3.4). */
    public function terminer(Request $request, MissionSecours $mission): JsonResponse
    {
        $this->autoriser($request, $mission);

        if (! in_array($mission->statut, [StatutMissionSecours::Acceptee, StatutMissionSecours::EnRoute], true)) {
            return response()->json(['message' => 'Cette mission ne peut pas être clôturée.'], 422);
        }

        $donnees = $request->validate([
            'passagers_recuperes' => ['required', 'integer', 'min:0', 'max:200'],
            'panne_confirmee' => ['nullable', 'boolean'],
        ]);

        $mission = $this->secours->terminerMission(
            $mission,
            $donnees['passagers_recuperes'],
            $donnees['panne_confirmee'] ?? true,
        );

        return response()->json([
            'message' => $mission->ouvreDroitAPrime()
                ? 'Mission terminée, prime de secours créditée.'
                : 'Mission terminée. La prime nécessite la confirmation de la panne et de la prise en charge.',
            'mission' => new MissionSecoursResource($mission->load('panne.bus', 'bus')),
        ]);
    }

    private function chauffeur(Request $request)
    {
        return $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');
    }

    private function autoriser(Request $request, MissionSecours $mission): void
    {
        abort_unless(
            $mission->chauffeur_id === $this->chauffeur($request)->id,
            403,
            'Cette mission ne vous est pas affectée.',
        );
    }
}
