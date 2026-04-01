<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\ManualAttendance;
use App\Models\PayrollJustification;
use App\Models\Setting;
use Carbon\Carbon;

class PayrollCalculator
{
    /**
     * Calculer les jours ouvrables d'un mois selon la configuration
     */
    public static function calculateWorkingDays(int $year, int $month): float
    {
        // Récupérer le mode de calcul depuis les paramètres
        $workingDaysMode = Setting::get('working_days_mode', 'fixed_30');

        if ($workingDaysMode === 'all_days') {
            // Mode: Tous les jours du mois (30 ou 31 jours)
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            return $endDate->day; // Retourne le nombre de jours du mois (28-31)
        }

        if ($workingDaysMode === 'fixed_30') {
            // Mode: Toujours 30 jours
            return 30.0;
        }

        // Mode par défaut: Jours ouvrables (Lun-Ven + Samedi)
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $workingDays = 0;
        $currentDate = $startDate->copy();

        // Paramètres configurables
        $saturdayValue = (float) Setting::get('saturday_working_value', 0.5);
        $sundayWorking = Setting::get('sunday_working', false);

        while ($currentDate->lte($endDate)) {
            $dayOfWeek = $currentDate->dayOfWeek;

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                // Lundi à Vendredi = 1 jour
                $workingDays += 1;
            } elseif ($dayOfWeek == 6) {
                // Samedi = valeur configurable (0, 0.5, ou 1)
                $workingDays += $saturdayValue;
            } elseif ($dayOfWeek == 0 && $sundayWorking) {
                // Dimanche = 1 jour si activé
                $workingDays += 1;
            }

            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculer les statistiques de présence d'un employé pour un mois
     * Le salaire est progressif : basé sur les heures réellement travaillées
     * Un "jour travaillé" n'est compté que lorsqu'il y a check-in ET check-out
     */
    public static function calculateAttendanceStats(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $workingHoursPerDay = (float) Setting::get('working_hours_per_day', 8);

        // ===== 1. PRÉSENCES GPS (Attendance) =====
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        // Grouper par date ET par plage (matin/soir)
        $groupedByDateShift = $attendances->groupBy(function ($attendance) {
            $shift = $attendance->shift ?? 'morning';
            return $attendance->timestamp->format('Y-m-d') . '_' . $shift;
        });

        $totalHoursWorked = 0;
        $totalLateMinutes = 0;
        $totalTravelLateMinutes = 0;
        $daysWithoutCheckout = 0;
        $completedSessions = 0;

        foreach ($groupedByDateShift as $dateShift => $shiftAttendances) {
            list($date, $shift) = explode('_', $dateShift);

            $checkIn = $shiftAttendances->where('type', 'check-in')->first();
            $checkOut = $shiftAttendances->where('type', 'check-out')->first();

            if ($checkIn) {
                if ($checkOut) {
                    // Session complète : calculer les heures réelles
                    $sessionMinutes = $checkIn->timestamp->diffInMinutes($checkOut->timestamp);

                    // Soustraire la pause déjeuner
                    $breakMinutes = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
                        $checkIn->timestamp, $checkOut->timestamp, $user->employee_type
                    );
                    $sessionMinutes -= $breakMinutes;

                    $totalHoursWorked += max(0, $sessionMinutes / 60);
                    $completedSessions++;
                } else {
                    // Session en cours (pas de checkout) : compter les heures progressivement
                    $now = now();
                    // Ne compter que si c'est aujourd'hui (session active)
                    if (Carbon::parse($date)->isToday()) {
                        $sessionMinutes = $checkIn->timestamp->diffInMinutes($now);
                        $breakMinutes = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
                            $checkIn->timestamp, $now, $user->employee_type
                        );
                        $sessionMinutes -= $breakMinutes;
                        $totalHoursWorked += max(0, $sessionMinutes / 60);
                    }
                    $daysWithoutCheckout++;
                }

                // Compter les retards (séparer retards normaux et retards de trajet)
                if ($checkIn->is_late && $checkIn->late_minutes > 0) {
                    if ($checkIn->is_travel_late) {
                        $totalTravelLateMinutes += $checkIn->late_minutes;
                    } else {
                        $totalLateMinutes += $checkIn->late_minutes;
                    }
                }
            }
        }

        // ===== 2. PRÉSENCES MANUELLES (ManualAttendance) =====
        $manualAttendances = ManualAttendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        foreach ($manualAttendances as $manualAttendance) {
            $totalHoursWorked += $manualAttendance->duration_in_hours;
            $completedSessions++;
        }

        // Calculer les jours travaillés à partir des heures (ex: 8h = 1 jour, 4h = 0.5 jour)
        $daysWorked = round($totalHoursWorked / $workingHoursPerDay, 2);

        return [
            'days_worked' => $daysWorked,
            'total_hours_worked' => round($totalHoursWorked, 2),
            'completed_sessions' => $completedSessions,
            'total_late_minutes' => $totalLateMinutes,
            'total_travel_late_minutes' => $totalTravelLateMinutes,
            'days_without_checkout' => $daysWithoutCheckout,
        ];
    }

    /**
     * Calculer les justifications approuvées pour un employé
     */
    public static function getJustifications(User $user, int $year, int $month): array
    {
        $justifications = PayrollJustification::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'approved')
            ->get();

        $totalDaysJustified = $justifications->sum('days_justified');
        $totalLateMinutesJustified = $justifications->sum('late_minutes_justified');

        return [
            'days_justified' => $totalDaysJustified,
            'late_minutes_justified' => $totalLateMinutesJustified,
        ];
    }

    /**
     * Calculer la paie complète d'un employé
     */
    public static function calculatePayroll(User $user, int $year, int $month): array
    {
        // VÉRIFIER D'ABORD S'IL Y A UN AJUSTEMENT MANUEL ACTIF
        $manualAdjustment = \App\Models\ManualPayrollAdjustment::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'active')
            ->first();

        // Si un ajustement manuel existe, utiliser ces valeurs au lieu des calculs automatiques
        if ($manualAdjustment) {
            return [
                'monthly_salary' => $manualAdjustment->salaire_mensuel,
                'working_days' => $manualAdjustment->jours_total,
                'days_worked' => $manualAdjustment->jours_travailles,
                'days_not_worked' => $manualAdjustment->jours_total - $manualAdjustment->jours_travailles,
                'days_justified' => 0, // Pas de justifications avec ajustements manuels
                'total_late_minutes' => ($manualAdjustment->heures_retard * 60) + $manualAdjustment->minutes_retard,
                'late_minutes_justified' => 0,
                'manual_deductions' => $manualAdjustment->deduction_manuelle,
                'manual_deductions_details' => [],
                'loan_deductions' => 0,
                'loan_deductions_details' => [],
                'late_penalty_amount' => $manualAdjustment->penalite_retard,
                'absence_deduction' => $manualAdjustment->montant_perdu,
                'gross_salary' => $manualAdjustment->salaire_brut,
                'salary_based_on_days_worked' => $manualAdjustment->salaire_brut,
                'total_deductions' => $manualAdjustment->penalite_retard + $manualAdjustment->deduction_manuelle,
                'net_salary' => $manualAdjustment->salaire_net,
                'days_without_checkout' => 0,
                'daily_rate' => $manualAdjustment->salaire_journalier,
                'hourly_rate' => 0,
                'per_second_rate' => 0,
                'is_manual_adjustment' => true, // Indicateur pour savoir qu'il s'agit d'un ajustement manuel
                'manual_adjustment_notes' => $manualAdjustment->notes,
            ];
        }

        // Sinon, calculer normalement
        // 1. Récupérer les paramètres
        $penaltyPerSecond = (float) Setting::get('penalty_per_second', 0.50);
        $travelPenaltyPerMinute = (float) Setting::get('travel_late_penalty_per_minute', 30);
        $workingHoursPerDay = (float) Setting::get('working_hours_per_day', 8);

        // 2. Calculer les jours ouvrables du mois
        $workingDaysInMonth = self::calculateWorkingDays($year, $month);

        // 3. Récupérer le salaire de base
        $monthlySalary = (float) $user->monthly_salary;

        // Si pas de salaire défini, retourner zéros
        if ($monthlySalary <= 0) {
            return [
                'monthly_salary' => 0,
                'working_days' => $workingDaysInMonth,
                'days_worked' => 0,
                'days_not_worked' => $workingDaysInMonth,
                'days_justified' => 0,
                'total_late_minutes' => 0,
                'late_minutes_justified' => 0,
                'late_penalty_amount' => 0,
                'absence_deduction' => 0,
                'gross_salary' => $monthlySalary,
                'total_deductions' => 0,
                'net_salary' => 0,
                'days_without_checkout' => 0,
            ];
        }

        // 4. Calculer les taux
        $dailyRate = $monthlySalary / $workingDaysInMonth;
        $hourlyRate = $dailyRate / $workingHoursPerDay;
        $perMinuteRate = $hourlyRate / 60;
        $perSecondRate = $perMinuteRate / 60;

        // 5. Récupérer les statistiques de présence (basé sur les heures réelles)
        $attendanceStats = self::calculateAttendanceStats($user, $year, $month);
        $totalHoursWorked = $attendanceStats['total_hours_worked'] ?? 0;
        $daysWorked = $attendanceStats['days_worked']; // Calculé à partir des heures
        $totalLateMinutes = $attendanceStats['total_late_minutes'];
        $totalTravelLateMinutes = $attendanceStats['total_travel_late_minutes'] ?? 0;
        $daysWithoutCheckout = $attendanceStats['days_without_checkout'];

        // 6. Calculer les jours non travaillés
        $daysNotWorked = max(0, $workingDaysInMonth - $daysWorked);

        // 7. Récupérer les justifications
        $justifications = self::getJustifications($user, $year, $month);
        $daysJustified = $justifications['days_justified'];
        $lateMinutesJustified = $justifications['late_minutes_justified'];

        // 8. Calculer les pénalités de retard
        // 8a. Retards normaux (1er check-in du jour) : penalty_per_second
        $totalLateSeconds = max(0, ($totalLateMinutes - $lateMinutesJustified)) * 60;
        $normalLatePenalty = max(0, $totalLateSeconds * $penaltyPerSecond);

        // 8b. Retards de trajet (déplacement entre campus) : travel_late_penalty_per_minute
        $travelLatePenalty = max(0, $totalTravelLateMinutes * $travelPenaltyPerMinute);

        $latePenaltyAmount = $normalLatePenalty + $travelLatePenalty;

        // 9. Calculer le salaire proportionnel aux heures réellement travaillées
        // Salaire = heures travaillées × taux horaire (progressif)
        $salaryForDaysWorked = $totalHoursWorked * $hourlyRate;

        // On garde le concept de déduction d'absence pour la compatibilité, mais mis à 0
        $absenceDeduction = 0;

        // 10. Récupérer les déductions manuelles
        $manualDeductions = \App\Models\ManualDeduction::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'active')
            ->get();

        $totalManualDeductions = $manualDeductions->sum('amount');

        // 11. Récupérer les déductions de prêts
        $loans = \App\Models\Loan::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $totalLoanDeductions = 0;
        $loanDeductionsDetails = [];

        foreach ($loans as $loan) {
            if ($loan->shouldDeductForMonth($year, $month)) {
                $deductionAmount = $loan->getDeductionAmountForMonth($year, $month);
                $totalLoanDeductions += $deductionAmount;
                $loanDeductionsDetails[] = [
                    'loan_id' => $loan->id,
                    'total_amount' => $loan->total_amount,
                    'monthly_amount' => $loan->monthly_amount,
                    'amount_paid' => $loan->amount_paid,
                    'remaining_amount' => $loan->remaining_amount,
                    'deduction_this_month' => $deductionAmount,
                    'reason' => $loan->reason,
                ];
            }
        }

        // 12. Calculer le salaire net avec le nouveau système
        // Le salaire brut reste le salaire mensuel (pour affichage)
        $grossSalary = $monthlySalary;

        // Mais on calcule le net à partir du salaire proportionnel aux jours travaillés
        $salaryBasedOnDaysWorked = $salaryForDaysWorked;

        // Les déductions s'appliquent sur le salaire des jours travaillés
        $totalDeductions = $latePenaltyAmount + $totalManualDeductions + $totalLoanDeductions;
        $netSalary = max(0, $salaryBasedOnDaysWorked - $totalDeductions);

        return [
            'monthly_salary' => $monthlySalary,
            'working_days' => $workingDaysInMonth,
            'days_worked' => $daysWorked,
            'total_hours_worked' => $totalHoursWorked,
            'days_not_worked' => $daysNotWorked,
            'days_justified' => $daysJustified,
            'total_late_minutes' => $totalLateMinutes,
            'total_travel_late_minutes' => $totalTravelLateMinutes,
            'normal_late_penalty' => $normalLatePenalty,
            'travel_late_penalty' => $travelLatePenalty,
            'late_minutes_justified' => $lateMinutesJustified,
            'manual_deductions' => $totalManualDeductions,
            'manual_deductions_details' => $manualDeductions,
            'loan_deductions' => $totalLoanDeductions,
            'loan_deductions_details' => $loanDeductionsDetails,
            'late_penalty_amount' => $latePenaltyAmount,
            'absence_deduction' => $absenceDeduction,
            'gross_salary' => $grossSalary,
            'salary_based_on_days_worked' => $salaryBasedOnDaysWorked, // NOUVEAU: salaire proportionnel
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'days_without_checkout' => $daysWithoutCheckout,
            // Taux calculés (pour information)
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'per_second_rate' => $perSecondRate,
        ];
    }

    /**
     * Calculer la paie pour un vacataire (basé sur les heures)
     */
    public static function calculateVacatairePayroll(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Taux horaire par défaut du vacataire (utilisé pour les UE sans taux spécifique, ex: BTS)
        $defaultHourlyRate = (float) $user->hourly_rate;

        // Récupérer toutes les UE actives du vacataire
        $unitesActivees = \App\Models\UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->get();

        // Récupérer toutes les présences du mois
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        // ===== CALCULER LES HEURES PAR UE =====
        $ueBreakdown = [];
        $totalHours = 0;
        $totalGross = 0;
        $daysWorked = 0;

        // Grouper les présences par UE
        $attendancesByUe = $attendances->groupBy('unite_enseignement_id');

        foreach ($unitesActivees as $ue) {
            $ueAttendances = $attendancesByUe->get($ue->id, collect());
            $ueHours = 0;

            // Grouper par date + shift
            $groupedByDateShift = $ueAttendances->groupBy(function ($att) {
                $shift = $att->shift ?? 'morning';
                return $att->timestamp->format('Y-m-d') . '_' . $shift;
            });

            foreach ($groupedByDateShift as $dateShift => $shiftAttendances) {
                list($date, $shift) = explode('_', $dateShift);

                $checkIn = $shiftAttendances->where('type', 'check-in')->first();
                $checkOut = $shiftAttendances->where('type', 'check-out')->first();

                if ($checkIn && $checkOut) {
                    $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp, true);
                    $breakMinutes = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
                        $checkIn->timestamp, $checkOut->timestamp, $user->employee_type
                    );
                    $hoursWorked -= ($breakMinutes / 60);
                    $ueHours += max(0, $hoursWorked);
                    $daysWorked += 0.5;
                } elseif ($checkIn) {
                    $ueHours += ($shift === 'morning') ? 8 : 3.5;
                    $daysWorked += 0.5;
                }
            }

            // Ajouter les présences manuelles pour cette UE
            $manualAttendances = ManualAttendance::where('user_id', $user->id)
                ->where('unite_enseignement_id', $ue->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            foreach ($manualAttendances as $manual) {
                $ueHours += $manual->duration_in_hours;
                $daysWorked += ($manual->session_type === 'jour') ? 1 : 0.5;
            }

            // Utiliser les heures validées si supérieures
            if ($ue->heures_effectuees_validees > $ueHours) {
                $ueHours = (float) $ue->heures_effectuees_validees;
            }

            // Taux effectif : taux de la UE si défini, sinon taux du vacataire
            $effectiveRate = ($ue->taux_horaire !== null && $ue->taux_horaire > 0)
                ? (float) $ue->taux_horaire
                : $defaultHourlyRate;

            $ueMontant = $ueHours * $effectiveRate;

            $ueBreakdown[] = [
                'ue_id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'niveau' => $ue->niveau,
                'taux_horaire' => $effectiveRate,
                'heures' => round($ueHours, 2),
                'montant' => round($ueMontant, 2),
            ];

            $totalHours += $ueHours;
            $totalGross += $ueMontant;
        }

        // ===== PRÉSENCES SANS UE (pointages génériques) =====
        $attendancesSansUe = $attendancesByUe->get(null, collect())
            ->merge($attendancesByUe->get('', collect()));

        if ($attendancesSansUe->isNotEmpty()) {
            $genericHours = 0;

            $groupedGeneric = $attendancesSansUe->groupBy(function ($att) {
                $shift = $att->shift ?? 'morning';
                return $att->timestamp->format('Y-m-d') . '_' . $shift;
            });

            foreach ($groupedGeneric as $dateShift => $shiftAttendances) {
                list($date, $shift) = explode('_', $dateShift);

                $checkIn = $shiftAttendances->where('type', 'check-in')->first();
                $checkOut = $shiftAttendances->where('type', 'check-out')->first();

                if ($checkIn && $checkOut) {
                    $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp, true);
                    $breakMinutes = \App\Models\NotificationSetting::calculateBreakOverlapMinutes(
                        $checkIn->timestamp, $checkOut->timestamp, $user->employee_type
                    );
                    $hoursWorked -= ($breakMinutes / 60);
                    $genericHours += max(0, $hoursWorked);
                    $daysWorked += 0.5;
                } elseif ($checkIn) {
                    $genericHours += ($shift === 'morning') ? 8 : 3.5;
                    $daysWorked += 0.5;
                }
            }

            // Présences manuelles sans UE
            $manualSansUe = ManualAttendance::where('user_id', $user->id)
                ->whereNull('unite_enseignement_id')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            foreach ($manualSansUe as $manual) {
                $genericHours += $manual->duration_in_hours;
                $daysWorked += ($manual->session_type === 'jour') ? 1 : 0.5;
            }

            if ($genericHours > 0) {
                $genericMontant = $genericHours * $defaultHourlyRate;
                $ueBreakdown[] = [
                    'ue_id' => null,
                    'code_ue' => null,
                    'nom_matiere' => 'Autres pointages',
                    'niveau' => null,
                    'taux_horaire' => $defaultHourlyRate,
                    'heures' => round($genericHours, 2),
                    'montant' => round($genericMontant, 2),
                ];
                $totalHours += $genericHours;
                $totalGross += $genericMontant;
            }
        }

        $netAmount = max(0, $totalGross);

        return [
            'hourly_rate' => $defaultHourlyRate,
            'days_worked' => $daysWorked,
            'hours_worked' => round($totalHours, 2),
            'total_late_minutes' => 0,
            'gross_amount' => round($totalGross, 2),
            'late_penalty' => 0,
            'net_amount' => round($netAmount, 2),
            'ue_breakdown' => $ueBreakdown,
        ];
    }
}
