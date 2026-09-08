<?php

namespace App\Http\Controllers\Bus;

use App\Enums\TypeLieu;
use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\LieuResource;
use App\Http\Resources\Bus\ParcoursResource;
use App\Models\Lieu;
use App\Models\Parcours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catalogue du réseau : lieux desservis et parcours (CDC §3.1).
 */
class ReseauController extends Controller
{
    /**
     * Points de ramassage proposés à l'étudiant à l'inscription.
     *
     * Seuls les lieux réellement desservis par un parcours actif sont
     * renvoyés : proposer un arrêt qu'aucun bus ne dessert n'aurait pas
     * de sens. Le campus, lui, n'est pas demandé — il change d'un jour à
     * l'autre selon l'emploi du temps.
     */
    public function pointsRamassage(): AnonymousResourceCollection
    {
        $lieux = Lieu::query()
            ->where('actif', true)
            ->where('type', TypeLieu::Ramassage)
            ->whereHas('etapes.parcours', fn ($q) => $q->where('actif', true))
            ->with(['etapes.parcours.ligne'])
            ->orderBy('nom')
            ->get();

        return LieuResource::collection($lieux);
    }

    /** Tous les lieux du réseau, campus compris. */
    public function lieux(): AnonymousResourceCollection
    {
        $lieux = Lieu::query()
            ->where('actif', true)
            ->orderBy('type')
            ->orderBy('nom')
            ->get();

        return LieuResource::collection($lieux);
    }

    /** Parcours actifs, avec leurs étapes ordonnées. */
    public function parcours(): AnonymousResourceCollection
    {
        $parcours = Parcours::query()
            ->where('actif', true)
            ->with(['ligne', 'etapes.lieu'])
            ->get();

        return ParcoursResource::collection($parcours);
    }

    /**
     * Niveaux et specialites proposes a la completion de profil.
     *
     * Ils viennent d'Estuaire RH, ou l'administration les tient a jour :
     * c'est le couple (niveau, specialite) qui rattache l'etudiant a ses
     * unites d'enseignement, donc a son emploi du temps. Les proposer en
     * liste evite les saisies approximatives, qui rendraient l'emploi du
     * temps vide sans que l'etudiant comprenne pourquoi.
     */
    public function scolarite(): JsonResponse
    {
        // Les valeurs sont lues sur les unites d'enseignement elles-memes,
        // et non sur les tables `levels` / `specialties` : ce sont ces
        // chaines-la que compare l'emploi du temps, et elles seules
        // garantissent qu'un choix donnera un resultat non vide.
        $ues = \App\Models\UniteEnseignement::query()->withoutGlobalScopes();

        return response()->json([
            'niveaux' => (clone $ues)
                ->whereNotNull('niveau')
                ->distinct()
                ->orderBy('niveau')
                ->pluck('niveau')
                ->values(),

            'specialites' => (clone $ues)
                ->whereNotNull('specialite')
                ->distinct()
                ->orderBy('specialite')
                ->pluck('specialite')
                ->values(),
        ]);
    }
}
