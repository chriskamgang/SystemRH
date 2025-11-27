<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DeviceUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required|string',
            'device_model' => 'nullable|string',
            'device_os' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            return response()->json([
                'message' => "Votre compte est désactivé. Contactez l'administrateur.",
            ], 403);
        }

        // Vérifier si ce téléphone a déjà été utilisé par un autre employé aujourd'hui
        $todayUsage = DeviceUsage::where('device_id', $request->device_id)
            ->whereDate('usage_date', Carbon::today())
            ->first();

        if ($todayUsage && $todayUsage->user_id !== $user->id) {
            $otherUser = User::find($todayUsage->user_id);
            return response()->json([
                'message' => "Ce téléphone a déjà été utilisé par " . ($otherUser ? $otherUser->full_name : 'un autre employé') . " aujourd'hui. Pour des raisons de sécurité, un téléphone ne peut être utilisé que par une seule personne par jour.",
                'error_code' => 'DEVICE_ALREADY_USED_TODAY',
                'used_by' => $otherUser ? $otherUser->full_name : 'Un autre employé',
            ], 403);
        }

        // Enregistrer l'utilisation du téléphone pour aujourd'hui (uniquement si pas déjà enregistré)
        if (!$todayUsage) {
            DeviceUsage::create([
                'device_id' => $request->device_id,
                'usage_date' => Carbon::today(),
                'user_id' => $user->id,
                'device_model' => $request->device_model,
                'device_os' => $request->device_os,
            ]);
        }

        // Mettre à jour les informations de l'appareil de l'utilisateur
        $user->update([
            'device_id' => $request->device_id,
            'device_model' => $request->device_model,
            'device_os' => $request->device_os,
        ]);

        // Créer un token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Charger les relations
        $user->load(['role', 'department', 'campuses', 'permissions']);

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'photo' => $user->photo,
                'employee_type' => $user->employee_type,
                'custom_start_time' => $user->custom_start_time,
                'custom_end_time' => $user->custom_end_time,
                'custom_late_tolerance' => $user->custom_late_tolerance,
                'has_custom_hours' => $user->hasCustomWorkHours(),
                'role' => $user->role,
                'department' => $user->department,
                'campuses' => $user->campuses,
                'permissions' => $user->permissions,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
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
                'custom_start_time' => $user->custom_start_time,
                'custom_end_time' => $user->custom_end_time,
                'custom_late_tolerance' => $user->custom_late_tolerance,
                'has_custom_hours' => $user->hasCustomWorkHours(),
                'role' => $user->role,
                'department' => $user->department,
                'campuses' => $user->campuses,
                'permissions' => $user->permissions,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }
}
