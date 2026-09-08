<?php

namespace App\Http\Controllers\Bus;

use App\Enums\StatutTournee;
use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\LieuResource;
use App\Http\Resources\Bus\LigneResource;
use App\Http\Resources\Bus\TourneeResource;
use App\Models\Ligne;
use App\Models\Tournee;
use App\Services\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Espace etudiant : consultation des lignes et suivi du bus en direct (section 3.1).
 */
class SuiviController extends Controller
{
    public function __construct(private readonly GeoService $geo) {}

    /** Catalogue des lignes et de leurs points de ramassage, pour le choix a l'inscription. */
    public function lignes(): JsonResponse
    {
        $lignes = Ligne::where('actif', true)
            ->with(['arrets' => fn ($q) => $q->where('actif', true)])
            ->get();

        return response()->json(['data' => LigneResource::collection($lignes)]);
    }

    /**
     * Position du bus desservant la ligne de l'etudiant et estimation
     * de l'heure d'arrivee a son arret.
     */
    public function monBus(Request $request): JsonResponse
    {
        $etudiant = $request->user()->etudiant
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil étudiant.');

        $lieu = $etudiant->lieuRamassage;

        if (! $lieu) {
            return response()->json([
                'message' => 'Aucun point de ramassage n’est associé à votre profil.',
                'tournee' => null,
            ]);
        }

        // Tour actif du jour sur un parcours desservant son point de
        // ramassage. L'etudiant n'est pas rattache a une ligne : plusieurs
        // peuvent passer par son arret, et son campus change chaque jour.
        $tournee = Tournee::query()
            ->whereDate('created_at', today())
            ->whereHas(
                'parcours.etapes',
                fn ($q) => $q->where('lieu_id', $lieu->id),
            )
            ->whereIn('statut', [StatutTournee::EnAttente, StatutTournee::Embarquement, StatutTournee::EnTransit])
            ->with(['affectation.bus.dernierePosition', 'affectation.ligne', 'lieu', 'parcours.etapes.lieu'])
            ->latest('id')
            ->first();

        if (! $tournee) {
            return response()->json([
                'message' => 'Aucun bus n’est actuellement en circulation vers votre arrêt.',
                'tournee' => null,
            ]);
        }

        $position = $tournee->affectation->bus->dernierePosition;
        $estimation = null;

        // Estimation simple : distance restante rapportee a une vitesse moyenne urbaine.
        if ($position) {
            $distance = $this->geo->distanceLieu($lieu, $position->latitude, $position->longitude);
            $vitesse = max(($position->vitesse_kmh ?: 25), 10);
            $minutes = (int) ceil($distance / 1000 / $vitesse * 60);

            $estimation = [
                'distance_metres' => (int) round($distance),
                'minutes_estimees' => $minutes,
                'heure_estimee' => now()->addMinutes($minutes)->toIso8601String(),
            ];
        }

        return response()->json([
            'tournee' => new TourneeResource($tournee),
            'position' => $position ? [
                'latitude' => $position->latitude,
                'longitude' => $position->longitude,
                'vitesse_kmh' => $position->vitesse_kmh,
                'releve_le' => $position->releve_le?->toIso8601String(),
            ] : null,

            // Point de ramassage de l'etudiant : la carte y pose son
            // repere et y centre l'estimation.
            'mon_arret' => new LieuResource($lieu),

            'estimation_arrivee' => $estimation,
            'canal_temps_reel' => "bus.{$tournee->affectation->bus_id}",
        ]);
    }

    /**
     * Parametres de connexion temps reel, pour l'application mobile.
     *
     * Seule la cle publique est exposee : le secret sert a signer cote
     * serveur et ne doit jamais quitter le backend. Le canal de suivi d'un
     * bus est public — il ne porte qu'une position, aucune donnee nominative.
     */
    public function configTempsReel(): JsonResponse
    {
        $actif = config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));

        return response()->json([
            'actif' => $actif,
            'cle' => config('broadcasting.connections.reverb.key'),
            'hote' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'chiffre' => config('broadcasting.connections.reverb.options.scheme') === 'https',
        ]);
    }

    /** Historique des trajets effectues par l'etudiant. */
    public function mesTrajets(Request $request): JsonResponse
    {
        $etudiant = $request->user()->etudiant
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil étudiant.');

        $trajets = $etudiant->trajets()
            ->with('tournee.lieu', 'tournee.affectation.ligne')
            ->latest('embarque_le')
            ->paginate(20);

        return response()->json($trajets);
    }
}
