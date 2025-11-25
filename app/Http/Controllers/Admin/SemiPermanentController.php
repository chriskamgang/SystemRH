<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SemiPermanentController extends Controller
{
    /**
     * Display a listing of semi-permanents.
     */
    public function index(Request $request)
    {
        // Récupérer tous les semi-permanents
        $query = User::where('employee_type', 'semi_permanent')
            ->with(['department', 'campuses']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $semiPermanents = $query->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20);

        $campuses = Campus::orderBy('name')->get();

        return view('admin.semi-permanents.index', compact('semiPermanents', 'campuses'));
    }

    /**
     * Display the specified semi-permanent.
     */
    public function show(string $id)
    {
        $semiPermanent = User::with(['campuses', 'department'])->findOrFail($id);

        // Vérifier que c'est bien un semi-permanent
        if ($semiPermanent->employee_type !== 'semi_permanent') {
            return redirect()->route('admin.semi-permanents.index')
                ->with('error', 'Cet employé n\'est pas un semi-permanent.');
        }

        // Statistiques du mois en cours
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
            ->orderBy('timestamp', 'desc')
            ->get();

        // Calculer les heures travaillées
        $groupedAttendances = $attendances->groupBy(function ($attendance) {
            return $attendance->timestamp->format('Y-m-d');
        });

        $totalHours = 0;
        $totalDays = 0;
        foreach ($groupedAttendances as $date => $dayAttendances) {
            $checkIn = $dayAttendances->where('type', 'check-in')->first();
            $checkOut = $dayAttendances->where('type', 'check-out')->first();

            if ($checkIn && $checkOut) {
                $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp);
                $totalHours += $hoursWorked;
                $totalDays++;
            }
        }

        // Calculer le volume horaire attendu pour le mois
        $volumeHoraireHebdo = $semiPermanent->volume_horaire_hebdomadaire ?? 0;
        $weeksInMonth = Carbon::now()->weeksInMonth();
        $expectedHours = $volumeHoraireHebdo * $weeksInMonth;

        // Calculer le taux de réalisation
        $realizationRate = $expectedHours > 0 ? ($totalHours / $expectedHours) * 100 : 0;

        return view('admin.semi-permanents.show', compact(
            'semiPermanent',
            'totalHours',
            'totalDays',
            'expectedHours',
            'realizationRate',
            'attendances'
        ));
    }

    /**
     * Display payments management page.
     */
    public function payments(Request $request)
    {
        // Filtres de période
        $month = $request->filled('month') ? (int) explode('-', $request->month)[1] : Carbon::now()->month;
        $year = $request->filled('month') ? (int) explode('-', $request->month)[0] : Carbon::now()->year;

        $query = User::where('employee_type', 'semi_permanent')
            ->whereNotNull('monthly_salary')
            ->where('monthly_salary', '>', 0)
            ->with(['department', 'campuses']);

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        $semiPermanents = $query->get()->map(function ($semiPermanent) use ($year, $month) {
            // Calculer les heures travaillées du mois
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

            $attendances = Attendance::where('user_id', $semiPermanent->id)
                ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
                ->get()
                ->groupBy(function ($attendance) {
                    return $attendance->timestamp->format('Y-m-d');
                });

            $totalHours = 0;
            $totalDays = 0;
            foreach ($attendances as $date => $dayAttendances) {
                $checkIn = $dayAttendances->where('type', 'check-in')->first();
                $checkOut = $dayAttendances->where('type', 'check-out')->first();

                if ($checkIn && $checkOut) {
                    $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp);
                    $totalHours += $hoursWorked;
                    $totalDays++;
                }
            }

            // Calculer le volume horaire attendu
            $volumeHoraireHebdo = $semiPermanent->volume_horaire_hebdomadaire ?? 0;
            $weeksInMonth = Carbon::create($year, $month, 1)->weeksInMonth();
            $expectedHours = $volumeHoraireHebdo * $weeksInMonth;

            // Taux de réalisation
            $realizationRate = $expectedHours > 0 ? ($totalHours / $expectedHours) * 100 : 0;

            // Ajouter les données calculées
            $semiPermanent->hours_worked = $totalHours;
            $semiPermanent->days_worked = $totalDays;
            $semiPermanent->expected_hours = $expectedHours;
            $semiPermanent->realization_rate = $realizationRate;
            $semiPermanent->payment_amount = $semiPermanent->monthly_salary; // Salaire fixe

            return $semiPermanent;
        });

        // Statistiques globales
        $totalSemiPermanents = $semiPermanents->count();
        $totalHours = $semiPermanents->sum('hours_worked');
        $totalCost = $semiPermanents->sum('payment_amount');
        $averageRealization = $semiPermanents->avg('realization_rate');

        $campuses = Campus::orderBy('name')->get();
        $monthFormatted = Carbon::create($year, $month)->format('Y-m');

        return view('admin.semi-permanents.payments', compact(
            'semiPermanents',
            'campuses',
            'monthFormatted',
            'year',
            'month',
            'totalSemiPermanents',
            'totalHours',
            'totalCost',
            'averageRealization'
        ));
    }

    /**
     * Display semi-permanents report.
     */
    public function report(Request $request)
    {
        // Filtres
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $query = User::where('employee_type', 'semi_permanent')
            ->with(['campuses']);

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        $semiPermanents = $query->get()->map(function ($semiPermanent) use ($startDate, $endDate) {
            // Statistiques
            $attendances = Attendance::where('user_id', $semiPermanent->id)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($attendance) {
                    return $attendance->timestamp->format('Y-m-d');
                });

            $totalDays = $attendances->count();
            $totalHours = 0;
            $totalLate = 0;

            foreach ($attendances as $date => $dayAttendances) {
                $checkIn = $dayAttendances->where('type', 'check-in')->first();
                $checkOut = $dayAttendances->where('type', 'check-out')->first();

                if ($checkIn && $checkOut) {
                    $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp);
                    $totalHours += $hoursWorked;
                }

                if ($checkIn && $checkIn->is_late) {
                    $totalLate++;
                }
            }

            // Calculer le volume horaire attendu pour la période
            $daysInPeriod = $startDate->diffInDays($endDate) + 1;
            $weeksInPeriod = ceil($daysInPeriod / 7);
            $volumeHoraireHebdo = $semiPermanent->volume_horaire_hebdomadaire ?? 0;
            $expectedHours = $volumeHoraireHebdo * $weeksInPeriod;

            // Taux de réalisation
            $realizationRate = $expectedHours > 0 ? ($totalHours / $expectedHours) * 100 : 0;

            // Statistiques sur les UE (si l'enseignant en a)
            $ueStat = UniteEnseignement::where('enseignant_id', $semiPermanent->id)
                ->where('statut', 'activee')
                ->selectRaw('COUNT(*) as total_ue, SUM(heures_effectuees) as total_heures_ue')
                ->first();

            $semiPermanent->total_days = $totalDays;
            $semiPermanent->total_hours = $totalHours;
            $semiPermanent->total_late = $totalLate;
            $semiPermanent->expected_hours = $expectedHours;
            $semiPermanent->realization_rate = $realizationRate;
            $semiPermanent->total_ue = $ueStat->total_ue ?? 0;
            $semiPermanent->total_heures_ue = $ueStat->total_heures_ue ?? 0;

            return $semiPermanent;
        });

        $campuses = Campus::orderBy('name')->get();

        return view('admin.semi-permanents.report', compact('semiPermanents', 'campuses', 'startDate', 'endDate'));
    }

    /**
     * Export semi-permanents report.
     */
    public function exportReport(Request $request)
    {
        // TODO: Implémenter l'export en PDF ou Excel
        return redirect()->route('admin.semi-permanents.report')
            ->with('info', 'Fonctionnalité d\'export en cours de développement.');
    }

    /**
     * Display weekly report for a semi-permanent.
     */
    public function weeklyReport(Request $request, string $id)
    {
        $semiPermanent = User::with(['campuses'])->findOrFail($id);

        // Vérifier que c'est bien un semi-permanent
        if ($semiPermanent->employee_type !== 'semi_permanent') {
            return redirect()->route('admin.semi-permanents.index')
                ->with('error', 'Cet employé n\'est pas un semi-permanent.');
        }

        // Déterminer la semaine à afficher
        if ($request->filled('week')) {
            // Format: YYYY-Www (ex: 2025-W48)
            $weekString = $request->week;
            $year = (int) substr($weekString, 0, 4);
            $week = (int) substr($weekString, 6);
            $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        } else {
            // Semaine en cours par défaut
            $startOfWeek = Carbon::now()->startOfWeek();
        }

        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        // Récupérer toutes les présences de la semaine
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('timestamp', [$startOfWeek, $endOfWeek])
            ->with('campus')
            ->orderBy('timestamp', 'asc')
            ->get();

        // Récupérer les UE actives de l'enseignant
        $unitesEnseignement = UniteEnseignement::where('enseignant_id', $id)
            ->where('statut', 'activee')
            ->get();

        // Récupérer les incidents de présence (pointages UE) de la semaine
        $presenceIncidents = \App\Models\PresenceIncident::where('user_id', $id)
            ->whereBetween('incident_date', [$startOfWeek, $endOfWeek])
            ->with('uniteEnseignement')
            ->orderBy('incident_date', 'asc')
            ->orderBy('check_in_time', 'asc')
            ->get();

        // Organiser les données par jour de la semaine
        $weekDays = [];
        $joursFr = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dateString = $currentDay->format('Y-m-d');

            // Présences générales du jour
            $dayAttendances = $attendances->filter(function ($attendance) use ($dateString) {
                return $attendance->timestamp->format('Y-m-d') === $dateString;
            });

            // Calculer les heures travaillées du jour
            $checkIn = $dayAttendances->where('type', 'check-in')->first();
            $checkOut = $dayAttendances->where('type', 'check-out')->first();

            $hoursWorked = 0;
            if ($checkIn && $checkOut) {
                $hoursWorked = $checkIn->timestamp->diffInMinutes($checkOut->timestamp) / 60;
            }

            // Présences UE du jour
            $dayUePresences = $presenceIncidents->filter(function ($incident) use ($dateString) {
                return Carbon::parse($incident->incident_date)->format('Y-m-d') === $dateString;
            });

            $weekDays[] = [
                'date' => $currentDay,
                'day_name' => $joursFr[$i],
                'is_work_day' => in_array($joursFr[$i], $semiPermanent->jours_travail ?? []),
                'hours_worked' => $hoursWorked,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'ue_presences' => $dayUePresences,
            ];
        }

        // Calculer les totaux de la semaine
        $totalHoursWeek = collect($weekDays)->sum('hours_worked');
        $daysWorked = collect($weekDays)->where('hours_worked', '>', 0)->count();
        $totalUeSessions = $presenceIncidents->count();

        return view('admin.semi-permanents.weekly-report', compact(
            'semiPermanent',
            'weekDays',
            'startOfWeek',
            'endOfWeek',
            'totalHoursWeek',
            'daysWorked',
            'totalUeSessions',
            'unitesEnseignement'
        ));
    }
}
