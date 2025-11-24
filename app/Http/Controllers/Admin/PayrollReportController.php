<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\Role;
use App\Models\PayrollJustification;
use App\Models\PayrollRecord;
use App\Helpers\PayrollCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollReportController extends Controller
{
    /**
     * Display the payroll report for permanent and semi-permanent employees.
     */
    public function index(Request $request)
    {
        // Filtres de période (mois en cours par défaut)
        $month = $request->filled('month') ? (int) $request->month : Carbon::now()->month;
        $year = $request->filled('year') ? (int) $request->year : Carbon::now()->year;

        // Calculer les jours ouvrables du mois
        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        // Filtres additionnels
        $query = User::where('role_id', '!=', 1) // Exclure les admins
            ->where('employee_type', '!=', 'enseignant_vacataire') // Exclure les vacataires
            ->whereNotNull('monthly_salary') // Seulement ceux qui ont un salaire mensuel
            ->where('monthly_salary', '>', 0)
            ->with(['role', 'department', 'campuses']);

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->employee_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Récupérer tous les employés
        $employees = $query->get()->map(function ($employee) use ($year, $month, $workingDays) {
            // Calculer la paie avec le PayrollCalculator
            $payroll = PayrollCalculator::calculatePayroll($employee, $year, $month);

            // Ajouter les données à l'employé
            foreach ($payroll as $key => $value) {
                $employee->$key = $value;
            }

            return $employee;
        })->sortByDesc('net_salary');

        // Données pour les filtres
        $campuses = Campus::orderBy('name')->get();
        $roles = Role::where('id', '!=', 1)->orderBy('display_name')->get();

        // Statistiques globales
        $totalGrossSalary = $employees->sum('gross_salary');
        $totalDeductions = $employees->sum('total_deductions');
        $totalNetSalary = $employees->sum('net_salary');
        $totalEmployees = $employees->count();

        return view('admin.payroll.report', compact(
            'employees',
            'campuses',
            'roles',
            'year',
            'month',
            'workingDays',
            'totalGrossSalary',
            'totalDeductions',
            'totalNetSalary',
            'totalEmployees'
        ));
    }

    /**
     * Enregistrer une justification.
     */
    public function justify(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'days_justified' => 'required|numeric|min:0',
            'late_minutes_justified' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        PayrollJustification::create([
            'user_id' => $request->user_id,
            'created_by' => auth()->id(),
            'year' => $request->year,
            'month' => $request->month,
            'days_justified' => $request->days_justified,
            'late_minutes_justified' => $request->late_minutes_justified ?? 0,
            'reason' => $request->reason,
            'status' => 'approved',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Justification enregistrée avec succès.',
        ]);
    }

    /**
     * Appliquer la déduction et créer/mettre à jour le PayrollRecord.
     */
    public function applyDeduction(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $user = User::findOrFail($request->user_id);
        $year = $request->year;
        $month = $request->month;

        // Calculer la paie
        $payroll = PayrollCalculator::calculatePayroll($user, $year, $month);
        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        // Créer ou mettre à jour le PayrollRecord
        PayrollRecord::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'monthly_salary' => $payroll['monthly_salary'],
                'working_days' => $workingDays,
                'days_worked' => $payroll['days_worked'],
                'days_not_worked' => $payroll['days_not_worked'],
                'days_justified' => $payroll['days_justified'],
                'total_late_minutes' => $payroll['total_late_minutes'],
                'late_minutes_justified' => $payroll['late_minutes_justified'],
                'late_penalty_amount' => $payroll['late_penalty_amount'],
                'absence_deduction' => $payroll['absence_deduction'],
                'gross_salary' => $payroll['gross_salary'],
                'total_deductions' => $payroll['total_deductions'],
                'net_salary' => $payroll['net_salary'],
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Déduction appliquée avec succès.',
            'payroll' => $payroll,
        ]);
    }

    /**
     * Export the payroll report.
     */
    public function export(Request $request)
    {
        // TODO: Implémenter l'export en PDF ou Excel
        return redirect()->route('admin.payroll.report')
            ->with('info', 'Fonctionnalité d\'export en cours de développement.');
    }
}
