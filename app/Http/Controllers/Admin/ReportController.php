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

        // Filtre par plage horaire
        $shift = $request->get('shift');

        // Get attendance statistics by employee
        $employeeStats = User::where('role_id', '!=', 1)
            ->with(['role']) // Remove campus relation as it doesn't exist on User model
            ->get()
            ->map(function ($user) use ($startDate, $endDate, $shift) {
                $checkinsQuery = Attendance::where('user_id', $user->id)
                    ->where('type', 'check-in')
                    ->whereBetween('timestamp', [$startDate, $endDate]);

                // Appliquer le filtre de plage horaire
                if ($shift === 'morning') {
                    // Matin: avant 17h00
                    $checkinsQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
                } elseif ($shift === 'evening') {
                    // Soir: après 17h00
                    $checkinsQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
                }

                $checkins = $checkinsQuery->get();

                $totalDays = $checkins->count();
                $lateDays = $checkins->where('is_late', true)->count();
                $onTimeDays = $totalDays - $lateDays;

                // Calculate average late minutes
                $avgLateMinutes = $checkins->where('is_late', true)->avg('late_minutes') ?? 0;

                // Calculate work hours
                $checkoutsQuery = Attendance::where('user_id', $user->id)
                    ->where('type', 'check-out')
                    ->whereBetween('timestamp', [$startDate, $endDate]);

                // Appliquer le même filtre de plage horaire
                if ($shift === 'morning') {
                    $checkoutsQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
                } elseif ($shift === 'evening') {
                    $checkoutsQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
                }

                $checkouts = $checkoutsQuery->get();

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
        $campusStats = Campus::all()->map(function ($campus) use ($startDate, $endDate, $shift) {
            $checkinsQuery = Attendance::where('campus_id', $campus->id)
                ->where('type', 'check-in')
                ->whereBetween('timestamp', [$startDate, $endDate]);

            // Appliquer le filtre de plage horaire
            if ($shift === 'morning') {
                $checkinsQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
            } elseif ($shift === 'evening') {
                $checkinsQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
            }

            $checkins = $checkinsQuery->get();

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
        $totalCheckinsQuery = Attendance::where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate]);
        $totalLateQuery = Attendance::where('type', 'check-in')
            ->where('is_late', true)
            ->whereBetween('timestamp', [$startDate, $endDate]);
        $uniqueEmployeesQuery = Attendance::where('type', 'check-in')
            ->whereBetween('timestamp', [$startDate, $endDate]);

        // Appliquer le filtre de plage horaire
        if ($shift === 'morning') {
            $totalCheckinsQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
            $totalLateQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
            $uniqueEmployeesQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
        } elseif ($shift === 'evening') {
            $totalCheckinsQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
            $totalLateQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
            $uniqueEmployeesQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
        }

        $overallStats = [
            'total_checkins' => $totalCheckinsQuery->count(),
            'total_late' => $totalLateQuery->count(),
            'unique_employees' => $uniqueEmployeesQuery->distinct('user_id')->count(),
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

        $shift = $request->get('shift');
        $format = $request->get('format', 'csv');

        // Get attendance data
        $attendancesQuery = Attendance::with(['user', 'campus'])
            ->whereBetween('timestamp', [$startDate, $endDate]);

        // Appliquer le filtre de plage horaire
        if ($shift === 'morning') {
            $attendancesQuery->whereRaw('TIME(timestamp) < ?', ['17:00:00']);
        } elseif ($shift === 'evening') {
            $attendancesQuery->whereRaw('TIME(timestamp) >= ?', ['17:00:00']);
        }

        $attendances = $attendancesQuery->orderBy('timestamp', 'desc')->get();

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
                'Plage horaire',
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
                // Déterminer la plage horaire
                $hour = (int) $attendance->timestamp->format('H');
                $shiftLabel = $hour < 17 ? 'Matin' : 'Soir';

                fputcsv($file, [
                    $attendance->timestamp->format('Y-m-d'),
                    $attendance->timestamp->format('H:i:s'),
                    $shiftLabel,
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
