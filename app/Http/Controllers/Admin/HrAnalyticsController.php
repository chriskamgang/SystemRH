<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\HrAnalyticsSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HrAnalyticsController extends Controller
{
    /**
     * Display the HR analytics dashboard.
     */
    public function index()
    {
        $now   = Carbon::now();
        $today = $now->toDateString();

        // ----------------------------------------------------------------
        // 1. Workforce totals
        // ----------------------------------------------------------------
        $totalEmployees = User::where('is_active', true)->count();

        $employeesByType = User::where('is_active', true)
            ->selectRaw('employee_type, COUNT(*) as total')
            ->groupBy('employee_type')
            ->pluck('total', 'employee_type')
            ->toArray();

        $permanentCount      = $employeesByType['permanent']      ?? 0;
        $semiPermanentCount  = $employeesByType['semi_permanent']  ?? 0;
        $vacataireCount      = $employeesByType['vacataire']       ?? 0;

        // ----------------------------------------------------------------
        // 2. Attendance rate — last 30 days
        //    Rate = check-in records / (working days × active employees)
        //    Simplified: unique user-days with at least one check_in / total possible user-days
        // ----------------------------------------------------------------
        $thirtyDaysAgo = $now->copy()->subDays(30)->startOfDay();

        $checkInsLast30 = Attendance::where('type', 'check_in')
            ->where('timestamp', '>=', $thirtyDaysAgo)
            ->count();

        // Unique (user, date) pairs
        $uniqueUserDays = Attendance::where('type', 'check_in')
            ->where('timestamp', '>=', $thirtyDaysAgo)
            ->selectRaw('user_id, DATE(timestamp) as day')
            ->distinct()
            ->get()
            ->count();

        // Max possible: 22 working days × total active employees
        $workingDays    = 22;
        $maxPossible    = $totalEmployees * $workingDays;
        $attendanceRate = $maxPossible > 0
            ? round(($uniqueUserDays / $maxPossible) * 100, 1)
            : 0;

        // Average late rate
        $lateCount = Attendance::where('type', 'check_in')
            ->where('timestamp', '>=', $thirtyDaysAgo)
            ->where('is_late', true)
            ->count();

        $lateRate = $checkInsLast30 > 0
            ? round(($lateCount / $checkInsLast30) * 100, 1)
            : 0;

        // ----------------------------------------------------------------
        // 3. Leave requests stats
        // ----------------------------------------------------------------
        $currentMonthStart = $now->copy()->startOfMonth();

        $leaveStats = LeaveRequest::selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $pendingLeaves  = $leaveStats['pending']  ?? 0;
        $approvedLeaves = $leaveStats['approved'] ?? 0;
        $rejectedLeaves = $leaveStats['rejected'] ?? 0;

        $leavesThisMonth = LeaveRequest::where('created_at', '>=', $currentMonthStart)->count();

        // ----------------------------------------------------------------
        // 4. New hires vs departures (soft-deleted) — last 90 days
        // ----------------------------------------------------------------
        $ninetyDaysAgo = $now->copy()->subDays(90);

        $newHires = User::where('created_at', '>=', $ninetyDaysAgo)->count();

        $departures = User::onlyTrashed()
            ->where('deleted_at', '>=', $ninetyDaysAgo)
            ->count();

        // ----------------------------------------------------------------
        // 5. Department headcount (active departments, active employees)
        // ----------------------------------------------------------------
        $departments = Department::with([
            'users' => fn($q) => $q->where('is_active', true),
        ])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

        $deptLabels  = $departments->pluck('name')->toArray();
        $deptCounts  = $departments->map(fn($d) => $d->users->count())->toArray();

        // ----------------------------------------------------------------
        // 6. Attendance trend — last 12 months (check-in count per month)
        // ----------------------------------------------------------------
        $trendData = Attendance::where('type', 'check_in')
            ->where('timestamp', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(timestamp, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill in all 12 months so the chart has no gaps
        $trendLabels = [];
        $trendValues = [];
        for ($i = 11; $i >= 0; $i--) {
            $key           = $now->copy()->subMonths($i)->format('Y-m');
            $label         = $now->copy()->subMonths($i)->translatedFormat('M Y');
            $trendLabels[] = $label;
            $trendValues[] = $trendData[$key] ?? 0;
        }

        // ----------------------------------------------------------------
        // 7. Latest snapshot (if exists)
        // ----------------------------------------------------------------
        $latestSnapshot = HrAnalyticsSnapshot::orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        return view('admin.analytics.index', compact(
            'totalEmployees',
            'permanentCount',
            'semiPermanentCount',
            'vacataireCount',
            'attendanceRate',
            'lateRate',
            'checkInsLast30',
            'pendingLeaves',
            'approvedLeaves',
            'rejectedLeaves',
            'leavesThisMonth',
            'newHires',
            'departures',
            'deptLabels',
            'deptCounts',
            'trendLabels',
            'trendValues',
            'latestSnapshot'
        ));
    }
}
