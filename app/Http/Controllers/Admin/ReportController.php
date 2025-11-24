<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display the reports page.
     */
    public function index(Request $request)
    {
        // Default to current month
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        // Get attendance statistics by employee
        $employeeStats = User::where('role_id', '!=', 1)
            ->with(['role']) // Remove campus relation as it doesn't exist on User model
            ->get()
            ->map(function ($user) use ($startDate, $endDate) {
                $checkins = Attendance::where('user_id', $user->id)
                    ->where('type', 'check-in')
                    ->whereBetween('timestamp', [$startDate, $endDate])
                    ->get();

                $totalDays = $checkins->count();
                $lateDays = $checkins->where('is_late', true)->count();
                $onTimeDays = $totalDays - $lateDays;

                // Calculate average late minutes
                $avgLateMinutes = $checkins->where('is_late', true)->avg('late_minutes') ?? 0;

                // Calculate work hours
                $checkouts = Attendance::where('user_id', $user->id)
                    ->where('type', 'check-out')
                    ->whereBetween('timestamp', [$startDate, $endDate])
                    ->get();

                $totalWorkHours = 0;
                foreach ($checkins as $checkin) {
                    $checkout = $checkouts->where('timestamp', '>', $checkin->timestamp)
                        ->where('campus_id', $checkin->campus_id)
                        ->first();

                    if ($checkout) {
                        $hours = $checkin->timestamp->diffInMinutes($checkout->timestamp) / 60;
                        $totalWorkHours += $hours;
                    }
                }

                return [
                    'user' => $user,
                    'total_days' => $totalDays,
                    'on_time_days' => $onTimeDays,
                    'late_days' => $lateDays,
                    'avg_late_minutes' => round($avgLateMinutes, 1),
                    'total_work_hours' => round($totalWorkHours, 1),
                    'punctuality_rate' => $totalDays > 0 ? round(($onTimeDays / $totalDays) * 100, 1) : 0,
                ];
            });

        // Get campus statistics
        $campusStats = Campus::all()->map(function ($campus) use ($startDate, $endDate) {
            $checkins = Attendance::where('campus_id', $campus->id)
                ->where('type', 'check-in')
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->get();

            $totalCheckins = $checkins->count();
            $lateCheckins = $checkins->where('is_late', true)->count();

            return [
                'campus' => $campus,
                'total_checkins' => $totalCheckins,
                'late_checkins' => $lateCheckins,
                'punctuality_rate' => $totalCheckins > 0 ? round((($totalCheckins - $lateCheckins) / $totalCheckins) * 100, 1) : 0,
            ];
        });

        // Overall statistics
        $overallStats = [
            'total_checkins' => Attendance::where('type', 'check-in')
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->count(),
            'total_late' => Attendance::where('type', 'check-in')
                ->where('is_late', true)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->count(),
            'unique_employees' => Attendance::where('type', 'check-in')
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->distinct('user_id')
                ->count(),
        ];

        $overallStats['punctuality_rate'] = $overallStats['total_checkins'] > 0
            ? round((($overallStats['total_checkins'] - $overallStats['total_late']) / $overallStats['total_checkins']) * 100, 1)
            : 0;

        return view('admin.reports.index', compact(
            'employeeStats',
            'campusStats',
            'overallStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export report data.
     */
    public function export(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $format = $request->get('format', 'csv');

        // Get attendance data
        $attendances = Attendance::with(['user', 'campus'])
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'desc')
            ->get();

        if ($format === 'csv') {
            return $this->exportCsv($attendances, $startDate, $endDate);
        }

        return redirect()->back()->with('error', 'Format non supporté');
    }

    /**
     * Export data as CSV.
     */
    private function exportCsv($attendances, $startDate, $endDate)
    {
        $filename = 'rapport_presences_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($file, [
                'Date',
                'Heure',
                'Employé',
                'Campus',
                'Type',
                'En retard',
                'Minutes de retard',
                'Latitude',
                'Longitude'
            ]);

            // Data
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->timestamp->format('Y-m-d'),
                    $attendance->timestamp->format('H:i:s'),
                    $attendance->user->full_name,
                    $attendance->campus->name,
                    $attendance->type === 'check-in' ? 'Entrée' : 'Sortie',
                    $attendance->is_late ? 'Oui' : 'Non',
                    $attendance->late_minutes ?? 0,
                    $attendance->latitude,
                    $attendance->longitude,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
