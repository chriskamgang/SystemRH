<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UniteEnseignement;
use App\Models\UeSeance;
use App\Models\UeValidation;
use Illuminate\Http\Request;

class TpValidationApiController extends Controller
{
    // Mes fiches TP (UEs avec séances)
    public function mesFiches(Request $request)
    {
        $user = $request->user();

        $ues = UniteEnseignement::whereHas('seances')
            ->where('niveau', $user->niveau)
            ->pourSpecialite($user->specialite ?? '')
            ->with(['seances' => function ($q) use ($user) {
                $q->with(['validations' => function ($q2) use ($user) {
                    $q2->where('user_id', $user->id);
                }]);
            }, 'enseignant:id,first_name,last_name'])
            ->orderBy('code_ue')
            ->get();

        $result = $ues->map(function ($ue) use ($user) {
            $totalSeances = $ue->seances->count();
            $tpEffectues = 0;
            $objectifsAtteints = 0;

            $seances = $ue->seances->map(function ($seance) use ($user, &$tpEffectues, &$objectifsAtteints) {
                $validation = $seance->validations->first();
                if ($validation && $validation->tp_effectue) $tpEffectues++;
                if ($validation && $validation->objectif_atteint) $objectifsAtteints++;

                return [
                    'id' => $seance->id,
                    'numero' => $seance->numero,
                    'titre' => $seance->titre,
                    'objectif' => $seance->objectif,
                    'tp_effectue' => $validation ? $validation->tp_effectue : false,
                    'objectif_atteint' => $validation ? $validation->objectif_atteint : false,
                    'observation' => $validation ? $validation->observation : null,
                    'validated_at' => $validation ? $validation->validated_at?->toISOString() : null,
                ];
            });

            return [
                'ue_id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'enseignant' => $ue->enseignant ? $ue->enseignant->first_name . ' ' . $ue->enseignant->last_name : null,
                'total_seances' => $totalSeances,
                'tp_effectues' => $tpEffectues,
                'objectifs_atteints' => $objectifsAtteints,
                'progression' => $totalSeances > 0 ? round(($tpEffectues / $totalSeances) * 100) : 0,
                'seances' => $seances,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    // Valider une séance
    public function valider(Request $request, UeSeance $seance)
    {
        $request->validate([
            'tp_effectue' => 'required|boolean',
            'objectif_atteint' => 'required|boolean',
            'observation' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $validation = UeValidation::updateOrCreate(
            [
                'ue_seance_id' => $seance->id,
                'user_id' => $user->id,
            ],
            [
                'tp_effectue' => $request->tp_effectue,
                'objectif_atteint' => $request->objectif_atteint,
                'observation' => $request->observation,
                'validated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Validation enregistrée.',
            'data' => $validation,
        ]);
    }

    // Mon carnet de compétences (résumé)
    public function monCarnet(Request $request)
    {
        $user = $request->user();

        $totalSeances = UeSeance::whereHas('uniteEnseignement', function ($q) use ($user) {
            $q->where('niveau', $user->niveau)->pourSpecialite($user->specialite ?? '');
        })->count();

        $mesValidations = UeValidation::where('user_id', $user->id);
        $tpEffectues = (clone $mesValidations)->where('tp_effectue', true)->count();
        $objectifsAtteints = (clone $mesValidations)->where('objectif_atteint', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_seances' => $totalSeances,
                'tp_effectues' => $tpEffectues,
                'objectifs_atteints' => $objectifsAtteints,
                'progression_tp' => $totalSeances > 0 ? round(($tpEffectues / $totalSeances) * 100) : 0,
                'progression_objectifs' => $totalSeances > 0 ? round(($objectifsAtteints / $totalSeances) * 100) : 0,
            ],
        ]);
    }
}
