<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\Tardiness;
use App\Models\Setting;
use App\Models\UeSchedule;
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
                'start' => Setting::get('morning_start_time', '08:00'),
                'end' => Setting::get('morning_end_time', '17:00'),
            ];
        } else {
            return [
                'start' => Setting::get('evening_start_time', '18:00'),
                'end' => Setting::get('evening_end_time', '21:30'),
            ];
        }
    }

    /**
     * Vérifier si un enseignant a cours le soir aujourd'hui (via emploi du temps)
     */
    /**
     * Trouver un check-in actif (sans check-out correspondant) pour un shift donné
     */
    private function findActiveCheckIn($user, $shift)
    {
        $todayCheckIns = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('shift', $shift)
            ->whereDate('timestamp', today())
            ->orderBy('timestamp', 'desc')
            ->get();

        foreach ($todayCheckIns as $ci) {
            $hasCheckOut = Attendance::where('user_id', $user->id)
                ->where('shift', $shift)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $ci->timestamp)
                ->whereDate('timestamp', today())
                ->exists();

            if (!$hasCheckOut) {
                return $ci;
            }
        }

        return null;
    }

    private function hasEveningClass($user): bool
    {
        $jourSemaine = strtolower(Carbon::now()->locale('fr')->isoFormat('dddd'));

        return UeSchedule::whereHas('uniteEnseignement', function ($q) use ($user) {
                $q->where('enseignant_id', $user->id);
            })
            ->where('jour_semaine', $jourSemaine)
            ->where('heure_debut', '>=', '17:00')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtenir l'heure de fin effective pour un utilisateur
     * 17h00 par défaut (8h travail + 1h pause), 21h30 si travaille le soir
     */
    private function getEffectiveEndTime($user): string
    {
        // Vérifier si l'utilisateur travaille le soir (assignation campus OU cours le soir)
        if ($this->userWorksEvening($user)) {
            return '21:30';
        }
        return Setting::get('morning_end_time', '17:00');
    }

    /**
     * Vérifier si un utilisateur travaille le soir
     * - Via assignation campus (works_evening = true) ex: responsable campus
     * - Via emploi du temps UE (cours >= 17:00) ex: enseignant titulaire
     */
    private function userWorksEvening($user): bool
    {
        // 1. Vérifier l'assignation campus (works_evening)
        try {
            $hasEveningShift = $user->campusShifts()->where('works_evening', true)->exists();
            if ($hasEveningShift) {
                return true;
            }
        } catch (\Exception $e) {
            // Colonne works_evening peut ne pas exister en production
        }

        // 2. Vérifier l'emploi du temps UE (cours le soir)
        if (in_array($user->employee_type, ['enseignant_vacataire', 'semi_permanent', 'enseignant_titulaire'])) {
            return $this->hasEveningClass($user);
        }

        return false;
    }

    /**
     * Normaliser l'heure de check-in : si avant 8h00, compter à partir de 8h00
     */
    private function normalizeCheckInTime(Carbon $timestamp): Carbon
    {
        $workStart = Carbon::parse('08:00')->setDate($timestamp->year, $timestamp->month, $timestamp->day);
        if ($timestamp->lt($workStart)) {
            return $workStart;
        }
        return $timestamp;
    }

    /**
     * Normaliser l'heure de check-out : plafonner à l'heure de fin (pas d'heures sup)
     * 18h00 par défaut, 21h30 si cours le soir
     */
    private function normalizeCheckOutTime(Carbon $timestamp, $user): Carbon
    {
        $endTime = $this->getEffectiveEndTime($user);
        $workEnd = Carbon::parse($endTime)->setDate($timestamp->year, $timestamp->month, $timestamp->day);
        if ($timestamp->gt($workEnd)) {
            return $workEnd;
        }
        return $timestamp;
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

        // Si enseignant, vérifier l'UE
        if (in_array($user->employee_type, ['enseignant_vacataire', 'semi_permanent', 'enseignant_titulaire']) && $request->unite_enseignement_id) {
            $ue = \App\Models\UniteEnseignement::find($request->unite_enseignement_id);

            // Vérifier que l'UE appartient à l'enseignant
            if (!$ue || $ue->enseignant_id !== $user->id) {
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

            // Vérifier l'emploi du temps si des créneaux existent (valides pour la période actuelle)
            $schedules = \App\Models\UeSchedule::where('unite_enseignement_id', $ue->id)
                ->where('is_active', true)
                ->validNow()
                ->get();

            if ($schedules->count() > 0) {
                $jourActuel = \App\Models\UeSchedule::getCurrentDayFr();
                $heureActuelle = now();
                $toleranceMinutes = (int) \App\Models\Setting::get('schedule_tolerance_minutes', '15');

                $creneauValide = $schedules->first(function ($schedule) use ($jourActuel, $heureActuelle, $campus, $toleranceMinutes) {
                    if ($schedule->jour_semaine !== $jourActuel) {
                        return false;
                    }
                    if ($schedule->campus_id !== $campus->id) {
                        return false;
                    }
                    $debut = \Carbon\Carbon::parse($schedule->heure_debut)->subMinutes($toleranceMinutes);
                    $fin = \Carbon\Carbon::parse($schedule->heure_fin)->addMinutes($toleranceMinutes);
                    $current = \Carbon\Carbon::parse($heureActuelle->format('H:i:s'));
                    return $current->between($debut, $fin);
                });

                if (!$creneauValide) {
                    $creneauxDuJour = $schedules->where('jour_semaine', $jourActuel)->where('campus_id', $campus->id);
                    if ($creneauxDuJour->isEmpty()) {
                        return response()->json([
                            'message' => "Cette UE n'est pas programmée aujourd'hui sur ce campus.",
                        ], 400);
                    }
                    return response()->json([
                        'message' => "Cette UE n'est pas programmée à cette heure. Consultez votre emploi du temps.",
                    ], 400);
                }
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
        // La tolérance s'adapte à la précision GPS du téléphone
        $accuracy = $request->accuracy;
        if (!$campus->isUserInZone($request->latitude, $request->longitude, $accuracy)) {
            $distance = round($campus->distanceToUser($request->latitude, $request->longitude));
            return response()->json([
                'message' => "Vous êtes à {$distance}m du campus. Rapprochez-vous à moins de {$campus->radius}m.",
                'distance' => $distance,
                'radius' => $campus->radius,
                'accuracy' => $accuracy,
            ], 400);
        }

        // Détecter automatiquement la plage horaire
        $now = now();
        $shift = $this->detectShift($now);

        // Vérifier s'il y a un check-in actif sur N'IMPORTE QUEL campus
        // L'employé doit d'abord faire check-out avant de changer de campus
        $allTodayCheckIns = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('shift', $shift)
            ->whereDate('timestamp', today())
            ->get();

        foreach ($allTodayCheckIns as $checkIn) {
            $hasCheckOut = Attendance::where('user_id', $user->id)
                ->where('campus_id', $checkIn->campus_id)
                ->where('shift', $shift)
                ->where('type', 'check-out')
                ->where('timestamp', '>', $checkIn->timestamp)
                ->whereDate('timestamp', today())
                ->exists();

            if (!$hasCheckOut) {
                $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
                $activeCampusName = $checkIn->campus ? $checkIn->campus->name : 'un autre campus';

                if ($checkIn->campus_id === $campus->id) {
                    $msg = "Vous avez deja un check-in actif pour la plage du {$shiftLabel} sur ce campus. Veuillez d'abord faire un check-out.";
                } else {
                    $msg = "Vous avez un check-in actif sur {$activeCampusName}. Veuillez d'abord faire le check-out la-bas avant de pointer ici.";
                }

                return response()->json([
                    'message' => $msg,
                    'existing_checkin' => $checkIn->load('campus'),
                    'active_campus' => $activeCampusName,
                    'shift' => $shift,
                ], 400);
            }
        }

        // Vérifier si c'est le premier check-in de la journée (pour cette plage)
        $isFirstCheckInOfDay = !Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('shift', $shift)
            ->whereDate('timestamp', today())
            ->exists();

        $isVacataire = $user->employee_type === 'enseignant_vacataire';

        // Obtenir les horaires de travail
        $currentTime = Carbon::parse($now->format('H:i:s'));

        if ($user->hasCustomWorkHours()) {
            $shiftStartTime = Carbon::parse($user->custom_start_time);
            $shiftEndTime = Carbon::parse($user->custom_end_time);
            $lateTolerance = $user->getLateTolerance($campus);
            $shiftTimes = [
                'start' => $user->custom_start_time,
                'end' => $user->custom_end_time,
            ];
        } else {
            $shiftTimes = $this->getShiftTimes($shift);
            $effectiveEnd = $this->getEffectiveEndTime($user);
            $shiftTimes['end'] = $effectiveEnd;

            $shiftStartTime = Carbon::parse($shiftTimes['start']);
            $lateTolerance = 0;
        }

        $toleranceTime = $shiftStartTime->copy()->addMinutes($lateTolerance);

        // Déterminer si en retard
        $isHalfDay = false;
        $isLate = false;
        $lateMinutes = 0;
        $travelLateReason = null;

        if ($isVacataire) {
            // Vacataires : pas de retard (payés à l'heure)
            $isLate = false;
            $lateMinutes = 0;
        } elseif ($isFirstCheckInOfDay) {
            // Premier check-in du jour : contrôle de retard normal
            $isLate = $currentTime->gt($toleranceTime);
            $lateMinutes = $isLate ? $shiftStartTime->diffInMinutes($currentTime) : 0;

            $halfDayThreshold = (int) Setting::get('half_day_threshold_minutes', 120);
            if ($isLate && $lateMinutes >= $halfDayThreshold) {
                $isHalfDay = true;
            }
        } else {
            // Check-in suivant (déplacement entre campus) : vérifier le temps de trajet
            $lastCheckOut = Attendance::where('user_id', $user->id)
                ->where('type', 'check-out')
                ->where('shift', $shift)
                ->whereDate('timestamp', today())
                ->latest('timestamp')
                ->first();

            if ($lastCheckOut) {
                $lastCheckOutTime = Carbon::parse($lastCheckOut->timestamp);
                $elapsedMinutes = $lastCheckOutTime->diffInMinutes($now);

                // Récupérer le temps de trajet configuré entre les deux campus
                $travelMinutes = \App\Models\CampusTravelTime::getTravelMinutes(
                    $lastCheckOut->campus_id,
                    $campus->id,
                    (int) Setting::get('default_travel_minutes', 30)
                );

                // Si le temps écoulé dépasse le temps de trajet configuré → retard
                if ($elapsedMinutes > $travelMinutes) {
                    $isLate = true;
                    $lateMinutes = $elapsedMinutes - $travelMinutes;
                    $travelLateReason = "Déplacement depuis {$lastCheckOut->campus->name}: {$elapsedMinutes} min (trajet autorisé: {$travelMinutes} min)";
                }
            }
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
            'is_travel_late' => $travelLateReason ? true : false,
            'late_minutes' => $lateMinutes,
            'is_half_day' => $isHalfDay,
            'device_info' => $request->device_info,
            'status' => 'valid',
        ];

        // Ajouter l'UE si enseignant (vacataire, semi-permanent ou titulaire)
        if (in_array($user->employee_type, ['enseignant_vacataire', 'semi_permanent', 'enseignant_titulaire']) && $request->unite_enseignement_id) {
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
                'justification' => $travelLateReason,
            ]);
        }

        $attendance->load(['campus', 'tardiness']);

        $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';
        if ($isHalfDay) {
            $message = "Check-in enregistré pour la plage du {$shiftLabel}. Demi-journée comptabilisée (arrivée tardive).";
        } elseif ($isLate && $travelLateReason) {
            $message = "Check-in enregistré pour la plage du {$shiftLabel}. Retard de {$lateMinutes} min (déplacement entre campus).";
        } elseif ($isLate) {
            $message = "Check-in enregistré pour la plage du {$shiftLabel} avec retard de {$lateMinutes} minutes.";
        } else {
            $message = "Check-in enregistré avec succès pour la plage du {$shiftLabel}.";
        }

        return response()->json([
            'message' => $message,
            'attendance' => $attendance,
            'shift' => $shift,
            'shift_label' => $shiftLabel,
            'shift_start_time' => $shiftTimes['start'],
            'shift_end_time' => $shiftTimes['end'],
            'is_late' => $isLate,
            'is_half_day' => $isHalfDay,
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

        // Vérifier si l'utilisateur est dans la zone du campus indiqué
        $accuracy = $request->accuracy;
        if (!$campus->isUserInZone($request->latitude, $request->longitude, $accuracy)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas dans la zone du campus pour faire le check-out.',
            ], 400);
        }

        // Détecter automatiquement la plage horaire
        $now = now();
        $shift = $this->detectShift($now);

        // Chercher un check-in actif : d'abord sur le shift actuel, sinon sur TOUS les shifts
        $checkIn = $this->findActiveCheckIn($user, $shift);

        // Si pas trouvé sur le shift actuel, chercher sur l'autre shift
        if (!$checkIn) {
            $otherShift = $shift === 'morning' ? 'evening' : 'morning';
            $checkIn = $this->findActiveCheckIn($user, $otherShift);
            if ($checkIn) {
                $shift = $otherShift; // Utiliser le shift du check-in trouvé
            }
        }

        if (!$checkIn) {
            return response()->json([
                'message' => "Aucun check-in actif trouvé pour aujourd'hui.",
            ], 400);
        }

        // Forcer le check-out sur le même campus que le check-in
        if ($checkIn->campus_id !== $campus->id) {
            $checkInCampusName = $checkIn->campus ? $checkIn->campus->name : 'un autre campus';
            return response()->json([
                'message' => "Vous devez faire le check-out sur {$checkInCampusName} (campus de votre check-in).",
                'checkin_campus_id' => $checkIn->campus_id,
                'checkin_campus' => $checkInCampusName,
            ], 400);
        }

        // Normaliser les heures pour le calcul :
        // - Check-in avant 8h → compter à partir de 8h
        // - Check-out après 18h (ou 21h30 si cours le soir) → plafonner
        $effectiveCheckIn = $this->normalizeCheckInTime($checkIn->timestamp);
        $effectiveCheckOut = $this->normalizeCheckOutTime($now, $user);

        // Calculer la durée de cette session (en soustrayant la pause)
        $sessionDurationMinutes = $effectiveCheckIn->diffInMinutes($effectiveCheckOut);
        $breakMinutes = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
            $checkIn->timestamp, $now, $user->employee_type
        );
        $sessionDurationMinutes -= $breakMinutes;
        $sessionDurationHours = round($sessionDurationMinutes / 60, 2);

        // Variables pour le plafonnement
        $isCapped = false;
        $cappedHours = null;
        $nonCountedHours = null;
        $ueWarning = null;

        // Si le check-in a une UE associée (vacataire ou semi-permanent)
        if ($checkIn->unite_enseignement_id) {
            $ue = \App\Models\UniteEnseignement::find($checkIn->unite_enseignement_id);

            if ($ue) {
                $heuresRestantes = $ue->heures_restantes;

                // Si les heures de cette session dépassent les heures restantes
                if ($sessionDurationHours > $heuresRestantes) {
                    $isCapped = true;
                    $cappedHours = $heuresRestantes;
                    $nonCountedHours = round($sessionDurationHours - $heuresRestantes, 2);

                    $ueWarning = [
                        'message' => 'Attention: Les heures de cette session dépassent le volume horaire restant pour cette UE.',
                        'ue_nom' => $ue->nom_matiere,
                        'volume_horaire_total' => $ue->volume_horaire_total,
                        'heures_deja_effectuees' => $ue->heures_effectuees,
                        'heures_restantes' => $heuresRestantes,
                        'heures_session_reelles' => $sessionDurationHours,
                        'heures_comptabilisees' => $cappedHours,
                        'heures_non_comptabilisees' => $nonCountedHours,
                        'explication' => "Les {$nonCountedHours}h supplémentaires ne seront pas payées car vous avez atteint le maximum autorisé de {$ue->volume_horaire_total}h pour cette UE.",
                    ];
                }
            }
        }

        // Créer le check-out
        $checkoutData = [
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'type' => 'check-out',
            'shift' => $shift,
            'timestamp' => $now,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'device_info' => $request->device_info,
            'status' => 'valid',
        ];

        // Si le check-in avait une UE, l'associer aussi au check-out
        if ($checkIn->unite_enseignement_id) {
            $checkoutData['unite_enseignement_id'] = $checkIn->unite_enseignement_id;
        }

        $checkout = Attendance::create($checkoutData);

        $checkout->load('campus');

        $shiftLabel = $shift === 'morning' ? 'matin' : 'soir';

        $responseMessage = "Check-out enregistré avec succès pour la plage du {$shiftLabel}.";
        if ($isCapped) {
            $responseMessage .= " ⚠️ Heures plafonnées à {$cappedHours}h (sur {$sessionDurationHours}h effectuées).";
        }

        $response = [
            'message' => $responseMessage,
            'checkout' => $checkout,
            'checkin' => $checkIn,
            'shift' => $shift,
            'shift_label' => $shiftLabel,
            'duration_minutes' => $sessionDurationMinutes,
            'duration_hours' => $sessionDurationHours,
            'duration_formatted' => $this->formatDuration($sessionDurationMinutes),
            'is_capped' => $isCapped,
        ];

        if ($isCapped) {
            $response['capped_hours'] = $cappedHours;
            $response['non_counted_hours'] = $nonCountedHours;
            $response['ue_warning'] = $ueWarning;
        }

        return response()->json($response, 201);
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
                // Plafonner et soustraire la pause
                $effIn = $checkIn->timestamp->copy();
                $effOut = $checkOut->timestamp->copy();
                $wStart = $effIn->copy()->setTime(8, 0, 0);
                $wEnd = $effIn->copy()->setTime(17, 0, 0);
                if ($effIn->lt($wStart)) $effIn = $wStart;
                if ($checkIn->timestamp->hour < 17 && $effOut->gt($wEnd)) $effOut = $wEnd;
                $mins = $effIn->diffInMinutes($effOut);
                $breakMins = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
                    $effIn, $effOut, $user->employee_type
                );
                $totalMinutes += max(0, $mins - $breakMins);
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
