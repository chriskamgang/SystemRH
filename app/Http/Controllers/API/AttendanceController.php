<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\Tardiness;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Détecter automatiquement la plage horaire (matin ou soir) selon l'heure actuelle
     */
    private function detectShift($currentTime = null)
    {
        if (!$currentTime) {
            $currentTime = now();
        }

        $separatorTime = Setting::get('shift_separator_time', '17:00');
        $separator = Carbon::parse($separatorTime);
        $current = Carbon::parse($currentTime->format('H:i:s'));

        return $current->lt($separator) ? 'morning' : 'evening';
    }

    /**
     * Obtenir les horaires d'une plage
     */
    private function getShiftTimes($shift)
    {
        if ($shift === 'morning') {
            return [
                'start' => Setting::get('morning_start_time', '08:15'),
                'end' => Setting::get('morning_end_time', '17:00'),
            ];
        } else {
            return [
                'start' => Setting::get('evening_start_time', '17:30'),
                'end' => Setting::get('evening_end_time', '21:00'),
            ];
        }
    }

    /**
     * Check-in - Pointage d'entrée
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'device_info' => 'nullable|array',
            'unite_enseignement_id' => 'nullable|exists:unites_enseignement,id',
        ]);

        $user = $request->user();
        $campus = Campus::findOrFail($request->campus_id);

        // Si enseignant vacataire ou semi-permanent, vérifier l'UE
        if (($user->employee_type === 'enseignant_vacataire' || $user->employee_type === 'semi_permanent') && $request->unite_enseignement_id) {
            $ue = \App\Models\UniteEnseignement::find($request->unite_enseignement_id);

            // Vérifier que l'UE appartient à l'enseignant
            if (!$ue || $ue->vacataire_id !== $user->id) {
                return response()->json([
                    'message' => 'Cette UE ne vous appartient pas.',
                ], 403);
            }

            // Vérifier que l'UE est activée
            if ($ue->statut !== 'activee') {
                return response()->json([
                    'message' => 'Cette UE n\'est pas encore activée. Contactez l\'administration.',
                ], 400);
            }

            // Vérifier qu'il reste des heures
            if ($ue->heures_restantes <= 0) {
                return response()->json([
                    'message' => 'Vous avez déjà effectué toutes les heures pour cette UE.',
                ], 400);
            }
        }

        // Vérifier si le campus est actif
        if (!$campus->is_active) {
            return response()->json([
                'message' => 'Ce campus est actuellement désactivé.',
            ], 400);
        }

        // Vérifier si l'utilisateur est assigné à ce campus
        if (!$user->campuses->contains($campus->id)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas assigné à ce campus.',
            ], 403);
        }

        // Vérifier si l'utilisateur est dans la zone géographique
        if (!$campus->isUserInZone($request->latitude, $request->longitude)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas dans la zone du campus. Veuillez vous rapprocher.',
                'distance_info' => 'Vous devez être dans un rayon de ' . $campus->radius . ' mètres.',
            ], 400);
        }

        // Détecter automatiquement la plage horaire
        $now = now();
        $shift = $this->detectShift($now);

        // Vérifier s'il y a déjà un check-in sans check-out pour CETTE plage aujourd'hui
        // Les vacataires peuvent faire plusieurs check-ins dans différents campus
        $isVacataire = $user->employee_type === 'enseignant_vacataire';

        if (!$isVacataire) {
            // Personnel permanent : vérifier check-in actif pour cette plage
            $todayCheckInsThisShift = Attendance::where('user_id', $user->id)
                ->where('type', 'check-in')
                ->where('shift', $shift)
                ->whereDate('timestamp', today())
                ->get();

            foreach ($todayCheckInsThisShift as $checkIn) {
                $hasCheckOut = Attendance::where('user_id', $user->id)
                    ->where('campus_id', $checkIn->campus_id)
                    ->where('shift', $shift)
                    ->where('type', 'check-out')
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->whereDate('timestamp', today())
                    ->exists();

                if (!$hasCheckOut) {
                    $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
                    return response()->json([
                        'message' => "Vous avez déjà un check-in actif pour la plage du {$shiftLabel}. Veuillez d'abord faire un check-out.",
                        'existing_checkin' => $checkIn,
                        'shift' => $shift,
                    ], 400);
                }
            }
        } else {
            // Vacataire : vérifier uniquement qu'il n'y a pas de check-in actif sur CE campus pour CETTE plage
            $todayCheckInsThisCampusShift = Attendance::where('user_id', $user->id)
                ->where('campus_id', $campus->id)
                ->where('type', 'check-in')
                ->where('shift', $shift)
                ->whereDate('timestamp', today())
                ->get();

            foreach ($todayCheckInsThisCampusShift as $checkIn) {
                $hasCheckOut = Attendance::where('user_id', $user->id)
                    ->where('campus_id', $campus->id)
                    ->where('shift', $shift)
                    ->where('type', 'check-out')
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->whereDate('timestamp', today())
                    ->exists();

                if (!$hasCheckOut) {
                    $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
                    return response()->json([
                        'message' => "Vous avez déjà un check-in actif pour la plage du {$shiftLabel} sur ce campus. Veuillez d'abord faire un check-out.",
                        'shift' => $shift,
                    ], 400);
                }
            }
        }

        // Obtenir les horaires de la plage détectée
        $shiftTimes = $this->getShiftTimes($shift);
        $currentTime = Carbon::parse($now->format('H:i:s'));
        $shiftStartTime = Carbon::parse($shiftTimes['start']);
        $toleranceTime = $shiftStartTime->copy()->addMinutes($campus->late_tolerance ?? 5);

        // Déterminer si en retard
        // Pour les vacataires: pas de calcul de retard (payés à l'heure effectuée)
        if ($user->employee_type === 'enseignant_vacataire') {
            $isLate = false;
            $lateMinutes = 0;
        } else {
            // Pour personnel permanent/semi-permanent: calcul selon la plage
            $isLate = $currentTime->gt($toleranceTime);
            $lateMinutes = $isLate ? $shiftStartTime->diffInMinutes($currentTime) : 0;
        }

        // Créer le pointage
        $attendanceData = [
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'type' => 'check-in',
            'shift' => $shift, // Ajouter la plage horaire (morning/evening)
            'timestamp' => $now,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'device_info' => $request->device_info,
            'status' => 'valid',
        ];

        // Ajouter l'UE si vacataire ou semi-permanent
        if (($user->employee_type === 'enseignant_vacataire' || $user->employee_type === 'semi_permanent') && $request->unite_enseignement_id) {
            $attendanceData['unite_enseignement_id'] = $request->unite_enseignement_id;
        }

        $attendance = Attendance::create($attendanceData);

        // Si en retard, créer un enregistrement de retard
        if ($isLate) {
            Tardiness::create([
                'user_id' => $user->id,
                'campus_id' => $campus->id,
                'attendance_id' => $attendance->id,
                'date' => $now->toDateString(),
                'scheduled_time' => $shiftTimes['start'],
                'actual_time' => $now->format('H:i:s'),
                'late_minutes' => $lateMinutes,
                'status' => 'pending',
            ]);
        }

        $attendance->load(['campus', 'tardiness']);

        $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
        $message = $isLate
            ? "Check-in enregistré pour la plage du {$shiftLabel} avec retard de {$lateMinutes} minutes."
            : "Check-in enregistré avec succès pour la plage du {$shiftLabel}.";

        return response()->json([
            'message' => $message,
            'attendance' => $attendance,
            'shift' => $shift,
            'shift_label' => $shiftLabel,
            'shift_start_time' => $shiftTimes['start'],
            'shift_end_time' => $shiftTimes['end'],
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'timestamp' => $now,
        ], 201);
    }

    /**
     * Check-out - Pointage de sortie
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'device_info' => 'nullable|array',
        ]);

        $user = $request->user();
        $campus = Campus::findOrFail($request->campus_id);

        // Vérifier si l'utilisateur est dans la zone
        if (!$campus->isUserInZone($request->latitude, $request->longitude)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas dans la zone du campus pour faire le check-out.',
            ], 400);
        }

        // Détecter automatiquement la plage horaire
        $now = now();
        $shift = $this->detectShift($now);

        // Vérifier s'il y a un check-in sans check-out pour CETTE plage
        $todayCheckInsThisShift = Attendance::where('user_id', $user->id)
            ->where('campus_id', $campus->id)
            ->where('type', 'check-in')
            ->where('shift', $shift)
            ->whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->get();

        $checkIn = null;
        foreach ($todayCheckInsThisShift as $ci) {
            $hasCheckOut = Attendance::where('user_id', $user->id)
                ->where('campus_id', $campus->id)
                ->where('shift', $shift)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $ci->timestamp)
                ->whereDate('timestamp', today())
                ->exists();

            if (!$hasCheckOut) {
                $checkIn = $ci;
                break;
            }
        }

        if (!$checkIn) {
            $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
            return response()->json([
                'message' => "Aucun check-in actif trouvé pour la plage du {$shiftLabel} sur ce campus aujourd'hui.",
                'shift' => $shift,
            ], 400);
        }

        // Créer le check-out
        $checkout = Attendance::create([
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'type' => 'check-out',
            'shift' => $shift, // Ajouter la plage horaire
            'timestamp' => $now,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'device_info' => $request->device_info,
            'status' => 'valid',
        ]);

        // Calculer la durée
        $duration = $checkIn->timestamp->diffInMinutes($checkout->timestamp);

        $checkout->load('campus');

        $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';

        return response()->json([
            'message' => "Check-out enregistré avec succès pour la plage du {$shiftLabel}.",
            'checkout' => $checkout,
            'checkin' => $checkIn,
            'shift' => $shift,
            'shift_label' => $shiftLabel,
            'duration_minutes' => $duration,
            'duration_formatted' => $this->formatDuration($duration),
        ], 201);
    }

    /**
     * Historique des pointages de l'utilisateur
     */
    public function myHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
            'type' => 'nullable|in:check-in,check-out',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $query = Attendance::where('user_id', $user->id)
            ->with(['campus', 'tardiness', 'uniteEnseignement']);

        // Filtres
        if ($request->start_date) {
            $query->whereDate('timestamp', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('timestamp', '<=', $request->end_date);
        }

        if ($request->campus_id) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Récupérer toutes les présences (sans pagination pour l'app mobile)
        $attendances = $query->orderBy('timestamp', 'desc')->get();

        return response()->json([
            'success' => true,
            'attendances' => $attendances,
        ], 200);
    }

    /**
     * Pointages du jour
     */
    public function today(Request $request)
    {
        $user = $request->user();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('timestamp', today())
            ->with(['campus', 'tardiness', 'uniteEnseignement'])
            ->orderBy('timestamp', 'asc')
            ->get();

        // Grouper par campus et par paire check-in/check-out
        $grouped = $attendances->groupBy('campus_id')->map(function ($campusAttendances) {
            $checkIns = $campusAttendances->where('type', 'check-in');
            $checkOuts = $campusAttendances->where('type', 'check-out');

            return [
                'campus' => $campusAttendances->first()->campus,
                'check_ins' => $checkIns->values(),
                'check_outs' => $checkOuts->values(),
                'has_active_checkin' => $checkIns->count() > $checkOuts->count(),
            ];
        })->values();

        return response()->json([
            'date' => today()->toDateString(),
            'total_attendances' => $attendances->count(),
            'by_campus' => $grouped,
            'all_attendances' => $attendances,
        ], 200);
    }

    /**
     * Statistiques de présence
     */
    public function stats(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        // Total de check-ins
        $totalCheckIns = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        // Total de retards
        $totalLate = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('is_late', true)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->count();

        // Jours travaillés (jours uniques avec au moins un check-in)
        $daysWorked = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->select(DB::raw('DATE(timestamp) as date'))
            ->distinct()
            ->count();

        // Total d'heures (somme des durées check-in -> check-out)
        $totalMinutes = 0;
        $checkIns = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();

        foreach ($checkIns as $checkIn) {
            $checkOut = Attendance::where('user_id', $user->id)
                ->where('campus_id', $checkIn->campus_id)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', $checkIn->timestamp->toDateString())
                ->first();

            if ($checkOut) {
                $totalMinutes += $checkIn->timestamp->diffInMinutes($checkOut->timestamp);
            }
        }

        // Retards par campus
        $latenessByCampus = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('is_late', true)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->with('campus')
            ->get()
            ->groupBy('campus_id')
            ->map(function ($items) {
                return [
                    'campus' => $items->first()->campus->name,
                    'count' => $items->count(),
                    'total_late_minutes' => $items->sum('late_minutes'),
                ];
            })->values();

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'stats' => [
                'total_check_ins' => $totalCheckIns,
                'total_late' => $totalLate,
                'days_worked' => $daysWorked,
                'total_hours' => round($totalMinutes / 60, 2),
                'total_minutes' => $totalMinutes,
                'average_hours_per_day' => $daysWorked > 0 ? round(($totalMinutes / 60) / $daysWorked, 2) : 0,
                'on_time_rate' => $totalCheckIns > 0 ? round((($totalCheckIns - $totalLate) / $totalCheckIns) * 100, 2) : 0,
            ],
            'lateness_by_campus' => $latenessByCampus,
        ], 200);
    }

    /**
     * Statut actuel (check-in actif ou non)
     */
    public function currentStatus(Request $request)
    {
        $user = $request->user();

        $activeCheckIns = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->whereDate('timestamp', today())
            ->get()
            ->filter(function ($checkIn) {
                // Vérifier s'il n'y a pas de check-out correspondant
                return !Attendance::where('user_id', $checkIn->user_id)
                    ->where('campus_id', $checkIn->campus_id)
                    ->where('type', 'check-out')
                    ->where('timestamp', '>', $checkIn->timestamp)
                    ->whereDate('timestamp', $checkIn->timestamp->toDateString())
                    ->exists();
            });

        return response()->json([
            'has_active_checkin' => $activeCheckIns->isNotEmpty(),
            'active_checkins' => $activeCheckIns->load(['campus', 'uniteEnseignement'])->values(),
            'count' => $activeCheckIns->count(),
        ], 200);
    }

    /**
     * Formater la durée en heures et minutes
     */
    private function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%dh%02d', $hours, $mins);
    }
}
