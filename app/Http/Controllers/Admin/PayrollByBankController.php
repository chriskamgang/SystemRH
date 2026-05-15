<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\PayrollRecord;
use App\Helpers\PayrollCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollByBankController extends Controller
{
    /**
     * Display salaries grouped by bank.
     */
    public function index(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : Carbon::now()->month;
        $year = $request->filled('year') ? (int) $request->year : Carbon::now()->year;

        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        $bankGroups = $this->getBankGroups($request, $year, $month, $workingDays);

        $campuses = Campus::orderBy('name')->get();

        // Stats globales
        $totalEmployees = $bankGroups->sum(fn($g) => $g['employees']->count());
        $totalNetSalary = $bankGroups->sum('total_net');
        $totalGrossSalary = $bankGroups->sum('total_gross');
        $totalBanks = $bankGroups->count();
        $totalPaid = $bankGroups->sum(fn($g) => $g['paid_count']);

        return view('admin.payroll.by-bank', compact(
            'bankGroups',
            'campuses',
            'year',
            'month',
            'workingDays',
            'totalEmployees',
            'totalNetSalary',
            'totalGrossSalary',
            'totalBanks',
            'totalPaid'
        ));
    }

    /**
     * Mark a bank group as paid — creates PayrollRecords with status 'paid'.
     */
    public function markBankAsPaid(Request $request)
    {
        $request->validate([
            'banque' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $banque = $request->banque;
        $year = $request->year;
        $month = $request->month;
        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        // Récupérer les employés de cette banque
        $query = User::where('role_id', '!=', 1)
            ->where('employee_type', '!=', 'enseignant_vacataire')
            ->whereNotNull('monthly_salary')
            ->where('monthly_salary', '>', 0);

        if ($banque === '__none__') {
            $query->where(function ($q) {
                $q->whereNull('banque')->orWhere('banque', '');
            });
        } else {
            $query->where('banque', $banque);
        }

        $employees = $query->get();
        $count = 0;

        foreach ($employees as $employee) {
            // Ne pas re-valider si déjà payé
            $existing = PayrollRecord::where('user_id', $employee->id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'paid')
                ->first();

            if ($existing) {
                continue;
            }

            $payroll = PayrollCalculator::calculatePayroll($employee, $year, $month);

            PayrollRecord::updateOrCreate(
                [
                    'user_id' => $employee->id,
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
                    'late_minutes_justified' => $payroll['late_minutes_justified'] ?? 0,
                    'late_penalty_amount' => $payroll['late_penalty_amount'],
                    'absence_deduction' => $payroll['absence_deduction'],
                    'gross_salary' => $payroll['gross_salary'],
                    'total_deductions' => $payroll['total_deductions'],
                    'net_salary' => $payroll['net_salary'],
                    'status' => 'paid',
                    'approved_at' => now(),
                    'paid_at' => now(),
                    'approved_by' => auth()->id(),
                ]
            );

            $count++;
        }

        $bankLabel = $banque === '__none__' ? 'Sans banque' : $banque;

        return response()->json([
            'success' => true,
            'message' => "Virement validé pour {$bankLabel} : {$count} fiche(s) de paie enregistrée(s).",
            'count' => $count,
        ]);
    }

    /**
     * Cancel paid status for a bank group.
     */
    public function cancelBankPayment(Request $request)
    {
        $request->validate([
            'banque' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $banque = $request->banque;
        $year = $request->year;
        $month = $request->month;

        // Récupérer les IDs des employés de cette banque
        $query = User::where('role_id', '!=', 1)
            ->where('employee_type', '!=', 'enseignant_vacataire')
            ->whereNotNull('monthly_salary')
            ->where('monthly_salary', '>', 0);

        if ($banque === '__none__') {
            $query->where(function ($q) {
                $q->whereNull('banque')->orWhere('banque', '');
            });
        } else {
            $query->where('banque', $banque);
        }

        $userIds = $query->pluck('id');

        $count = PayrollRecord::whereIn('user_id', $userIds)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'paid')
            ->update([
                'status' => 'approved',
                'paid_at' => null,
            ]);

        $bankLabel = $banque === '__none__' ? 'Sans banque' : $banque;

        return response()->json([
            'success' => true,
            'message' => "Virement annulé pour {$bankLabel} : {$count} fiche(s) remise(s) en attente.",
            'count' => $count,
        ]);
    }

    /**
     * Export PDF grouped by bank.
     */
    public function exportPdf(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : Carbon::now()->month;
        $year = $request->filled('year') ? (int) $request->year : Carbon::now()->year;
        $selectedBank = $request->input('banque');

        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        $bankGroups = $this->getBankGroups($request, $year, $month, $workingDays);

        if ($selectedBank) {
            $bankGroups = $bankGroups->filter(fn($g) => $g['bank_name'] === $selectedBank)->values();
        }

        $totalEmployees = $bankGroups->sum(fn($g) => $g['employees']->count());
        $totalNetSalary = $bankGroups->sum('total_net');
        $totalGrossSalary = $bankGroups->sum('total_gross');
        $totalBanks = $bankGroups->count();

        $pdf = Pdf::loadView('admin.payroll.pdf.by-bank', compact(
            'bankGroups',
            'year',
            'month',
            'workingDays',
            'totalEmployees',
            'totalNetSalary',
            'totalGrossSalary',
            'totalBanks',
            'selectedBank'
        ));

        $pdf->setPaper('A4', 'landscape');

        $monthName = Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM');
        $suffix = $selectedBank ? '-' . \Illuminate\Support\Str::slug($selectedBank) : '';
        $filename = "salaires-par-banque-{$monthName}-{$year}{$suffix}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Build employee payroll data grouped by bank, with payment status.
     */
    private function getBankGroups(Request $request, int $year, int $month, float $workingDays)
    {
        $query = User::where('role_id', '!=', 1)
            ->where('employee_type', '!=', 'enseignant_vacataire')
            ->whereNotNull('monthly_salary')
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

        if ($request->filled('banque')) {
            $banque = $request->banque;
            if ($banque === '__none__') {
                $query->where(function ($q) {
                    $q->whereNull('banque')->orWhere('banque', '');
                });
            } else {
                $query->where('banque', $banque);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('numero_compte', 'like', "%{$search}%");
            });
        }

        $employees = $query->get();

        // Pré-charger les PayrollRecords payés pour ce mois
        $paidRecords = PayrollRecord::where('year', $year)
            ->where('month', $month)
            ->where('status', 'paid')
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $employees = $employees->map(function ($employee) use ($year, $month, $paidRecords) {
            $payroll = PayrollCalculator::calculatePayroll($employee, $year, $month);
            foreach ($payroll as $key => $value) {
                $employee->$key = $value;
            }

            // Statut de paiement
            $paidRecord = $paidRecords->get($employee->id);
            $employee->is_paid = $paidRecord !== null;
            $employee->paid_at = $paidRecord?->paid_at;

            return $employee;
        });

        // Group by bank
        $grouped = $employees->groupBy(function ($emp) {
            return $emp->banque ?: '__none__';
        })->sortKeys();

        return $grouped->map(function ($group, $bankKey) {
            $paidCount = $group->where('is_paid', true)->count();
            $allPaid = $paidCount === $group->count();

            return [
                'bank_key' => $bankKey,
                'bank_name' => $bankKey === '__none__' ? 'Non assignee' : $bankKey,
                'is_unassigned' => $bankKey === '__none__',
                'employees' => $group->sortByDesc('net_salary')->values(),
                'total_gross' => $group->sum('gross_salary'),
                'total_deductions' => $group->sum('total_deductions'),
                'total_net' => $group->sum('net_salary'),
                'count' => $group->count(),
                'paid_count' => $paidCount,
                'all_paid' => $allPaid,
            ];
        })->values();
    }
}
