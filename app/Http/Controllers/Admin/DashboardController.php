<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_employees' => User::where('role_id', '!=', 1)->count(),
            'active_employees' => User::where('role_id', '!=', 1)->where('is_active', true)->count(),
            'present_today' => Attendance::where('type', 'check-in')
                ->whereDate('timestamp', today())
                ->distinct('user_id')
                ->count(),
            'late_this_month' => Attendance::where('type', 'check-in')
                ->where('is_late', true)
                ->whereMonth('timestamp', now()->month)
                ->count(),
            'total_campuses' => Campus::count(),
            'active_campuses' => Campus::where('is_active', true)->count(),
        ];

        // Calculer le taux de retard
        $totalCheckIns = Attendance::where('type', 'check-in')
            ->whereMonth('timestamp', now()->month)
            ->count();
        $stats['late_rate'] = $totalCheckIns > 0
            ? round(($stats['late_this_month'] / $totalCheckIns) * 100, 1)
            : 0;

        // Derniers check-ins
        $recent_checkins = Attendance::with(['user', 'campus'])
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get();

        // Retards d'aujourd'hui
        $late_today = Attendance::with(['user', 'campus'])
            ->where('type', 'check-in')
            ->where('is_late', true)
            ->whereDate('timestamp', today())
            ->orderBy('late_minutes', 'desc')
            ->get();

        // Campus avec comptage
        $campuses = Campus::withCount([
            'attendances as present_count' => function ($query) {
                $query->where('type', 'check-in')
                    ->whereDate('timestamp', today())
                    ->distinct('user_id');
            },
            'users as total_employees'
        ])->get();

        // Données pour graphique des 7 derniers jours
        $chart_data = [
            'labels' => [],
            'checkins' => [],
            'late' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chart_data['labels'][] = $date->format('d/m');

            $checkins = Attendance::where('type', 'check-in')
                ->whereDate('timestamp', $date)
                ->count();
            $chart_data['checkins'][] = $checkins;

            $late = Attendance::where('type', 'check-in')
                ->where('is_late', true)
                ->whereDate('timestamp', $date)
                ->count();
            $chart_data['late'][] = $late;
        }

        // Données pour graphique par campus
        $campus_chart = [
            'labels' => [],
            'data' => []
        ];

        foreach ($campuses as $campus) {
            $campus_chart['labels'][] = $campus->name;
            $campus_chart['data'][] = $campus->present_count;
        }

        return view('admin.dashboard', compact(
            'stats',
            'recent_checkins',
            'late_today',
            'campuses',
            'chart_data',
            'campus_chart'
        ));
    }

    /**
     * Display the real-time attendance map.
     */
    public function realtime()
    {
        $campuses = Campus::where('is_active', true)->get();

        // Get active check-ins (people who checked in today but haven't checked out yet)
        $activeCheckIns = Attendance::with(['user', 'campus'])
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->whereNotIn('user_id', function ($query) {
                $query->select('user_id')
                    ->from('attendances')
                    ->where('type', 'check-out')
                    ->whereDate('timestamp', today());
            })
            ->orderBy('timestamp', 'desc')
            ->get();

        return view('admin.realtime', compact('campuses', 'activeCheckIns'));
    }

    /**
     * Get active check-ins data (API endpoint for real-time updates).
     */
    public function activeCheckIns()
    {
        $activeCheckIns = Attendance::with(['user', 'campus'])
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->whereNotIn('user_id', function ($query) {
                $query->select('user_id')
                    ->from('attendances')
                    ->where('type', 'check-out')
                    ->whereDate('timestamp', today());
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'user_name' => $attendance->user->full_name,
                    'campus_name' => $attendance->campus->name,
                    'timestamp' => $attendance->timestamp->format('H:i'),
                    'is_late' => $attendance->is_late,
                    'late_minutes' => $attendance->late_minutes,
                    'latitude' => $attendance->latitude,
                    'longitude' => $attendance->longitude,
                ];
            });

        return response()->json($activeCheckIns);
    }

    /**
     * Display the settings page.
     */
    public function settings()
    {
        $roles = \App\Models\Role::all();
        return view('admin.settings', compact('roles'));
    }

    /**
     * Update the settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'map_provider' => 'sometimes|in:openstreetmap,google',
            'google_maps_api_key' => 'nullable|string|max:255',
            'app_name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|email|max:255',
            'timezone' => 'sometimes|string|max:50',
            'maintenance_mode' => 'sometimes|boolean',
            'check_interval' => 'sometimes|integer|min:15|max:1440',
            'auto_checkout_time' => 'sometimes|date_format:H:i',
            'send_reminders' => 'sometimes|boolean',
            'send_late_alerts' => 'sometimes|boolean',
            'default_late_tolerance' => 'sometimes|integer|min:0|max:60',
            // Paramètres de paie
            'penalty_per_second' => 'sometimes|numeric|min:0',
            'working_hours_per_day' => 'sometimes|numeric|min:1|max:24',
            'saturday_working_hours' => 'sometimes|numeric|min:0|max:12',
        ]);

        // Save map provider
        if ($request->has('map_provider')) {
            \App\Models\Setting::set('map_provider', $request->map_provider);
        }

        // Save Google Maps API key
        if ($request->has('google_maps_api_key')) {
            \App\Models\Setting::set('google_maps_api_key', $request->google_maps_api_key);
        }

        // Save other settings
        foreach ($request->except(['_token', '_method', 'map_provider', 'google_maps_api_key']) as $key => $value) {
            if ($value !== null) {
                \App\Models\Setting::set($key, $value);
            }
        }

        return redirect()->back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Store a new role.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        \App\Models\Role::create($validated);

        return redirect()->back()->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Request $request, $id)
    {
        $role = \App\Models\Role::findOrFail($id);

        // Ne pas autoriser la modification du rôle admin
        if ($role->id == 1) {
            return redirect()->back()->with('error', 'Le rôle administrateur ne peut pas être modifié.');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $role->update($validated);

        return redirect()->back()->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Delete a role.
     */
    public function deleteRole($id)
    {
        $role = \App\Models\Role::findOrFail($id);

        // Ne pas autoriser la suppression du rôle admin
        if ($role->id == 1) {
            return redirect()->back()->with('error', 'Le rôle administrateur ne peut pas être supprimé.');
        }

        // Vérifier si des utilisateurs ont ce rôle
        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Ce rôle ne peut pas être supprimé car des utilisateurs l\'utilisent.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Rôle supprimé avec succès.');
    }
}
