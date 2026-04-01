<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PresenceIncident;
use App\Models\NotificationSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresenceAlertController extends Controller
{
    /**
     * Page principale - Liste des incidents
     * GET /admin/presence-alerts
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $search = $request->get('search');
        $campusId = $request->get('campus_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = PresenceIncident::with(['user', 'campus', 'validator'])
            ->orderBy('incident_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        if ($dateFrom) {
            $query->whereDate('incident_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('incident_date', '<=', $dateTo);
        }

        $incidents = $query->paginate(20);

        $pendingCount = PresenceIncident::where('status', 'pending')->count();

        return view('admin.presence-alerts.index', compact('incidents', 'pendingCount', 'status'));
    }

    /**
     * Page de configuration
     * GET /admin/presence-alerts/settings
     */
    public function settings()
    {
        $settings = NotificationSetting::getSettings();

        $workSchedule = [
            'morning_start_time' => Setting::get('morning_start_time', '08:15'),
            'morning_end_time' => Setting::get('morning_end_time', '17:00'),
            'late_tolerance' => Setting::get('late_tolerance', '15'),
        ];

        return view('admin.presence-alerts.settings', compact('settings', 'workSchedule'));
    }

    /**
     * Mettre à jour la configuration
     * POST /admin/presence-alerts/settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permanent_semi_permanent_time' => 'required|date_format:H:i',
            'temporary_time' => 'required|date_format:H:i',
            'response_delay_minutes' => 'required|integer|min:5|max:180',
            'penalty_hours' => 'required|numeric|min:0.25|max:24',
            'is_active' => 'boolean',
            'break_enabled' => 'boolean',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'morning_start_time' => 'required|date_format:H:i',
            'morning_end_time' => 'required|date_format:H:i',
            'late_tolerance' => 'required|integer|min:0|max:60',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Sauvegarder les horaires de travail
        Setting::set('morning_start_time', $request->morning_start_time);
        Setting::set('morning_end_time', $request->morning_end_time);
        Setting::set('late_tolerance', $request->late_tolerance);

        $settings = NotificationSetting::getSettings();

        $updateData = [
            'permanent_semi_permanent_time' => $request->permanent_semi_permanent_time . ':00',
            'temporary_time' => $request->temporary_time . ':00',
            'response_delay_minutes' => $request->response_delay_minutes,
            'penalty_hours' => $request->penalty_hours,
            'is_active' => $request->has('is_active'),
            'break_enabled' => $request->has('break_enabled'),
        ];

        if ($request->break_start_time) {
            $updateData['break_start_time'] = $request->break_start_time . ':00';
        }
        if ($request->break_end_time) {
            $updateData['break_end_time'] = $request->break_end_time . ':00';
        }

        $settings->update($updateData);

        // Sauvegarder les paramètres de rappels de cours
        Setting::set('course_reminders_enabled', $request->has('course_reminders_enabled') ? '1' : '0');
        if ($request->course_reminder_minutes) {
            Setting::set('course_reminder_minutes', $request->course_reminder_minutes);
        }

        return back()->with('success', 'Paramètres mis à jour avec succès');
    }

    /**
     * Détails d'un incident
     * GET /admin/presence-alerts/{id}
     */
    public function show($id)
    {
        $incident = PresenceIncident::with(['user', 'campus', 'attendance', 'validator'])
            ->findOrFail($id);

        return view('admin.presence-alerts.show', compact('incident'));
    }

    /**
     * Valider un incident (appliquer la pénalité)
     * POST /admin/presence-alerts/{id}/validate
     */
    public function validate(Request $request, $id)
    {
        $incident = PresenceIncident::findOrFail($id);

        if (!$incident->isPending()) {
            return back()->with('error', 'Cet incident a déjà été traité');
        }

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $incident->validateIncident(auth()->id(), $request->admin_notes);

        return back()->with('success', 'Incident validé. La pénalité sera appliquée au salaire de l\'employé.');
    }

    /**
     * Ignorer un incident
     * POST /admin/presence-alerts/{id}/ignore
     */
    public function ignore(Request $request, $id)
    {
        $incident = PresenceIncident::findOrFail($id);

        if (!$incident->isPending()) {
            return back()->with('error', 'Cet incident a déjà été traité');
        }

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $incident->ignoreIncident(auth()->id(), $request->admin_notes);

        return back()->with('success', 'Incident ignoré. Aucune pénalité ne sera appliquée.');
    }

    /**
     * Statistiques globales
     * GET /admin/presence-alerts/statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $stats = [
            'total_incidents' => PresenceIncident::whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'pending' => PresenceIncident::where('status', 'pending')->whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'validated' => PresenceIncident::where('status', 'validated')->whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'ignored' => PresenceIncident::where('status', 'ignored')->whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'responded' => PresenceIncident::where('has_responded', true)->whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'not_responded' => PresenceIncident::where('has_responded', false)->whereBetween('incident_date', [$dateFrom, $dateTo])->count(),
            'total_penalty_hours' => PresenceIncident::where('status', 'validated')->whereBetween('incident_date', [$dateFrom, $dateTo])->sum('penalty_hours'),
        ];

        // Top 10 employés avec le plus d'incidents
        $topUsers = PresenceIncident::selectRaw('user_id, COUNT(*) as incident_count')
            ->whereBetween('incident_date', [$dateFrom, $dateTo])
            ->groupBy('user_id')
            ->orderBy('incident_count', 'desc')
            ->limit(10)
            ->with('user')
            ->get();

        return view('admin.presence-alerts.statistics', compact('stats', 'topUsers', 'dateFrom', 'dateTo'));
    }

    /**
     * API: Récupérer les incidents (pour AJAX)
     * GET /admin/api/presence-alerts/incidents
     */
    public function apiGetIncidents(Request $request)
    {
        $status = $request->get('status', 'pending');

        $incidents = PresenceIncident::with(['user', 'campus'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('incident_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'incidents' => $incidents,
        ]);
    }

    /**
     * API: Récupérer le nombre d'incidents pending (pour badge)
     * GET /admin/api/presence-alerts/pending-count
     */
    public function apiGetPendingCount()
    {
        $count = PresenceIncident::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
