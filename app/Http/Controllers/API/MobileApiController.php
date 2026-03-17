<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ManualDeduction;
use App\Helpers\PayrollCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MobileApiController extends Controller
{
    /**
     * Get user salary status for current month
     * GET /api/user/salary-status
     */
    public function getSalaryStatus(Request $request)
    {
        try {
            $user = $request->user();

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            $isVacataire = $user->employee_type === 'enseignant_vacataire';

            // Récupérer les déductions manuelles détaillées
            $manualDeductions = ManualDeduction::with(['appliedBy'])
                ->where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', 'active')
                ->get();

            $manualDeductionsTotal = $manualDeductions->sum('amount');

            $manualDeductionsDetails = $manualDeductions->map(function ($deduction) {
                return [
                    'id' => $deduction->id,
                    'amount' => $deduction->amount,
                    'reason' => $deduction->reason,
                    'applied_by' => $deduction->appliedBy ? $deduction->appliedBy->full_name : 'N/A',
                    'applied_at' => $deduction->created_at->format('d/m/Y H:i'),
                ];
            });

            if ($isVacataire) {
                // Calcul spécifique vacataire : basé sur les heures et le taux horaire
                $payroll = PayrollCalculator::calculateVacatairePayroll($user, $year, $month);

                // Calculer les jours programmés via l'emploi du temps
                $scheduledDays = $this->getVacataireScheduledDays($user, $year, $month);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'full_name' => $user->full_name,
                            'email' => $user->email,
                            'employee_type' => $user->employee_type,
                            'employee_id' => $user->employee_id,
                        ],
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'month_name' => Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY'),
                        ],
                        'is_vacataire' => true,
                        'salary' => [
                            'hourly_rate' => $payroll['hourly_rate'] ?? 0,
                            'hours_worked' => $payroll['hours_worked'] ?? 0,
                            'gross_salary' => $payroll['gross_amount'] ?? 0,
                            'net_salary' => max(0, ($payroll['net_amount'] ?? 0) - $manualDeductionsTotal),
                            'total_deductions' => ($payroll['late_penalty'] ?? 0) + $manualDeductionsTotal,
                        ],
                        'attendance' => [
                            'scheduled_days' => $scheduledDays['total_scheduled_days'],
                            'days_worked' => $payroll['days_worked'] ?? 0,
                            'days_missed' => max(0, $scheduledDays['past_scheduled_days'] - ($payroll['days_worked'] ?? 0)),
                            'working_days' => $scheduledDays['total_scheduled_days'],
                            'days_not_worked' => max(0, $scheduledDays['past_scheduled_days'] - ($payroll['days_worked'] ?? 0)),
                            'days_justified' => 0,
                            'days_without_checkout' => 0,
                        ],
                        'lateness' => [
                            'total_late_minutes' => $payroll['total_late_minutes'] ?? 0,
                            'late_minutes_justified' => 0,
                            'late_penalty_amount' => $payroll['late_penalty'] ?? 0,
                        ],
                        'deductions' => [
                            'late_penalty_amount' => $payroll['late_penalty'] ?? 0,
                            'absence_deduction' => 0,
                            'manual_deductions' => $manualDeductionsTotal,
                            'manual_deductions_details' => $manualDeductionsDetails,
                        ],
                        'ue_summary' => $scheduledDays['ue_summary'],
                    ],
                ]);
            }

            // Calcul standard pour permanents et semi-permanents
            $payroll = PayrollCalculator::calculatePayroll($user, $year, $month);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'employee_type' => $user->employee_type,
                        'employee_id' => $user->employee_id,
                    ],
                    'period' => [
                        'month' => $month,
                        'year' => $year,
                        'month_name' => Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY'),
                    ],
                    'is_vacataire' => false,
                    'salary' => [
                        'monthly_salary' => $payroll['monthly_salary'] ?? 0,
                        'gross_salary' => $payroll['gross_salary'] ?? 0,
                        'net_salary' => $payroll['net_salary'] ?? 0,
                        'total_deductions' => $payroll['total_deductions'] ?? 0,
                    ],
                    'attendance' => [
                        'working_days' => $payroll['working_days'] ?? 0,
                        'days_worked' => $payroll['days_worked'] ?? 0,
                        'days_not_worked' => $payroll['days_not_worked'] ?? 0,
                        'days_justified' => $payroll['days_justified'] ?? 0,
                        'days_without_checkout' => $payroll['days_without_checkout'] ?? 0,
                    ],
                    'lateness' => [
                        'total_late_minutes' => $payroll['total_late_minutes'] ?? 0,
                        'late_minutes_justified' => $payroll['late_minutes_justified'] ?? 0,
                        'late_penalty_amount' => $payroll['late_penalty_amount'] ?? 0,
                    ],
                    'deductions' => [
                        'absence_deduction' => $payroll['absence_deduction'] ?? 0,
                        'late_penalty_amount' => $payroll['late_penalty_amount'] ?? 0,
                        'manual_deductions' => $payroll['manual_deductions'] ?? 0,
                        'manual_deductions_details' => $manualDeductionsDetails,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getSalaryStatus: ' . $e->getMessage(), [
                'user_id' => $request->user() ? $request->user()->id : null,
                'month' => $request->query('month'),
                'year' => $request->query('year'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul du salaire',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Calculer les jours programmés pour un vacataire basé sur son emploi du temps
     */
    private function getVacataireScheduledDays(User $user, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $today = now()->endOfDay();

        // Récupérer les UE actives du vacataire
        $ueIds = \App\Models\UniteEnseignement::where('enseignant_id', $user->id)
            ->where('statut', 'activee')
            ->pluck('id');

        // Récupérer les créneaux d'emploi du temps
        $schedules = \App\Models\UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->with('uniteEnseignement')
            ->get();

        // Jours de la semaine où le vacataire a des cours
        $scheduledWeekdays = $schedules->pluck('jour_semaine')->unique()->toArray();

        $jourToWeekday = [
            'lundi' => 1, 'mardi' => 2, 'mercredi' => 3,
            'jeudi' => 4, 'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
        ];

        // Compter les jours programmés dans le mois
        $totalScheduledDays = 0;
        $pastScheduledDays = 0;
        $date = $startDate->copy();

        while ($date->lte($endDate)) {
            $dayName = array_search($date->dayOfWeekIso, $jourToWeekday);
            if ($dayName && in_array($dayName, $scheduledWeekdays)) {
                $totalScheduledDays++;
                if ($date->lte($today)) {
                    $pastScheduledDays++;
                }
            }
            $date->addDay();
        }

        // Résumé des UE
        $ueSummary = $schedules->groupBy('unite_enseignement_id')->map(function ($items) {
            $ue = $items->first()->uniteEnseignement;
            return [
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'creneaux_par_semaine' => $items->count(),
                'jours' => $items->pluck('jour_semaine')->unique()->values()->toArray(),
            ];
        })->values();

        return [
            'total_scheduled_days' => $totalScheduledDays,
            'past_scheduled_days' => $pastScheduledDays,
            'scheduled_weekdays' => $scheduledWeekdays,
            'ue_summary' => $ueSummary,
        ];
    }

    /**
     * Get user manual deductions
     * GET /api/user/manual-deductions
     */
    public function getManualDeductions(Request $request)
    {
        $user = $request->user();

        $month = $request->query('month');
        $year = $request->query('year');

        $query = ManualDeduction::with(['appliedBy'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');

        if ($month && $year) {
            $query->where('month', $month)->where('year', $year);
        }

        $deductions = $query->get();

        $formattedDeductions = $deductions->map(function ($deduction) {
            return [
                'id' => $deduction->id,
                'amount' => $deduction->amount,
                'reason' => $deduction->reason,
                'month' => $deduction->month,
                'year' => $deduction->year,
                'period' => Carbon::create($deduction->year, $deduction->month)->locale('fr')->isoFormat('MMMM YYYY'),
                'applied_by' => $deduction->appliedBy->full_name,
                'applied_at' => $deduction->created_at->format('d/m/Y H:i'),
                'status' => $deduction->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedDeductions,
        ]);
    }

    /**
     * Get user profile info
     * GET /api/user/profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'employee_id' => $user->employee_id,
                'employee_type' => $user->employee_type,
                'monthly_salary' => $user->monthly_salary,
                'hourly_rate' => $user->hourly_rate,
                'campus' => $user->campus ? [
                    'id' => $user->campus->id,
                    'name' => $user->campus->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Get user loans
     * GET /api/user/loans
     */
    public function getLoans(Request $request)
    {
        $user = $request->user();

        $status = $request->query('status'); // active, completed, cancelled

        $query = \App\Models\Loan::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $loans = $query->get();

        $formattedLoans = $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'total_amount' => $loan->total_amount,
                'monthly_amount' => $loan->monthly_amount,
                'amount_paid' => $loan->amount_paid,
                'remaining_amount' => $loan->remaining_amount,
                'progress_percentage' => $loan->progress_percentage,
                'start_date' => $loan->start_date->format('Y-m-d'),
                'start_date_formatted' => $loan->start_date->format('d/m/Y'),
                'reason' => $loan->reason,
                'status' => $loan->status,
                'remaining_months' => $loan->remaining_months,
                'total_months' => $loan->total_months,
                'created_at' => $loan->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedLoans,
        ]);
    }
}
