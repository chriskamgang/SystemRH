<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\Department;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RealTimeTrackingController extends Controller
{
    /**
     * Afficher la page de suivi en temps réel
     */
    public function index(Request $request)
    {
        $campuses = Campus::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $stats = $this->getStats();

        return view('admin.real-time-tracking.index', compact('campuses', 'departments', 'stats'));
    }

    /**
     * API pour récupérer les positions en temps réel
     * Combine: positions GPS actives + check-ins du jour (fallback)
     */
    public function getLocations(Request $request)
    {
        $filter = $request->query('filter', 'all'); // all | checked_in
        $campusId = $request->query('campus_id');
        $departmentId = $request->query('department_id');

        $results = collect();
        $seenUserIds = [];

        // === 1. Positions GPS actives (temps réel) ===
        $query = UserLocation::active()
            ->with(['user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'employee_type', 'department_id', 'role_id')
                  ->with(['department:id,name', 'role:id,name']);
            }]);

        if ($filter === 'checked_in') {
            $query->checkedIn();
        }

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $locations = $query->get();

        foreach ($locations as $location) {
            if (!$location->user) continue;

            $campus = $location->isInCampusZone();

            if ($campusId && (!$campus || $campus->id != $campusId)) {
                continue;
            }

            $seenUserIds[] = $location->user->id;

            $results->push([
                'id' => $location->id,
                'source' => 'gps',
                'user' => [
                    'id' => $location->user->id,
                    'name' => trim($location->user->last_name . ' ' . $location->user->first_name),
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
            ]);
        }

        // === 2. Fallback: check-ins du jour (employés sans GPS actif) ===
        $checkinQuery = Attendance::whereDate('timestamp', today())
            ->where('type', 'check-in')
            ->where('status', 'valid')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotIn('user_id', $seenUserIds)
            ->with(['user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'employee_type', 'department_id', 'role_id')
                  ->with(['department:id,name', 'role:id,name']);
            }, 'campus']);

        if ($departmentId) {
            $checkinQuery->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($campusId) {
            $checkinQuery->where('campus_id', $campusId);
        }

        // Prendre le dernier check-in par utilisateur
        $checkins = $checkinQuery->orderBy('timestamp', 'desc')->get()->unique('user_id');

        // Exclure ceux qui ont fait un check-out après
        foreach ($checkins as $checkin) {
            if (!$checkin->user) continue;

            // Vérifier si l'utilisateur a fait un check-out après ce check-in
            $hasCheckout = Attendance::where('user_id', $checkin->user_id)
                ->whereDate('timestamp', today())
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkin->timestamp)
                ->exists();

            if ($hasCheckout && $filter === 'checked_in') {
                continue; // L'employé a déjà quitté
            }

            $campus = $checkin->campus;

            $results->push([
                'id' => 'checkin-' . $checkin->id,
                'source' => 'checkin',
                'user' => [
                    'id' => $checkin->user->id,
                    'name' => trim($checkin->user->last_name . ' ' . $checkin->user->first_name),
                    'email' => $checkin->user->email,
                    'employee_type' => $checkin->user->employee_type,
                    'employee_type_label' => $this->getEmployeeTypeLabel($checkin->user->employee_type),
                    'department' => $checkin->user->department?->name,
                    'role' => $checkin->user->role?->name,
                ],
                'position' => [
                    'latitude' => (float) $checkin->latitude,
                    'longitude' => (float) $checkin->longitude,
                    'accuracy' => null,
                ],
                'campus' => $campus ? [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'code' => $campus->code,
                    'color' => $this->getCampusColor($campus->id),
                ] : null,
                'in_zone' => $campus !== null,
                'last_updated' => $checkin->timestamp->diffForHumans(),
                'last_updated_timestamp' => $checkin->timestamp->toIso8601String(),
                'marker_color' => $hasCheckout ? 'gray' : 'green',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $results->values(),
                'total' => $results->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * API pour les statistiques en temps réel
     * Combine GPS actif + check-ins du jour
     */
    public function getStats()
    {
        // GPS actif
        $totalGpsActive = UserLocation::active()->count();

        // Check-ins du jour (employés uniques avec check-in valide)
        $todayCheckins = Attendance::whereDate('timestamp', today())
            ->where('type', 'check-in')
            ->where('status', 'valid')
            ->distinct('user_id')
            ->count('user_id');

        // Employés actuellement checked-in (pas encore check-out)
        $activeCheckins = DB::table('attendances as a')
            ->whereDate('a.timestamp', today())
            ->where('a.type', 'check-in')
            ->where('a.status', 'valid')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('attendances as a2')
                  ->whereColumn('a2.user_id', 'a.user_id')
                  ->whereDate('a2.timestamp', today())
                  ->where('a2.type', 'check-out')
                  ->whereColumn('a2.timestamp', '>', 'a.timestamp');
            })
            ->distinct('a.user_id')
            ->count('a.user_id');

        // Employés dans une zone campus (depuis check-ins avec campus_id)
        $totalInZone = Attendance::whereDate('timestamp', today())
            ->where('type', 'check-in')
            ->where('status', 'valid')
            ->whereNotNull('campus_id')
            ->distinct('user_id')
            ->count('user_id');

        $totalOutOfZone = max(0, $todayCheckins - $totalInZone);

        return [
            'total_active' => max($totalGpsActive, $activeCheckins),
            'total_checked_in' => $activeCheckins,
            'total_in_zone' => $totalInZone,
            'total_out_of_zone' => $totalOutOfZone,
        ];
    }

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

    private function getCampusColor($campusId)
    {
        $colors = [
            1 => '#3B82F6',
            2 => '#10B981',
            3 => '#F59E0B',
            4 => '#EF4444',
            5 => '#8B5CF6',
            6 => '#EC4899',
            7 => '#14B8A6',
            8 => '#F97316',
        ];

        return $colors[$campusId] ?? '#6B7280';
    }

    private function getMarkerColor($location, $campus)
    {
        if (!$campus) {
            return 'red';
        }

        $lastAttendanceToday = $location->user->attendances()
            ->whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->first();

        $hasActiveCheckIn = $lastAttendanceToday && $lastAttendanceToday->type === 'check-in';

        if ($hasActiveCheckIn) {
            return 'green';
        }

        return 'blue';
    }
}
