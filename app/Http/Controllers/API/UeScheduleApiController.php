<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UeSchedule;
use App\Models\UniteEnseignement;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UeScheduleApiController extends Controller
{
    /**
     * Mon emploi du temps complet de la semaine
     */
    public function monEmploi(Request $request)
    {
        $user = $request->user();
        $ueIds = UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->pluck('id');

        $schedules = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->validNow()
            ->with(['uniteEnseignement', 'campus'])
            ->orderByRaw("FIELD(jour_semaine, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')")
            ->orderBy('heure_debut')
            ->get();

        $grouped = $schedules->groupBy('jour_semaine')->map(function ($items) {
            return $items->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'jour_semaine' => $schedule->jour_semaine,
                    'heure_debut' => substr($schedule->heure_debut, 0, 5),
                    'heure_fin' => substr($schedule->heure_fin, 0, 5),
                    'salle' => $schedule->salle,
                    'duree_heures' => $schedule->duree_en_heures,
                    'ue' => [
                        'id' => $schedule->uniteEnseignement->id,
                        'code_ue' => $schedule->uniteEnseignement->code_ue,
                        'nom_matiere' => $schedule->uniteEnseignement->nom_matiere,
                    ],
                    'campus' => [
                        'id' => $schedule->campus->id,
                        'name' => $schedule->campus->name,
                    ],
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Créneaux d'aujourd'hui seulement
     */
    public function aujourdhui(Request $request)
    {
        $user = $request->user();
        $jourActuel = UeSchedule::getCurrentDayFr();

        $ueIds = UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->pluck('id');

        $schedules = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->validNow()
            ->where('jour_semaine', $jourActuel)
            ->with(['uniteEnseignement', 'campus'])
            ->orderBy('heure_debut')
            ->get();

        return response()->json([
            'success' => true,
            'jour' => $jourActuel,
            'data' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'jour_semaine' => $schedule->jour_semaine,
                    'heure_debut' => substr($schedule->heure_debut, 0, 5),
                    'heure_fin' => substr($schedule->heure_fin, 0, 5),
                    'salle' => $schedule->salle,
                    'duree_heures' => $schedule->duree_en_heures,
                    'ue' => [
                        'id' => $schedule->uniteEnseignement->id,
                        'code_ue' => $schedule->uniteEnseignement->code_ue,
                        'nom_matiere' => $schedule->uniteEnseignement->nom_matiere,
                        'heures_restantes' => $schedule->uniteEnseignement->heures_restantes,
                        'volume_horaire_total' => $schedule->uniteEnseignement->volume_horaire_total,
                        'pourcentage_progression' => $schedule->uniteEnseignement->pourcentage_progression,
                    ],
                    'campus' => [
                        'id' => $schedule->campus->id,
                        'name' => $schedule->campus->name,
                    ],
                ];
            }),
        ]);
    }

    /**
     * UE disponibles maintenant (dans le créneau + tolérance)
     */
    public function uesDisponiblesMaintenant(Request $request)
    {
        $user = $request->user();
        $jourActuel = UeSchedule::getCurrentDayFr();
        $toleranceMinutes = (int) Setting::get('schedule_tolerance_minutes', '15');

        $ueIds = UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->pluck('id');

        // Récupérer les créneaux d'aujourd'hui (valides pour la période actuelle)
        $schedules = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->validNow()
            ->where('jour_semaine', $jourActuel)
            ->with(['uniteEnseignement', 'campus'])
            ->orderBy('heure_debut')
            ->get();

        // Filtrer ceux qui sont actuellement dans le créneau (avec tolérance)
        $now = Carbon::now();
        $currentTime = Carbon::parse($now->format('H:i:s'));

        $disponibles = $schedules->filter(function ($schedule) use ($currentTime, $toleranceMinutes) {
            $debut = Carbon::parse($schedule->heure_debut)->subMinutes($toleranceMinutes);
            $fin = Carbon::parse($schedule->heure_fin)->addMinutes($toleranceMinutes);
            return $currentTime->between($debut, $fin);
        });

        // Si aucune UE avec emploi du temps n'est disponible, inclure aussi les UE sans emploi du temps (rétrocompatibilité)
        $ueIdsAvecSchedule = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->pluck('unite_enseignement_id')
            ->unique();

        $uesSansSchedule = UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->whereNotIn('id', $ueIdsAvecSchedule)
            ->where(function ($q) {
                // Vérifier qu'il reste des heures
                $q->whereRaw('volume_horaire_total > COALESCE(heures_effectuees_validees, 0)');
            })
            ->get();

        $result = [];

        // UE avec créneaux actifs maintenant
        foreach ($disponibles as $schedule) {
            $ue = $schedule->uniteEnseignement;
            if ($ue->heures_restantes <= 0) continue;

            $result[] = [
                'id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'volume_horaire_total' => $ue->volume_horaire_total,
                'heures_effectuees' => $ue->heures_effectuees,
                'heures_restantes' => $ue->heures_restantes,
                'pourcentage_progression' => $ue->pourcentage_progression,
                'montant_paye' => $ue->montant_paye,
                'montant_restant' => $ue->montant_restant,
                'montant_max' => $ue->montant_max,
                'taux_horaire' => $ue->enseignant->hourly_rate ?? 0,
                'statut' => $ue->statut,
                'annee_academique' => $ue->annee_academique,
                'semestre' => $ue->semestre,
                'schedule' => [
                    'heure_debut' => substr($schedule->heure_debut, 0, 5),
                    'heure_fin' => substr($schedule->heure_fin, 0, 5),
                    'salle' => $schedule->salle,
                    'campus_id' => $schedule->campus_id,
                    'campus_name' => $schedule->campus->name,
                ],
            ];
        }

        // UE sans emploi du temps (rétrocompatibilité)
        foreach ($uesSansSchedule as $ue) {
            if ($ue->heures_restantes <= 0) continue;

            $result[] = [
                'id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'volume_horaire_total' => $ue->volume_horaire_total,
                'heures_effectuees' => $ue->heures_effectuees,
                'heures_restantes' => $ue->heures_restantes,
                'pourcentage_progression' => $ue->pourcentage_progression,
                'montant_paye' => $ue->montant_paye,
                'montant_restant' => $ue->montant_restant,
                'montant_max' => $ue->montant_max,
                'taux_horaire' => $ue->enseignant->hourly_rate ?? 0,
                'statut' => $ue->statut,
                'annee_academique' => $ue->annee_academique,
                'semestre' => $ue->semestre,
                'schedule' => null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
