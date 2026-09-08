<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bus\PresenceRequest;
use App\Http\Resources\Bus\PresenceResource;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Presence en ligne des chauffeurs et carte de la flotte (CDC 3.1).
 *
 * Le chauffeur passe en ligne des l'ouverture de son application ; les
 * etudiants lisent la flotte en ligne pour peupler leur carte, puis suivent
 * les deplacements par le canal temps reel « flotte ».
 */
class PresenceController extends Controller
{
    public function __construct(private readonly PresenceService $presences) {}

    /**
     * Battement de presence du chauffeur.
     *
     * Appele a l'ouverture de l'application puis a intervalle regulier. Le
     * bus et le tour eventuel sont deduits de l'affectation du jour : rien
     * n'est pris du client, qui ne peut donc pas se declarer sur le bus
     * d'un autre.
     */
    public function battre(PresenceRequest $request): JsonResponse
    {
        $chauffeur = $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');

        $presence = $this->presences->pointerPresence(
            $chauffeur,
            $request->validated('latitude'),
            $request->validated('longitude'),
            $request->validated('vitesse_kmh'),
            $request->validated('cap_degres'),
        );

        return response()->json([
            'en_ligne' => true,

            // Le client saura ainsi s'il diffuse pour de bon : en ligne sans
            // affectation, personne ne le voit sur la carte.
            'bus_id' => $presence->bus_id,
            'tournee_id' => $presence->tournee_id,
            'diffuse' => $presence->bus_id !== null,
            'vu_le' => $presence->vu_le?->toIso8601String(),
        ]);
    }

    /** Le chauffeur quitte le service : son bus disparait de la carte. */
    public function quitter(Request $request): JsonResponse
    {
        $chauffeur = $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');

        $this->presences->quitter($chauffeur);

        return response()->json(['en_ligne' => false]);
    }

    /**
     * Bus actuellement en ligne, stationnes comme en circulation.
     *
     * Route publique : elle ne porte qu'une immatriculation et une position,
     * aucune donnee nominative — la meme information qu'un passant lit sur
     * le flanc du vehicule.
     */
    public function busEnLigne(): JsonResponse
    {
        $presences = $this->presences->busEnLigne();

        return response()->json([
            'data' => PresenceResource::collection($presences),
            'total' => $presences->count(),
        ]);
    }
}
