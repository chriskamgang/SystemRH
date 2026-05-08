<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Tardiness;
use App\Models\Absence;
use App\Models\LeaveRequest;
use App\Models\Department;
use App\Models\TrainingEnrollment;
use App\Models\JobPosting;
use App\Models\Evaluation;
use App\Models\HrAnalyticsSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrAnalyticsController extends Controller
{
    /**
     * Dashboard RH global (temps reel)
     */
    public function dashboard(Request $request)
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        // Effectifs
        $totalEmployees = User::where('is_active', true)->count();
        $byType = User::where('is_active', true)
            ->select('employee_type', DB::raw('count(*) as count'))
            ->groupBy('employee_type')
            ->pluck('count', 'employee_type');

        $byDepartment = User::where('users.is_active', true)
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.name')
            ->pluck('count', 'name');

        // Presences du jour
        $todayAttendances = Attendance::whereDate('timestamp', today())->count();
        $todayPresent = Attendance::whereDate('timestamp', today())
            ->where('type', 'check-in')
            ->distinct('user_id')
            ->count('user_id');

        // Retards du mois
        $monthlyLateCount = Tardiness::whereBetween('created_at', [$startOfMonth, $now])->count();

        // Absences du mois
        $monthlyAbsenceCount = Absence::whereBetween('date', [$startOfMonth, $now])->count();

        // Conges en cours
        $activeLeaves = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->count();

        // Formations
        $activeTrainings = TrainingEnrollment::whereIn('status', ['enrolled', 'in_progress'])->count();
        $completedTrainings = TrainingEnrollment::where('status', 'completed')
            ->whereBetween('completed_at', [$startOfMonth, $now])
            ->count();

        // Recrutement
        $openPositions = JobPosting::where('status', 'published')->count();
        $monthlyApplications = DB::table('job_applications')
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->count();

        return response()->json([
            'success' => true,
            'dashboard' => [
                'workforce' => [
                    'total' => $totalEmployees,
                    'by_type' => $byType,
                    'by_department' => $byDepartment,
                ],
                'attendance_today' => [
                    'present' => $todayPresent,
                    'total' => $totalEmployees,
                    'rate' => $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100, 1) : 0,
                ],
                'monthly' => [
                    'late_count' => $monthlyLateCount,
                    'absence_count' => $monthlyAbsenceCount,
                    'active_leaves' => $activeLeaves,
                ],
                'training' => [
                    'active_enrollments' => $activeTrainings,
                    'completions_this_month' => $completedTrainings,
                ],
                'recruitment' => [
                    'open_positions' => $openPositions,
                    'applications_this_month' => $monthlyApplications,
                ],
            ],
        ]);
    }

    /**
     * Tendances mensuelles (6 derniers mois)
     */
    public function trends(Request $request)
    {
        $months = (int) $request->query('months', 6);
        $months = min($months, 12);

        $snapshots = HrAnalyticsSnapshot::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit($months)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($s) {
                $monthNames = ['', 'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec'];
                return [
                    'period' => $monthNames[$s->month] . ' ' . $s->year,
                    'year' => $s->year,
                    'month' => $s->month,
                    'total_employees' => $s->total_employees,
                    'new_hires' => $s->new_hires,
                    'departures' => $s->departures,
                    'turnover_rate' => $s->turnover_rate,
                    'avg_attendance_rate' => $s->avg_attendance_rate,
                    'avg_late_rate' => $s->avg_late_rate,
                    'total_leave_days' => $s->total_leave_days,
                    'total_payroll' => $s->total_payroll,
                    'avg_evaluation_score' => $s->avg_evaluation_score,
                    'training_completions' => $s->training_completions,
                    'open_positions' => $s->open_positions,
                ];
            });

        return response()->json(['success' => true, 'trends' => $snapshots]);
    }

    /**
     * Stats departement specifique
     */
    public function departmentStats($departmentId)
    {
        $department = Department::findOrFail($departmentId);

        $employees = User::where('department_id', $departmentId)
            ->where('is_active', true);

        $total = $employees->count();

        $todayPresent = Attendance::whereDate('timestamp', today())
            ->where('type', 'check-in')
            ->whereIn('user_id', User::where('department_id', $departmentId)->pluck('id'))
            ->distinct('user_id')
            ->count('user_id');

        $monthlyLate = Tardiness::whereBetween('created_at', [now()->startOfMonth(), now()])
            ->whereIn('user_id', User::where('department_id', $departmentId)->pluck('id'))
            ->count();

        $avgEvalScore = Evaluation::whereIn('employee_id', User::where('department_id', $departmentId)->pluck('id'))
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        return response()->json([
            'success' => true,
            'department' => $department->name,
            'stats' => [
                'total_employees' => $total,
                'present_today' => $todayPresent,
                'attendance_rate' => $total > 0 ? round(($todayPresent / $total) * 100, 1) : 0,
                'monthly_late_count' => $monthlyLate,
                'avg_evaluation_score' => $avgEvalScore ? round($avgEvalScore, 2) : null,
            ],
        ]);
    }
}
