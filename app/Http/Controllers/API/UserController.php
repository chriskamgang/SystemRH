<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\Task;
use App\Models\UniteEnseignement;
use App\Models\UeSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                'address' => $user->address,
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
                'banque' => $user->banque,
                'numero_compte' => $user->numero_compte,
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
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'banque' => 'nullable|string|max:100',
            'numero_compte' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $fields = ['first_name', 'last_name', 'email', 'phone', 'address',
                    'emergency_contact_name', 'emergency_contact_phone', 'banque', 'numero_compte'];

        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field) && $request->$field !== null) {
                $data[$field] = $request->$field;
            }
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

        // Charger tous les pointages du jour en une seule requête
        $todayAttendances = $user->attendances()
            ->whereDate('timestamp', today())
            ->with('campus')
            ->orderBy('timestamp', 'asc')
            ->get();

        // Check-ins actifs (aujourd'hui + hier pour détecter les orphelins cross-jour)
        $recentAttendances = $user->attendances()
            ->where('timestamp', '>=', today()->subDay())
            ->with('campus')
            ->get();

        $recentCheckOuts = $recentAttendances->where('type', 'check-out');

        $activeCheckIns = $recentAttendances->where('type', 'check-in')
            ->filter(function ($checkIn) use ($recentCheckOuts) {
                return !$recentCheckOuts
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->isNotEmpty();
            });

        $hasActiveCheckIn = $activeCheckIns->isNotEmpty();

        // Stats du mois - une seule requête
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthCheckIns = $user->attendances()
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
            ->get(['timestamp', 'is_late']);

        $monthStats = [
            'total_check_ins' => $monthCheckIns->count(),
            'total_late' => $monthCheckIns->where('is_late', true)->count(),
            'days_worked' => $monthCheckIns->map(fn($a) => $a->timestamp->format('Y-m-d'))->unique()->count(),
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
     * Toutes les données du tableau de bord en une seule requête
     * GET /api/user/home-data
     */
    public function homeData(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $isTeacher = in_array($user->employee_type, ['enseignant_vacataire', 'semi_permanent', 'enseignant_titulaire']);

        // ── Attendances du jour ───────────────────────────────────────────────
        $todayAttendances = $user->attendances()
            ->whereDate('timestamp', today())
            ->with('campus')
            ->orderBy('timestamp', 'asc')
            ->get();

        // ── Check-ins actifs (aujourd'hui + hier pour détecter les orphelins cross-jour) ──
        $recentAttendances = $user->attendances()
            ->where('timestamp', '>=', today()->subDay())
            ->with('campus')
            ->get();

        $recentCheckOuts = $recentAttendances->where('type', 'check-out');

        $activeCheckIns = $recentAttendances->where('type', 'check-in')
            ->filter(function ($checkIn) use ($recentCheckOuts) {
                return !$recentCheckOuts
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->isNotEmpty();
            });

        // ── Stats du mois ─────────────────────────────────────────────────────
        $monthCheckIns = $user->attendances()
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['timestamp', 'is_late']);

        // ── Campus (cachés 30 min — données statiques) ────────────────────────
        $campuses = Cache::remember("user_{$user->id}_campuses", 1800, function () use ($user) {
            return $user->campuses()->get()->map(function ($campus) {
                return [
                    'id'             => $campus->id,
                    'name'           => $campus->name,
                    'code'           => $campus->code,
                    'address'        => $campus->address,
                    'latitude'       => $campus->latitude,
                    'longitude'      => $campus->longitude,
                    'radius'         => $campus->radius,
                    'start_time'     => substr($campus->start_time, 0, 5),
                    'end_time'       => substr($campus->end_time, 0, 5),
                    'late_tolerance' => $campus->late_tolerance,
                    'is_primary'     => $campus->pivot->is_primary,
                    'is_active'      => $campus->is_active,
                ];
            })->values()->all();
        });

        // ── Tâches ────────────────────────────────────────────────────────────
        $tasks = $user->tasks()
            ->with('creator:id,first_name,last_name')
            ->orderByRaw("CASE WHEN task_user.status = 'pending' THEN 0 WHEN task_user.status = 'in_progress' THEN 1 ELSE 2 END")
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($task) {
                return [
                    'id'               => $task->id,
                    'title'            => $task->title,
                    'description'      => $task->description,
                    'priority'         => $task->priority,
                    'status'           => $task->status,
                    'penalty_amount'   => (int) ($task->pivot->penalty_amount ?? 0),
                    'my_status'        => $task->pivot->status,
                    'my_note'          => $task->pivot->note,
                    'completed_at'     => $task->pivot->completed_at,
                    'penalty_approved' => (bool) $task->pivot->penalty_approved,
                    'due_date'         => $task->due_date?->format('Y-m-d'),
                    'creator_name'     => $task->creator ? $task->creator->full_name : null,
                    'created_at'       => $task->created_at->toIso8601String(),
                ];
            });

        // ── Pause ─────────────────────────────────────────────────────────────
        $activeBreak   = BreakLog::where('user_id', $user->id)->where('date', $today)->whereNull('break_end')->first();
        $todayBreaks   = BreakLog::where('user_id', $user->id)->where('date', $today)->orderBy('break_start')->get();

        $breakData = [
            'on_break'    => $activeBreak !== null,
            'active_break' => $activeBreak ? [
                'break_id'         => $activeBreak->id,
                'break_start'      => $activeBreak->break_start->format('H:i'),
                'elapsed_minutes'  => $activeBreak->break_start->diffInMinutes(now()),
            ] : null,
            'total_break_minutes' => $todayBreaks->sum('duration_minutes'),
        ];

        // ── Vérifications de présence ─────────────────────────────────────────
        $pendingChecks = $user->presenceChecks()
            ->whereNull('response')
            ->where('check_time', '>=', now()->subDay())
            ->count();

        // ── Dernier check-in (reutiliser les donnees du jour deja chargees) ──
        $lastCheckIn = $todayAttendances->where('type', 'check-in')->sortByDesc('timestamp')->first()
            ?? $user->attendances()
                ->where('type', 'check-in')
                ->with('campus')
                ->latest('timestamp')
                ->first();

        // ── UE & Emploi du temps (enseignants seulement) ──────────────────────
        $ueData       = null;
        $todaySchedule = null;

        if ($isTeacher) {
            // Charger toutes les UE en une seule requete (au lieu de 3)
            $allUes = UniteEnseignement::where('enseignant_id', $user->id)
                ->orderBy('nom_matiere')
                ->get();

            $tauxHoraire = (float) ($user->hourly_rate ?? 0);

            $unitesActivees = $allUes->where('statut', 'activee')->map(function ($ue) use ($tauxHoraire) {
                    $heuresValidees = (float) $ue->heures_effectuees_validees;
                    return [
                        'id'                    => $ue->id,
                        'code_ue'               => $ue->code_ue,
                        'nom_matiere'           => $ue->nom_matiere,
                        'volume_horaire_total'  => (float) $ue->volume_horaire_total,
                        'heures_effectuees'     => $heuresValidees,
                        'heures_restantes'      => max(0, (float) $ue->volume_horaire_total - $heuresValidees),
                        'pourcentage_progression' => (float) $ue->pourcentage_progression_validees,
                        'montant_paye'          => $heuresValidees * $tauxHoraire,
                        'montant_restant'       => max(0, ((float) $ue->volume_horaire_total - $heuresValidees) * $tauxHoraire),
                        'montant_max'           => (float) $ue->montant_max,
                        'taux_horaire'          => $tauxHoraire,
                        'annee_academique'      => $ue->annee_academique,
                        'semestre'              => $ue->semestre,
                        'statut'                => 'activee',
                        'date_activation'       => $ue->date_activation?->format('Y-m-d H:i:s'),
                    ];
                });

            $unitesNonActivees = $allUes->where('statut', 'non_activee')->map(function ($ue) use ($tauxHoraire) {
                    return [
                        'id'                   => $ue->id,
                        'code_ue'              => $ue->code_ue,
                        'nom_matiere'          => $ue->nom_matiere,
                        'volume_horaire_total' => (float) $ue->volume_horaire_total,
                        'montant_potentiel'    => (float) $ue->montant_max,
                        'taux_horaire'         => $tauxHoraire,
                        'annee_academique'     => $ue->annee_academique,
                        'semestre'             => $ue->semestre,
                        'statut'               => 'non_activee',
                        'date_attribution'     => $ue->date_attribution?->format('Y-m-d H:i:s'),
                    ];
                });

            $ueData = [
                'unites_activees'     => $unitesActivees,
                'unites_non_activees' => $unitesNonActivees,
                'totaux' => [
                    'heures_effectuees' => (float) $unitesActivees->sum('heures_effectuees'),
                    'montant_paye'      => (float) $unitesActivees->sum('montant_paye'),
                    'montant_restant'   => (float) $unitesActivees->sum('montant_restant'),
                    'taux_horaire'      => $tauxHoraire,
                ],
            ];

            $jourActuel  = UeSchedule::getCurrentDayFr();
            $ueIds       = $allUes->where('statut', 'activee')->pluck('id');
            $schedules   = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
                ->where('is_active', true)
                ->validNow()
                ->where('jour_semaine', $jourActuel)
                ->with(['uniteEnseignement', 'campus'])
                ->orderBy('heure_debut')
                ->get();

            $todaySchedule = $schedules->map(function ($schedule) {
                return [
                    'id'           => $schedule->id,
                    'jour_semaine' => $schedule->jour_semaine,
                    'heure_debut'  => substr($schedule->heure_debut, 0, 5),
                    'heure_fin'    => substr($schedule->heure_fin, 0, 5),
                    'salle'        => $schedule->salle,
                    'duree_heures' => $schedule->duree_en_heures,
                    'ue' => [
                        'id'                     => $schedule->uniteEnseignement->id,
                        'code_ue'                => $schedule->uniteEnseignement->code_ue,
                        'nom_matiere'            => $schedule->uniteEnseignement->nom_matiere,
                        'heures_restantes'       => $schedule->uniteEnseignement->heures_restantes,
                        'volume_horaire_total'   => $schedule->uniteEnseignement->volume_horaire_total,
                        'pourcentage_progression'=> $schedule->uniteEnseignement->pourcentage_progression,
                    ],
                    'campus' => [
                        'id'   => $schedule->campus->id,
                        'name' => $schedule->campus->name,
                    ],
                ];
            })->values()->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'dashboard' => [
                    'pending_presence_checks' => $pendingChecks,
                    'has_active_checkin'      => $activeCheckIns->isNotEmpty(),
                    'active_checkins'         => $activeCheckIns->load('campus')->values(),
                    'month_stats' => [
                        'total_check_ins' => $monthCheckIns->count(),
                        'total_late'      => $monthCheckIns->where('is_late', true)->count(),
                        'days_worked'     => $monthCheckIns->map(fn($a) => $a->timestamp->format('Y-m-d'))->unique()->count(),
                    ],
                    'last_checkin' => $lastCheckIn,
                ],
                'campuses'      => $campuses,
                'tasks'         => $tasks,
                'break'         => $breakData,
                'ue'            => $ueData,
                'today_schedule'=> $todaySchedule,
            ],
        ]);
    }

    /**
     * Mes campus
     */
    public function myCampuses(Request $request)
    {
        $user = $request->user();
        $campuses = Cache::remember("user_{$user->id}_campuses", 1800, function () use ($user) {
            return $user->campuses()->get()->map(function ($campus) {
                return [
                    'id'             => $campus->id,
                    'name'           => $campus->name,
                    'code'           => $campus->code,
                    'address'        => $campus->address,
                    'latitude'       => $campus->latitude,
                    'longitude'      => $campus->longitude,
                    'radius'         => $campus->radius,
                    'start_time'     => substr($campus->start_time, 0, 5),
                    'end_time'       => substr($campus->end_time, 0, 5),
                    'late_tolerance' => $campus->late_tolerance,
                    'is_primary'     => $campus->pivot->is_primary,
                    'is_active'      => $campus->is_active,
                ];
            })->values()->all();
        });

        return response()->json([
            'campuses' => $campuses,
            'total'    => count($campuses),
        ], 200);
    }

    /**
     * Invalider le cache campus d'un utilisateur (à appeler si on modifie ses campus)
     */
    public static function clearCampusCache(int $userId): void
    {
        Cache::forget("user_{$userId}_campuses");
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
