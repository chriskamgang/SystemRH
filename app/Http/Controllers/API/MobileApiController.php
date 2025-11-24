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
        $user = $request->user();

        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        // Calculer la paie
        $payroll = PayrollCalculator::calculatePayroll($user, $year, $month);

        // Récupérer les déductions manuelles détaillées
        $manualDeductions = ManualDeduction::with(['appliedBy'])
            ->where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'active')
            ->get();

        $manualDeductionsDetails = $manualDeductions->map(function ($deduction) {
            return [
                'id' => $deduction->id,
                'amount' => $deduction->amount,
                'reason' => $deduction->reason,
                'applied_by' => $deduction->appliedBy->full_name,
                'applied_at' => $deduction->created_at->format('d/m/Y H:i'),
            ];
        });

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
                'salary' => [
                    'monthly_salary' => $payroll['monthly_salary'],
                    'gross_salary' => $payroll['gross_salary'],
                    'net_salary' => $payroll['net_salary'],
                    'total_deductions' => $payroll['total_deductions'],
                ],
                'attendance' => [
                    'working_days' => $payroll['working_days'],
                    'days_worked' => $payroll['days_worked'],
                    'days_not_worked' => $payroll['days_not_worked'],
                    'days_justified' => $payroll['days_justified'],
                    'days_without_checkout' => $payroll['days_without_checkout'],
                ],
                'lateness' => [
                    'total_late_minutes' => $payroll['total_late_minutes'],
                    'late_minutes_justified' => $payroll['late_minutes_justified'],
                    'late_penalty_amount' => $payroll['late_penalty_amount'],
                ],
                'deductions' => [
                    'absence_deduction' => $payroll['absence_deduction'],
                    'late_penalty_amount' => $payroll['late_penalty_amount'],
                    'manual_deductions' => $payroll['manual_deductions'],
                    'manual_deductions_details' => $manualDeductionsDetails,
                ],
            ],
        ]);
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
