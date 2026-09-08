<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCours;
use App\Models\UniteEnseignement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Evaluation des cours par les etudiants.
 *
 * Un etudiant note les unites d'enseignement qu'il suit, et lui seul :
 * l'appartenance est verifiee a chaque appel, faute de quoi n'importe quel
 * compte pourrait peser sur la moyenne d'un cours qui ne le concerne pas.
 */
class EvaluationCoursController extends Controller
{
    /**
     * Les cours que l'etudiant peut evaluer, avec son avis s'il en a
     * deja depose un.
     *
     * L'ecran affiche les deux etats dans une meme liste : sans cela, un
     * avis rendu disparaitrait de la vue et l'etudiant le croirait perdu.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->employee_type !== 'etudiant') {
            return response()->json([
                'message' => "L'évaluation des cours est réservée aux étudiants.",
            ], 403);
        }

        $ues = $this->coursDeLEtudiant($request)
            ->orderBy('nom_matiere')
            ->get();

        $miens = EvaluationCours::where('user_id', $user->id)
            ->whereIn('unite_enseignement_id', $ues->pluck('id'))
            ->get()
            ->keyBy('unite_enseignement_id');

        return response()->json([
            'data' => $ues->map(function (UniteEnseignement $ue) use ($miens) {
                $avis = $miens->get($ue->id);

                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'semestre' => $ue->semestre,
                    'mon_evaluation' => $avis ? [
                        'note' => $avis->note,
                        'commentaire' => $avis->commentaire,
                        'modifie_le' => $avis->updated_at?->toIso8601String(),
                    ] : null,
                ];
            })->values(),
        ]);
    }

    /**
     * Depose ou revise l'avis de l'etudiant sur un cours.
     *
     * Le second passage remplace le premier plutot que d'en ajouter un :
     * la contrainte d'unicite en base dit la meme chose, on evite juste de
     * la laisser echouer sous forme d'erreur SQL.
     */
    public function store(Request $request, UniteEnseignement $uniteEnseignement): JsonResponse
    {
        $user = $request->user();

        if ($user->employee_type !== 'etudiant') {
            return response()->json([
                'message' => "L'évaluation des cours est réservée aux étudiants.",
            ], 403);
        }

        $valide = $request->validate([
            'note' => [
                'required',
                'integer',
                'between:' . EvaluationCours::NOTE_MIN . ',' . EvaluationCours::NOTE_MAX,
            ],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ], [
            'note.required' => 'Une note est attendue.',
            'note.integer' => 'La note doit être un nombre entier.',
            'note.between' => 'La note va de 1 à 5 étoiles.',
        ]);

        // Un cours qu'il ne suit pas ne le regarde pas : la reponse ne dit
        // pas si l'UE existe, pour ne pas transformer cette route en
        // catalogue des enseignements.
        $suitCeCours = $this->coursDeLEtudiant($request)
            ->whereKey($uniteEnseignement->id)
            ->exists();

        if (! $suitCeCours) {
            return response()->json([
                'message' => "Ce cours ne figure pas dans votre emploi du temps.",
            ], 403);
        }

        $evaluation = EvaluationCours::updateOrCreate(
            [
                'user_id' => $user->id,
                'unite_enseignement_id' => $uniteEnseignement->id,
            ],
            [
                'note' => $valide['note'],
                'commentaire' => $valide['commentaire'] ?? null,
            ],
        );

        return response()->json([
            'message' => 'Merci, votre avis est enregistré.',
            'data' => [
                'note' => $evaluation->note,
                'commentaire' => $evaluation->commentaire,
                'modifie_le' => $evaluation->updated_at?->toIso8601String(),
            ],
        ], $evaluation->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Les UE du niveau et de la specialite de l'etudiant.
     *
     * Meme critere que l'emploi du temps (`UeScheduleApiController`) : les
     * deux ecrans doivent montrer les memes cours, sans quoi l'etudiant
     * pourrait noter une matiere absente de son planning — ou l'inverse.
     */
    private function coursDeLEtudiant(Request $request)
    {
        $user = $request->user();

        return UniteEnseignement::where('niveau', $user->niveau)
            ->pourSpecialite($user->specialite);
    }
}
