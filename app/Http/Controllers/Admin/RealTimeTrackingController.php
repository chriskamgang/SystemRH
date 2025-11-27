<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class RealTimeTrackingController extends Controller
{
    /**
     * Afficher la page de suivi en temps réel
     */
    public function index(Request $request)
    {
        // Récupérer les campus pour les filtres
        $campuses = Campus::orderBy('name')->get();

        // Récupérer les départements pour les filtres
        $departments = Department::orderBy('name')->get();

        // Statistiques initiales
        $stats = $this->getStats();

        return view('admin.real-time-tracking.index', compact('campuses', 'departments', 'stats'));
    }

    /**
     * API pour récupérer les positions en temps réel
     * Appelé en AJAX toutes les 10-15 secondes
     */
    public function getLocations(Request $request)
    {
        $filter = $request->query('filter', 'all'); // all | checked_in
        $campusId = $request->query('campus_id');
        $departmentId = $request->query('department_id');

        // Récupérer les positions actives
        $query = UserLocation::active()
            ->with(['user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'employee_type', 'department_id', 'role_id')
                  ->with(['department:id,name', 'role:id,name']);
            }]);

        // Filtrer par check-in actif si demandé
        if ($filter === 'checked_in') {
            $query->checkedIn();
        }

        // Filtrer par département
        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $locations = $query->get();

        // Enrichir les données
        $enrichedLocations = $locations->map(function ($location) use ($campusId) {
            $campus = $location->isInCampusZone();

            // Filtrer par campus si demandé
            if ($campusId && (!$campus || $campus->id != $campusId)) {
                return null;
            }

            return [
                'id' => $location->id,
                'user' => [
                    'id' => $location->user->id,
                    'name' => $location->user->full_name,
                    'email' => $location->user->email,
                    'employee_type' => $location->user->employee_type,
                    'employee_type_label' => $this->getEmployeeTypeLabel($location->user->employee_type),
                    'department' => $location->user->department?->name,
                    'role' => $location->user->role?->name,
                ],
                'position' => [
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'accuracy' => $location->accuracy ? (float) $location->accuracy : null,
                ],
                'campus' => $campus ? [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'code' => $campus->code,
                    'color' => $this->getCampusColor($campus->id),
                ] : null,
                'in_zone' => $campus !== null,
                'last_updated' => $location->last_updated_at->diffForHumans(),
                'last_updated_timestamp' => $location->last_updated_at->toIso8601String(),
                'marker_color' => $this->getMarkerColor($location, $campus),
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $enrichedLocations,
                'total' => $enrichedLocations->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * API pour les statistiques en temps réel
     */
    public function getStats()
    {
        $totalActive = UserLocation::active()->count();
        $totalCheckedIn = UserLocation::active()->checkedIn()->count();
        $totalInZone = UserLocation::active()->get()->filter(function ($location) {
            return $location->isInCampusZone() !== null;
        })->count();
        $totalOutOfZone = $totalActive - $totalInZone;

        return [
            'total_active' => $totalActive,
            'total_checked_in' => $totalCheckedIn,
            'total_in_zone' => $totalInZone,
            'total_out_of_zone' => $totalOutOfZone,
        ];
    }

    /**
     * Obtenir le label du type d'employé
     */
    private function getEmployeeTypeLabel($type)
    {
        $labels = [
            'enseignant_titulaire' => 'Enseignant Titulaire',
            'enseignant_vacataire' => 'Enseignant Vacataire',
            'semi_permanent' => 'Semi-permanent',
            'administratif' => 'Administratif',
            'technique' => 'Technique',
            'direction' => 'Direction',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Obtenir la couleur du campus
     */
    private function getCampusColor($campusId)
    {
        $colors = [
            1 => '#3B82F6', // Bleu
            2 => '#10B981', // Vert
            3 => '#F59E0B', // Orange
            4 => '#EF4444', // Rouge
            5 => '#8B5CF6', // Violet
            6 => '#EC4899', // Rose
        ];

        return $colors[$campusId] ?? '#6B7280'; // Gris par défaut
    }

    /**
     * Obtenir la couleur du marqueur selon l'état
     */
    private function getMarkerColor($location, $campus)
    {
        if (!$campus) {
            return 'red'; // Hors zone
        }

        // Vérifier si l'utilisateur a un check-in actif
        // Un check-in est actif si le dernier pointage d'aujourd'hui est de type 'check-in'
        $lastAttendanceToday = $location->user->attendances()
            ->whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->first();

        $hasActiveCheckIn = $lastAttendanceToday && $lastAttendanceToday->type === 'check-in';

        if ($hasActiveCheckIn) {
            return 'green'; // Check-in actif et dans la zone
        }

        return 'blue'; // Dans la zone mais pas de check-in
    }
}
