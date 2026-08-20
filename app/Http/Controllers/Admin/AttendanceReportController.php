<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Campus;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceReportController extends Controller
{
    private function getReportData(Request $request): array
    {
        $period = $request->get('period', 'day');
        $date = $request->get('date', today()->toDateString());
        $userId = $request->get('user_id');
        $campusId = $request->get('campus_id');

        $baseDate = Carbon::parse($date);

        switch ($period) {
            case 'week':
                $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
                break;
            case 'month':
                $startDate = $baseDate->copy()->startOfMonth();
                $endDate = $baseDate->copy()->endOfMonth();
                break;
            default:
                $startDate = $baseDate->copy()->startOfDay();
                $endDate = $baseDate->copy()->endOfDay();
                break;
        }

        $query = Attendance::with(['user', 'campus', 'uniteEnseignement'])
            ->whereHas('user')
            ->whereBetween('timestamp', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        $attendances = $query->orderBy('timestamp', 'asc')->get();

        $report = $attendances->groupBy('user_id')->map(function ($userAttendances) {
            $user = $userAttendances->first()->user;

            $dailyData = $userAttendances->groupBy(function ($a) {
                return $a->timestamp->format('Y-m-d');
            })->map(function ($dayGroup, $dateKey) use ($user) {
                return $this->buildDayData($dayGroup, $dateKey, $user);
            })->sortKeys();

            $totalMinutes = $dailyData->sum('worked_minutes');
            $totalLate = $dailyData->where('is_late', true)->count();
            $daysWorked = $dailyData->count();

            return (object) [
                'user' => $user,
                'days' => $dailyData->values(),
                'total_minutes' => $totalMinutes,
                'total_hours' => $this->formatDuration($totalMinutes),
                'total_late' => $totalLate,
                'days_worked' => $daysWorked,
                'avg_minutes' => $daysWorked > 0 ? round($totalMinutes / $daysWorked) : 0,
                'avg_hours' => $daysWorked > 0 ? $this->formatDuration(round($totalMinutes / $daysWorked)) : '0h 00min',
            ];
        })->sortBy('user.first_name')->values();

        $globalMinutes = $report->sum('total_minutes');
        $globalDays = $report->sum('days_worked');
        $globalLate = $report->sum('total_late');

        return compact(
            'report', 'period', 'startDate', 'endDate', 'date',
            'userId', 'campusId', 'globalMinutes', 'globalDays', 'globalLate'
        );
    }

    private function getPeriodLabel(string $period, Carbon $startDate, Carbon $endDate): string
    {
        return match($period) {
            'day' => $startDate->locale('fr')->isoFormat('dddd DD MMMM YYYY'),
            'week' => $startDate->format('d') . ' - ' . $endDate->locale('fr')->isoFormat('DD MMMM YYYY'),
            'month' => $startDate->locale('fr')->isoFormat('MMMM YYYY'),
            default => $startDate->format('d/m/Y'),
        };
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);

        $users = User::where('role_id', '!=', 1)->orderBy('first_name')->get();
        $campuses = Campus::orderBy('name')->get();

        return view('admin.attendance-report.index', array_merge($data, compact('users', 'campuses')));
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getReportData($request);
        $data['periodLabel'] = $this->getPeriodLabel($data['period'], $data['startDate'], $data['endDate']);

        $pdf = Pdf::loadView('admin.attendance-report.pdf', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'rapport-pointages-' . $data['period'] . '-' . $data['startDate']->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getReportData($request);
        $periodLabel = $this->getPeriodLabel($data['period'], $data['startDate'], $data['endDate']);

        $filename = 'rapport-pointages-' . $data['period'] . '-' . $data['startDate']->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $periodLabel) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header info
            fputcsv($file, ['Rapport de Pointages - ' . $periodLabel], ';');
            fputcsv($file, ['Periode: ' . $data['startDate']->format('d/m/Y') . ' au ' . $data['endDate']->format('d/m/Y')], ';');
            fputcsv($file, [], ';');

            // Column headers
            fputcsv($file, ['Employe', 'Date', 'Jour', 'Campus', 'Entree', 'Sortie', 'Duree session', 'Total jour', 'Statut'], ';');

            foreach ($data['report'] as $entry) {
                foreach ($entry->days as $day) {
                    foreach ($day['sessions'] as $sIndex => $session) {
                        fputcsv($file, [
                            $entry->user->full_name,
                            $day['date']->format('d/m/Y'),
                            $day['date']->locale('fr')->isoFormat('dddd'),
                            $session->campus ? $session->campus->name : '-',
                            $session->check_in ? $session->check_in->timestamp->format('H:i') : '-',
                            $session->check_out ? $session->check_out->timestamp->format('H:i') : '-',
                            $session->minutes > 0 ? $session->duration : '-',
                            $sIndex === 0 ? $day['worked_hours'] : '',
                            $sIndex === 0 ? ($day['is_late'] ? 'Retard ' . $day['late_minutes'] . ' min' : 'A l\'heure') : '',
                        ], ';');
                    }
                }
                // Total line per employee
                fputcsv($file, [
                    $entry->user->full_name . ' - TOTAL',
                    '', '', '', '', '',
                    $entry->total_hours,
                    $entry->days_worked . ' jour(s)',
                    'Moy: ' . $entry->avg_hours,
                ], ';');
                fputcsv($file, [], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildDayData($dayGroup, $dateKey, $user)
    {
        $checkIns = $dayGroup->where('type', 'check-in')->sortBy('timestamp')->values();
        $checkOuts = $dayGroup->where('type', 'check-out')->sortBy('timestamp')->values();

        $usedCheckOuts = [];
        $sessions = [];

        foreach ($checkIns as $ci) {
            $matchedCo = null;
            foreach ($checkOuts as $co) {
                if (!in_array($co->id, $usedCheckOuts)
                    && $co->campus_id === $ci->campus_id
                    && $co->timestamp->isAfter($ci->timestamp)) {
                    $matchedCo = $co;
                    $usedCheckOuts[] = $co->id;
                    break;
                }
            }

            $minutes = 0;
            if ($matchedCo) {
                $minutes = $this->calculateSessionMinutes($ci, $matchedCo, $user->employee_type);
            }

            $sessions[] = (object) [
                'check_in' => $ci,
                'check_out' => $matchedCo,
                'minutes' => $minutes,
                'duration' => $this->formatDuration($minutes),
                'campus' => $ci->campus,
                'ue' => $ci->uniteEnseignement,
            ];
        }

        // Orphan check-outs
        foreach ($checkOuts as $co) {
            if (!in_array($co->id, $usedCheckOuts)) {
                $sessions[] = (object) [
                    'check_in' => null,
                    'check_out' => $co,
                    'minutes' => 0,
                    'duration' => '-',
                    'campus' => $co->campus,
                    'ue' => null,
                ];
            }
        }

        $totalMinutes = collect($sessions)->sum('minutes');
        $firstCheckIn = $checkIns->first();

        return [
            'date' => Carbon::parse($dateKey),
            'sessions' => $sessions,
            'worked_minutes' => $totalMinutes,
            'worked_hours' => $this->formatDuration($totalMinutes),
            'is_late' => $firstCheckIn ? (bool) $firstCheckIn->is_late : false,
            'late_minutes' => $firstCheckIn ? $firstCheckIn->late_minutes : 0,
        ];
    }

    private function calculateSessionMinutes(Attendance $checkIn, Attendance $checkOut, ?string $employeeType): int
    {
        $effIn = $checkIn->timestamp->copy();
        $effOut = $checkOut->timestamp->copy();

        $wStart = $effIn->copy()->setTime(8, 0, 0);
        $wEnd = $effIn->copy()->setTime(17, 0, 0);

        if ($effIn->lt($wStart)) $effIn = $wStart;
        if ($checkIn->timestamp->hour < 17 && $effOut->gt($wEnd)) $effOut = $wEnd;

        $mins = $effIn->diffInMinutes($effOut);
        $breakMins = NotificationSetting::calculateBreakOverlapMinutes($effIn, $effOut, $employeeType);

        return max(0, $mins - $breakMins);
    }

    private function formatDuration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return "{$h}h " . str_pad($m, 2, '0', STR_PAD_LEFT) . "min";
    }
}
