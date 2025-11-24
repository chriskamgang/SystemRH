<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Profil de l'utilisateur connecté
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['role', 'department', 'campuses', 'permissions']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'photo' => $user->photo,
                'employee_type' => $user->employee_type,
                'role' => $user->role,
                'department' => $user->department,
                'campuses' => $user->campuses,
                'permissions' => $user->permissions,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [];

        if ($request->first_name) {
            $data['first_name'] = $request->first_name;
        }

        if ($request->last_name) {
            $data['last_name'] = $request->last_name;
        }

        if ($request->phone) {
            $data['phone'] = $request->phone;
        }

        // Upload de la photo
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $request->file('photo')->store('photos', 'public');
            $data['photo'] = $path;
        }

        if (!empty($data)) {
            $user->update($data);
        }

        $user->load(['role', 'department', 'campuses']);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user,
        ], 200);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Mot de passe changé avec succès.',
        ], 200);
    }

    /**
     * Mettre à jour le token FCM
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'Token FCM mis à jour avec succès.',
        ], 200);
    }

    /**
     * Supprimer le token FCM (lors de la déconnexion)
     */
    public function removeFcmToken(Request $request)
    {
        $user = $request->user();

        $user->update([
            'fcm_token' => null,
        ]);

        return response()->json([
            'message' => 'Token FCM supprimé avec succès.',
        ], 200);
    }

    /**
     * Statistiques globales de l'utilisateur
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Vérifications de présence en attente
        $pendingChecks = $user->presenceChecks()
            ->whereNull('response')
            ->where('check_time', '>=', now()->subDay())
            ->count();

        // Check-in actif aujourd'hui
        $activeCheckIns = $user->attendances()
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->get()
            ->filter(function ($checkIn) use ($user) {
                return !$user->attendances()
                    ->where('campus_id', $checkIn->campus_id)
                    ->where('type', 'check-out')
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->whereDate('timestamp', today())
                    ->exists();
            });

        $hasActiveCheckIn = $activeCheckIns->isNotEmpty();

        // Stats du mois en cours
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthStats = [
            'total_check_ins' => $user->attendances()
                ->where('type', 'check-in')
                ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
                ->count(),
            'total_late' => $user->attendances()
                ->where('type', 'check-in')
                ->where('is_late', true)
                ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
                ->count(),
            'days_worked' => $user->attendances()
                ->where('type', 'check-in')
                ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
                ->get()
                ->map(function ($attendance) {
                    return $attendance->timestamp->format('Y-m-d');
                })
                ->unique()
                ->count(),
        ];

        // Dernier check-in
        $lastCheckIn = $user->attendances()
            ->where('type', 'check-in')
            ->with('campus')
            ->latest('timestamp')
            ->first();

        return response()->json([
            'pending_presence_checks' => $pendingChecks,
            'has_active_checkin' => $hasActiveCheckIn,
            'active_checkins' => $activeCheckIns->load('campus')->values(),
            'month_stats' => $monthStats,
            'last_checkin' => $lastCheckIn,
        ], 200);
    }

    /**
     * Mes campus
     */
    public function myCampuses(Request $request)
    {
        $user = $request->user();
        $campuses = $user->campuses()
            ->with(['departments'])
            ->get()
            ->map(function ($campus) {
                return [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'code' => $campus->code,
                    'address' => $campus->address,
                    'latitude' => $campus->latitude,
                    'longitude' => $campus->longitude,
                    'radius' => $campus->radius,
                    'start_time' => substr($campus->start_time, 0, 5),
                    'end_time' => substr($campus->end_time, 0, 5),
                    'late_tolerance' => $campus->late_tolerance,
                    'is_primary' => $campus->pivot->is_primary,
                    'is_active' => $campus->is_active,
                ];
            });

        return response()->json([
            'campuses' => $campuses,
            'total' => $campuses->count(),
        ], 200);
    }

    /**
     * Mes notifications
     */
    public function notifications(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'is_read' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $query = $user->notifications();

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $perPage = $request->per_page ?? 20;
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'notifications' => $notifications,
        ], 200);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead($id, Request $request)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marquée comme lue.',
            'notification' => $notification,
        ], 200);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $request->user();

        $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues.',
        ], 200);
    }
}
