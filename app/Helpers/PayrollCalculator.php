<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Attendance;
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
     */
    public static function calculateAttendanceStats(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Récupérer toutes les présences du mois
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        // Grouper par date ET par plage (matin/soir)
        $groupedByDateShift = $attendances->groupBy(function ($attendance) {
            $shift = $attendance->shift ?? 'morning'; // Par défaut morning pour anciennes données
            return $attendance->timestamp->format('Y-m-d') . '_' . $shift;
        });

        $daysWorked = 0;
        $totalLateMinutes = 0;
        $daysWithoutCheckout = 0;

        foreach ($groupedByDateShift as $dateShift => $shiftAttendances) {
            list($date, $shift) = explode('_', $dateShift);

            $checkIn = $shiftAttendances->where('type', 'check-in')->first();
            $checkOut = $shiftAttendances->where('type', 'check-out')->first();

            if ($checkIn) {
                // Déterminer la valeur du jour selon la plage et le jour de la semaine
                $carbonDate = Carbon::parse($date);

                // Pour le matin: jour complet ou demi-journée selon le jour
                // Pour le soir: toujours compter comme présence (valeur à définir)
                if ($shift === 'morning') {
                    $dayValue = $carbonDate->dayOfWeek == 6 ? 0.5 : 1;
                } else {
                    // Soir: compter comme 0.5 jour par exemple
                    $dayValue = 0.5;
                }

                $daysWorked += $dayValue;

                // Compter les retards (ignorer les valeurs négatives = bug d'anciennes données)
                if ($checkIn->is_late && $checkIn->late_minutes > 0) {
                    $totalLateMinutes += $checkIn->late_minutes;
                }

                // Compter les plages sans checkout
                if (!$checkOut) {
                    $daysWithoutCheckout++;
                }
            }
        }

        return [
            'days_worked' => $daysWorked,
            'total_late_minutes' => $totalLateMinutes,
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
        // 1. Récupérer les paramètres
        $penaltyPerSecond = (float) Setting::get('penalty_per_second', 0.50);
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

        // 5. Récupérer les statistiques de présence
        $attendanceStats = self::calculateAttendanceStats($user, $year, $month);
        $daysWorked = $attendanceStats['days_worked'];
        $totalLateMinutes = $attendanceStats['total_late_minutes'];
        $daysWithoutCheckout = $attendanceStats['days_without_checkout'];

        // 6. Calculer les jours non travaillés
        $daysNotWorked = $workingDaysInMonth - $daysWorked;

        // 7. Récupérer les justifications
        $justifications = self::getJustifications($user, $year, $month);
        $daysJustified = $justifications['days_justified'];
        $lateMinutesJustified = $justifications['late_minutes_justified'];

        // 8. Calculer les pénalités de retard (en utilisant les secondes)
        $totalLateSeconds = ($totalLateMinutes - $lateMinutesJustified) * 60;
        $latePenaltyAmount = max(0, $totalLateSeconds * $penaltyPerSecond);

        // 9. Calculer les déductions pour absences (jours non travaillés et non justifiés)
        $daysToDeduct = max(0, $daysNotWorked - $daysJustified);
        $absenceDeduction = $daysToDeduct * $dailyRate;

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

        // 12. Calculer le salaire net
        $grossSalary = $monthlySalary;
        $totalDeductions = $latePenaltyAmount + $absenceDeduction + $totalManualDeductions + $totalLoanDeductions;
        $netSalary = max(0, $grossSalary - $totalDeductions);

        return [
            'monthly_salary' => $monthlySalary,
            'working_days' => $workingDaysInMonth,
            'days_worked' => $daysWorked,
            'days_not_worked' => $daysNotWorked,
            'days_justified' => $daysJustified,
            'total_late_minutes' => $totalLateMinutes,
            'late_minutes_justified' => $lateMinutesJustified,
            'manual_deductions' => $totalManualDeductions,
            'manual_deductions_details' => $manualDeductions,
            'loan_deductions' => $totalLoanDeductions,
            'loan_deductions_details' => $loanDeductionsDetails,
            'late_penalty_amount' => $latePenaltyAmount,
            'absence_deduction' => $absenceDeduction,
            'gross_salary' => $grossSalary,
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

        // Récupérer le taux horaire
        $hourlyRate = (float) $user->hourly_rate;

        if ($hourlyRate <= 0) {
            return [
                'hourly_rate' => 0,
                'days_worked' => 0,
                'hours_worked' => 0,
                'total_late_minutes' => 0,
                'gross_amount' => 0,
                'late_penalty' => 0,
                'net_amount' => 0,
            ];
        }

        // Récupérer toutes les présences
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        // Grouper par date ET par plage (matin/soir)
        $groupedByDateShift = $attendances->groupBy(function ($attendance) {
            $shift = $attendance->shift ?? 'morning';
            return $attendance->timestamp->format('Y-m-d') . '_' . $shift;
        });

        $totalHours = 0;
        $totalLateMinutes = 0;
        $daysWorked = 0;

        foreach ($groupedByDateShift as $dateShift => $shiftAttendances) {
            list($date, $shift) = explode('_', $dateShift);

            $checkIn = $shiftAttendances->where('type', 'check-in')->first();
            $checkOut = $shiftAttendances->where('type', 'check-out')->first();

            if ($checkIn && $checkOut) {
                $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp, true);
                $totalHours += $hoursWorked;

                $carbonDate = Carbon::parse($date);
                // Chaque plage (matin ou soir) compte comme 0.5 jour
                $dayValue = 0.5;
                $daysWorked += $dayValue;
            } elseif ($checkIn) {
                // Si pas de checkout, compter les heures par défaut selon la plage
                if ($shift === 'morning') {
                    $totalHours += 8; // 8h pour le matin
                } else {
                    $totalHours += 3.5; // 3h30 pour le soir
                }

                $carbonDate = Carbon::parse($date);
                $dayValue = 0.5;
                $daysWorked += $dayValue;
            }

            // Compter les retards (ignorer les valeurs négatives = bug d'anciennes données)
            if ($checkIn && $checkIn->is_late && $checkIn->late_minutes > 0) {
                $totalLateMinutes += $checkIn->late_minutes;
            }
        }

        // Calculer le montant brut
        $grossAmount = $totalHours * $hourlyRate;

        // Pénalité retards
        $penaltyPerSecond = (float) Setting::get('penalty_per_second', 0.50);
        $latePenalty = ($totalLateMinutes * 60) * $penaltyPerSecond;

        // Montant net
        $netAmount = max(0, $grossAmount - $latePenalty);

        return [
            'hourly_rate' => $hourlyRate,
            'days_worked' => $daysWorked,
            'hours_worked' => $totalHours,
            'total_late_minutes' => $totalLateMinutes,
            'gross_amount' => $grossAmount,
            'late_penalty' => $latePenalty,
            'net_amount' => $netAmount,
        ];
    }
}
