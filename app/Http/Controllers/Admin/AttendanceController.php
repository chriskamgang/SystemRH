<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'campus', 'uniteEnseignement'])
            ->whereHas('user'); // Only get attendances with valid users

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('timestamp', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('timestamp', '<=', $request->end_date);
        }

        // Filter by employee
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by campus
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Filter by type (check_in/check_out)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by late status
        if ($request->filled('is_late')) {
            $query->where('is_late', $request->is_late);
        }

        // Récupérer toutes les présences
        $allAttendances = $query->orderBy('timestamp', 'desc')->get();

        // Grouper par employé
        $employeeGroups = $allAttendances->groupBy('user_id')->map(function ($userAttendances, $userId) {
            $user = $userAttendances->first()->user;

            // Grouper par date et campus pour cet employé
            $dailyAttendances = $userAttendances->groupBy(function ($attendance) {
                return $attendance->timestamp->format('Y-m-d') . '_' . $attendance->campus_id;
            })->map(function ($dayGroup) {
                $checkIn = $dayGroup->where('type', 'check-in')->first();
                $checkOut = $dayGroup->where('type', 'check-out')->first();

                return (object) [
                    'campus' => $checkIn ? $checkIn->campus : ($checkOut ? $checkOut->campus : null),
                    'date' => $checkIn ? $checkIn->timestamp : ($checkOut ? $checkOut->timestamp : null),
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'check_in_time' => $checkIn ? $checkIn->timestamp : null,
                    'check_out_time' => $checkOut ? $checkOut->timestamp : null,
                    'is_late' => $checkIn ? $checkIn->is_late : false,
                    'late_minutes' => $checkIn ? $checkIn->late_minutes : 0,
                    'is_half_day' => $checkIn ? ($checkIn->is_half_day ?? false) : false,
                    'unite_enseignement' => $checkIn ? $checkIn->uniteEnseignement : null,
                ];
            })->sortByDesc('date')->values();

            // Calculer les statistiques
            $totalDays = $dailyAttendances->count();
            $totalLate = $dailyAttendances->where('is_late', true)->where('is_half_day', false)->count();
            $totalHalfDays = $dailyAttendances->where('is_half_day', true)->count();
            $totalCheckIns = $userAttendances->where('type', 'check-in')->count();
            $totalCheckOuts = $userAttendances->where('type', 'check-out')->count();

            return (object) [
                'user' => $user,
                'total_days' => $totalDays,
                'total_check_ins' => $totalCheckIns,
                'total_check_outs' => $totalCheckOuts,
                'total_late' => $totalLate,
                'total_half_days' => $totalHalfDays,
                'late_percentage' => $totalCheckIns > 0 ? round(($totalLate / $totalCheckIns) * 100, 1) : 0,
                'attendances' => $dailyAttendances,
                'first_attendance' => $dailyAttendances->first(),
                'last_attendance' => $dailyAttendances->last(),
            ];
        })->sortBy('user.first_name')->values();

        // Paginer manuellement
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $employees = new \Illuminate\Pagination\LengthAwarePaginator(
            $employeeGroups->forPage($currentPage, $perPage),
            $employeeGroups->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get users and campuses for filters
        $users = User::where('role_id', '!=', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $campuses = Campus::orderBy('name')->get();

        return view('admin.attendances.index', compact('employees', 'users', 'campuses'));
    }

    /**
     * Export attendance data as PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = Attendance::with(['user', 'campus', 'uniteEnseignement'])
            ->whereHas('user');

        $filterParts = [];
        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date')) {
            $query->whereDate('timestamp', '>=', $request->start_date);
            $startDate = Carbon::parse($request->start_date);
            $filterParts[] = 'Depuis: ' . $startDate->format('d/m/Y');
        }
        if ($request->filled('end_date')) {
            $query->whereDate('timestamp', '<=', $request->end_date);
            $endDate = Carbon::parse($request->end_date);
            $filterParts[] = 'Jusqu\'à: ' . $endDate->format('d/m/Y');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
            $user = User::find($request->user_id);
            if ($user) $filterParts[] = 'Employé: ' . $user->full_name;
        }
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
            $campus = Campus::find($request->campus_id);
            if ($campus) $filterParts[] = 'Campus: ' . $campus->name;
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
            $filterParts[] = 'Type: ' . ($request->type === 'check-in' ? 'Entrée' : 'Sortie');
        }
        if ($request->filled('is_late')) {
            $query->where('is_late', $request->is_late);
            $filterParts[] = $request->is_late ? 'Avec retard' : 'Sans retard';
        }

        $allAttendances = $query->orderBy('timestamp', 'desc')->get();

        $employees = $allAttendances->groupBy('user_id')->map(function ($userAttendances) {
            $user = $userAttendances->first()->user;
            $totalCheckIns = $userAttendances->where('type', 'check-in')->count();
            $totalCheckOuts = $userAttendances->where('type', 'check-out')->count();

            $dailyAttendances = $userAttendances->groupBy(function ($a) {
                return $a->timestamp->format('Y-m-d') . '_' . $a->campus_id;
            });
            $totalDays = $dailyAttendances->count();
            $totalLate = $userAttendances->where('type', 'check-in')->where('is_late', true)->count();

            return (object) [
                'user' => $user,
                'total_days' => $totalDays,
                'total_check_ins' => $totalCheckIns,
                'total_check_outs' => $totalCheckOuts,
                'total_late' => $totalLate,
                'late_percentage' => $totalCheckIns > 0 ? round(($totalLate / $totalCheckIns) * 100, 1) : 0,
            ];
        })->sortBy('user.first_name')->values();

        if (!$startDate) $startDate = Carbon::now()->startOfMonth();
        if (!$endDate) $endDate = Carbon::now();
        $filters = !empty($filterParts) ? implode(' | ', $filterParts) : null;

        $pdf = Pdf::loadView('admin.attendances.pdf.report', compact('employees', 'startDate', 'endDate', 'filters'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rapport-presences-' . $startDate->format('Y-m-d') . '.pdf');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $attendance = Attendance::with(['user', 'campus'])->findOrFail($id);

        // Get related check-out if this is a check-in
        $relatedCheckout = null;
        if ($attendance->type === 'check-in') {
            $relatedCheckout = Attendance::where('user_id', $attendance->user_id)
                ->where('campus_id', $attendance->campus_id)
                ->where('type', 'check-out')
                ->whereDate('timestamp', $attendance->timestamp->format('Y-m-d'))
                ->where('timestamp', '>', $attendance->timestamp)
                ->first();
        }

        // Get related check-in if this is a check-out
        $relatedCheckin = null;
        if ($attendance->type === 'check-out') {
            $relatedCheckin = Attendance::where('user_id', $attendance->user_id)
                ->where('campus_id', $attendance->campus_id)
                ->where('type', 'check-in')
                ->whereDate('timestamp', $attendance->timestamp->format('Y-m-d'))
                ->where('timestamp', '<', $attendance->timestamp)
                ->orderBy('timestamp', 'desc')
                ->first();
        }

        return view('admin.attendances.show', compact('attendance', 'relatedCheckout', 'relatedCheckin'));
    }
}
