<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    /**
     * Liste de tous les campus
     */
    public function index(Request $request)
    {
        $request->validate([
            'is_active' => 'nullable|boolean',
        ]);

        $query = Campus::query()->with(['departments']);

        // Filtrer par statut actif
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $campuses = $query->orderBy('name')->get();

        return response()->json([
            'campuses' => $campuses,
            'total' => $campuses->count(),
        ], 200);
    }

    /**
     * Détails d'un campus spécifique
     */
    public function show($id)
    {
        $campus = Campus::with(['departments'])->findOrFail($id);

        return response()->json([
            'campus' => $campus,
        ], 200);
    }

    /**
     * Campus assignés à l'utilisateur connecté
     */
    public function myCampuses(Request $request)
    {
        $user = $request->user();
        $campuses = $user->campuses()->with(['departments'])->get();

        return response()->json([
            'campuses' => $campuses,
            'total' => $campuses->count(),
        ], 200);
    }

    /**
     * Vérifier si l'utilisateur est dans une zone de campus
     */
    public function checkZone(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        $user = $request->user();

        // Si un campus spécifique est demandé
        if ($request->campus_id) {
            $campus = Campus::findOrFail($request->campus_id);

            // Vérifier si l'utilisateur est assigné à ce campus
            if (!$user->campuses->contains($campus->id)) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas assigné à ce campus.',
                    'in_zone' => false,
                ], 403);
            }

            $inZone = $campus->isUserInZone($request->latitude, $request->longitude);

            return response()->json([
                'campus' => [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'code' => $campus->code,
                ],
                'in_zone' => $inZone,
                'radius' => $campus->radius,
                'message' => $inZone
                    ? 'Vous êtes dans la zone du campus.'
                    : 'Vous n\'êtes pas dans la zone du campus.',
            ], 200);
        }

        // Sinon, vérifier tous les campus de l'utilisateur
        $userCampuses = $user->campuses;
        $results = [];

        foreach ($userCampuses as $campus) {
            $inZone = $campus->isUserInZone($request->latitude, $request->longitude);

            $results[] = [
                'campus' => [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'code' => $campus->code,
                ],
                'in_zone' => $inZone,
                'radius' => $campus->radius,
            ];
        }

        // Trouver les campus où l'utilisateur est actuellement
        $inZoneCampuses = collect($results)->filter(fn($r) => $r['in_zone'])->values();

        return response()->json([
            'all_campuses' => $results,
            'in_zone_campuses' => $inZoneCampuses,
            'is_in_any_zone' => $inZoneCampuses->isNotEmpty(),
            'total_campuses_checked' => count($results),
        ], 200);
    }

    /**
     * Calculer la distance entre l'utilisateur et un campus
     */
    public function calculateDistance(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $campus = Campus::findOrFail($request->campus_id);

        // Formule de Haversine pour calculer la distance
        $earthRadius = 6371000; // en mètres

        $latFrom = deg2rad($campus->latitude);
        $lonFrom = deg2rad($campus->longitude);
        $latTo = deg2rad($request->latitude);
        $lonTo = deg2rad($request->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        return response()->json([
            'campus' => [
                'id' => $campus->id,
                'name' => $campus->name,
                'latitude' => $campus->latitude,
                'longitude' => $campus->longitude,
                'radius' => $campus->radius,
            ],
            'user_location' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ],
            'distance_meters' => round($distance, 2),
            'distance_kilometers' => round($distance / 1000, 2),
            'in_zone' => $distance <= $campus->radius,
            'distance_from_zone' => $distance > $campus->radius
                ? round($distance - $campus->radius, 2)
                : 0,
        ], 200);
    }

    /**
     * Horaires d'un campus
     */
    public function schedule($id)
    {
        $campus = Campus::findOrFail($id);

        return response()->json([
            'campus' => [
                'id' => $campus->id,
                'name' => $campus->name,
            ],
            'schedule' => [
                'start_time' => $campus->start_time,
                'end_time' => $campus->end_time,
                'late_tolerance' => $campus->late_tolerance,
                'working_days' => $campus->working_days,
            ],
            'formatted' => [
                'start_time' => substr($campus->start_time, 0, 5),
                'end_time' => substr($campus->end_time, 0, 5),
                'late_tolerance_text' => $campus->late_tolerance . ' minutes',
            ],
        ], 200);
    }

    /**
     * Statistiques d'un campus (pour admin/responsable)
     */
    public function stats($id, Request $request)
    {
        $user = $request->user();
        $campus = Campus::findOrFail($id);

        // Vérifier les permissions
        if (!$user->hasPermission('view_campus_stats') && !$user->hasPermission('view_all_data')) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de voir ces statistiques.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        // Nombre total d'employés assignés
        $totalEmployees = $campus->users()->count();

        // Pointages dans la période
        $totalCheckIns = $campus->attendances()
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        // Retards
        $totalLate = $campus->attendances()
            ->where('type', 'check-in')
            ->where('is_late', true)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        // Taux de ponctualité
        $onTimeRate = $totalCheckIns > 0
            ? round((($totalCheckIns - $totalLate) / $totalCheckIns) * 100, 2)
            : 0;

        return response()->json([
            'campus' => [
                'id' => $campus->id,
                'name' => $campus->name,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => [
                'total_employees' => $totalEmployees,
                'total_check_ins' => $totalCheckIns,
                'total_late' => $totalLate,
                'on_time_rate' => $onTimeRate,
            ],
        ], 200);
    }
}
