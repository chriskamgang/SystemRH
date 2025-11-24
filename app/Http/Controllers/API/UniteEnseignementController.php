<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniteEnseignementController extends Controller
{
    /**
     * Liste des UE du vacataire connecté
     * GET /api/unites-enseignement
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Vérifier que c'est un enseignant vacataire
        if ($user->employee_type !== 'enseignant_vacataire') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux enseignants vacataires'
            ], 403);
        }

        // Récupérer les UE activées
        $unitesActivees = UniteEnseignement::where('vacataire_id', $user->id)
            ->where('statut', 'activee')
            ->with('presenceIncidents')
            ->orderBy('nom_matiere')
            ->get()
            ->map(function ($ue) use ($user) {
                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'volume_horaire_total' => (float) $ue->volume_horaire_total,
                    'heures_effectuees' => (float) $ue->heures_effectuees,
                    'heures_restantes' => (float) $ue->heures_restantes,
                    'pourcentage_progression' => (float) $ue->pourcentage_progression,
                    'montant_paye' => (float) $ue->montant_paye,
                    'montant_restant' => (float) $ue->montant_restant,
                    'montant_max' => (float) $ue->montant_max,
                    'taux_horaire' => (float) $user->hourly_rate,
                    'annee_academique' => $ue->annee_academique,
                    'semestre' => $ue->semestre,
                    'statut' => 'activee',
                    'date_activation' => $ue->date_activation?->format('Y-m-d H:i:s'),
                ];
            });

        // Récupérer les UE non activées
        $unitesNonActivees = UniteEnseignement::where('vacataire_id', $user->id)
            ->where('statut', 'non_activee')
            ->orderBy('nom_matiere')
            ->get()
            ->map(function ($ue) use ($user) {
                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'volume_horaire_total' => (float) $ue->volume_horaire_total,
                    'montant_potentiel' => (float) $ue->montant_max,
                    'taux_horaire' => (float) $user->hourly_rate,
                    'annee_academique' => $ue->annee_academique,
                    'semestre' => $ue->semestre,
                    'statut' => 'non_activee',
                    'date_attribution' => $ue->date_attribution?->format('Y-m-d H:i:s'),
                ];
            });

        // Calculer les totaux
        $totalHeuresEffectuees = $unitesActivees->sum('heures_effectuees');
        $totalMontantPaye = $unitesActivees->sum('montant_paye');
        $totalMontantRestant = $unitesActivees->sum('montant_restant');

        return response()->json([
            'success' => true,
            'data' => [
                'unites_activees' => $unitesActivees,
                'unites_non_activees' => $unitesNonActivees,
                'totaux' => [
                    'heures_effectuees' => (float) $totalHeuresEffectuees,
                    'montant_paye' => (float) $totalMontantPaye,
                    'montant_restant' => (float) $totalMontantRestant,
                    'taux_horaire' => (float) $user->hourly_rate,
                ],
            ]
        ]);
    }

    /**
     * Détails d'une UE spécifique
     * GET /api/unites-enseignement/{id}
     */
    public function show($id)
    {
        $user = Auth::user();

        $ue = UniteEnseignement::where('id', $id)
            ->where('vacataire_id', $user->id)
            ->with(['presenceIncidents' => function ($query) {
                $query->orderBy('incident_date', 'desc');
            }])
            ->first();

        if (!$ue) {
            return response()->json([
                'success' => false,
                'message' => 'UE non trouvée'
            ], 404);
        }

        // Historique des pointages pour cette UE
        $historique = $ue->presenceIncidents->map(function ($incident) {
            return [
                'id' => $incident->id,
                'date' => $incident->incident_date->format('Y-m-d'),
                'heures' => (float) $incident->penalty_hours,
                'status' => $incident->status,
                'campus' => $incident->campus?->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'volume_horaire_total' => (float) $ue->volume_horaire_total,
                'heures_effectuees' => (float) $ue->heures_effectuees,
                'heures_restantes' => (float) $ue->heures_restantes,
                'pourcentage_progression' => (float) $ue->pourcentage_progression,
                'montant_paye' => (float) $ue->montant_paye,
                'montant_restant' => (float) $ue->montant_restant,
                'montant_max' => (float) $ue->montant_max,
                'taux_horaire' => (float) $user->hourly_rate,
                'statut' => $ue->statut,
                'annee_academique' => $ue->annee_academique,
                'semestre' => $ue->semestre,
                'historique_pointages' => $historique,
            ]
        ]);
    }

    /**
     * Liste des UE activées pour le check-in
     * GET /api/unites-enseignement/actives
     */
    public function actives()
    {
        $user = Auth::user();

        // Vérifier que c'est un enseignant vacataire
        if ($user->employee_type !== 'enseignant_vacataire') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux enseignants vacataires'
            ], 403);
        }

        // Uniquement les UE activées avec heures restantes > 0
        $unitesDisponibles = UniteEnseignement::where('vacataire_id', $user->id)
            ->where('statut', 'activee')
            ->get()
            ->filter(function ($ue) {
                return $ue->heures_restantes > 0;
            })
            ->map(function ($ue) use ($user) {
                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'heures_effectuees' => (float) $ue->heures_effectuees,
                    'heures_restantes' => (float) $ue->heures_restantes,
                    'volume_total' => (float) $ue->volume_horaire_total,
                    'pourcentage' => (float) $ue->pourcentage_progression,
                    'taux_horaire' => (float) $user->hourly_rate,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $unitesDisponibles
        ]);
    }

    /**
     * Statistiques globales des UE du vacataire
     * GET /api/unites-enseignement/statistiques
     */
    public function statistiques()
    {
        $user = Auth::user();

        $unitesActivees = UniteEnseignement::where('vacataire_id', $user->id)
            ->where('statut', 'activee')
            ->with('presenceIncidents')
            ->get();

        $totalVolumeHoraire = $unitesActivees->sum('volume_horaire_total');
        $totalHeuresEffectuees = $unitesActivees->sum(function ($ue) {
            return $ue->heures_effectuees;
        });
        $totalHeuresRestantes = $totalVolumeHoraire - $totalHeuresEffectuees;
        $totalMontantPaye = $unitesActivees->sum(function ($ue) {
            return $ue->montant_paye;
        });
        $totalMontantMax = $totalVolumeHoraire * ($user->hourly_rate ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'nombre_ue_activees' => $unitesActivees->count(),
                'volume_horaire_total' => (float) $totalVolumeHoraire,
                'heures_effectuees' => (float) $totalHeuresEffectuees,
                'heures_restantes' => (float) $totalHeuresRestantes,
                'pourcentage_global' => $totalVolumeHoraire > 0
                    ? (float) (($totalHeuresEffectuees / $totalVolumeHoraire) * 100)
                    : 0,
                'montant_paye' => (float) $totalMontantPaye,
                'montant_potentiel_max' => (float) $totalMontantMax,
                'montant_restant' => (float) ($totalMontantMax - $totalMontantPaye),
                'taux_horaire' => (float) $user->hourly_rate,
            ]
        ]);
    }
}
