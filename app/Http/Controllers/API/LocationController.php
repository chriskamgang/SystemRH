<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Mettre à jour la position de l'utilisateur connecté
     * L'app mobile appelle cet endpoint toutes les 30-60 secondes
     *
     * POST /api/location/update
     * Body: { latitude, longitude, accuracy, device_info }
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'device_info' => 'nullable|string|max:255',
        ]);

        try {
            $user = auth()->user();

            // Mettre à jour ou créer la position
            $location = UserLocation::updateOrCreateLocation($user->id, [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'device_info' => $request->device_info ?? $request->header('User-Agent'),
                'is_active' => true,
            ]);

            // Vérifier si l'utilisateur est dans une zone campus
            $campus = $location->isInCampusZone();

            Log::info('User location updated', [
                'user_id' => $user->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'in_campus' => $campus ? $campus->name : 'Outside',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Position mise à jour avec succès',
                'data' => [
                    'location' => $location,
                    'in_campus' => $campus ? [
                        'id' => $campus->id,
                        'name' => $campus->name,
                        'code' => $campus->code,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user location', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la position',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer l'utilisateur comme inactif (app fermée)
     *
     * POST /api/location/deactivate
     */
    public function deactivateLocation(Request $request)
    {
        try {
            $location = UserLocation::where('user_id', auth()->id())->first();

            if ($location) {
                $location->update(['is_active' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Position désactivée',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation',
            ], 500);
        }
    }

    /**
     * Récupérer tous les utilisateurs actifs avec leur position
     * Utilisé par le dashboard admin pour afficher la carte en temps réel
     *
     * GET /api/location/active-users?filter=all|checked_in
     */
    public function getActiveUsers(Request $request)
    {
        try {
            $filter = $request->query('filter', 'all'); // all | checked_in

            // Récupérer les positions actives (mise à jour < 2 minutes)
            $query = UserLocation::active()
                ->with(['user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'email', 'employee_type', 'department_id', 'role_id')
                      ->with(['department:id,name', 'role:id,name']);
                }]);

            // Filtrer par check-in actif si demandé
            if ($filter === 'checked_in') {
                $query->checkedIn();
            }

            $locations = $query->get();

            // Enrichir les données avec les informations de campus
            $enrichedLocations = $locations->map(function ($location) {
                $campus = $location->isInCampusZone();

                return [
                    'id' => $location->id,
                    'user' => [
                        'id' => $location->user->id,
                        'name' => $location->user->full_name,
                        'email' => $location->user->email,
                        'employee_type' => $location->user->employee_type,
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
                    ] : null,
                    'in_zone' => $campus !== null,
                    'last_updated' => $location->last_updated_at->diffForHumans(),
                    'last_updated_timestamp' => $location->last_updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $enrichedLocations,
                    'total' => $enrichedLocations->count(),
                    'filter' => $filter,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get active users locations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des positions',
            ], 500);
        }
    }

    /**
     * Récupérer la dernière position d'un utilisateur spécifique
     *
     * GET /api/location/user/{userId}
     */
    public function getUserLocation($userId)
    {
        try {
            $location = UserLocation::where('user_id', $userId)
                ->with('user')
                ->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune position trouvée pour cet utilisateur',
                ], 404);
            }

            $campus = $location->isInCampusZone();

            return response()->json([
                'success' => true,
                'data' => [
                    'location' => $location,
                    'campus' => $campus,
                    'is_active' => $location->last_updated_at->diffInMinutes(now()) < 2,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la position',
            ], 500);
        }
    }
}
