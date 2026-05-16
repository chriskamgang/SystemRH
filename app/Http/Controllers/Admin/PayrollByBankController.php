<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\PayrollRecord;
use App\Helpers\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        // Check which banks have headers uploaded
        $bankHeaders = [];
        foreach ($bankGroups as $group) {
            $bankSlug = \Illuminate\Support\Str::slug($group['bank_name']);
            $bankHeaders[$group['bank_name']] = Storage::exists("public/bank-headers/{$bankSlug}.jpg")
                || Storage::exists("public/bank-headers/{$bankSlug}.png");
        }

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
            'totalPaid',
            'bankHeaders'
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
            $query->whereRaw('UPPER(TRIM(banque)) = ?', [mb_strtoupper(trim($banque))]);
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
            $query->whereRaw('UPPER(TRIM(banque)) = ?', [mb_strtoupper(trim($banque))]);
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
     * Update net salary for a specific employee (before or after marking as paid).
     */
    public function updateEmployeeSalary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'net_salary' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $year = $request->year;
        $month = $request->month;
        $workingDays = PayrollCalculator::calculateWorkingDays($year, $month);

        // Calculer la paie de base
        $payroll = PayrollCalculator::calculatePayroll($user, $year, $month);

        $newNet = (float) $request->net_salary;
        $originalNet = $payroll['net_salary'] ?? 0;
        $adjustment = $newNet - $originalNet;

        // Créer ou mettre à jour le PayrollRecord avec le montant modifié
        $record = PayrollRecord::updateOrCreate(
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
                'late_minutes_justified' => $payroll['late_minutes_justified'] ?? 0,
                'late_penalty_amount' => $payroll['late_penalty_amount'],
                'absence_deduction' => $payroll['absence_deduction'],
                'gross_salary' => $payroll['gross_salary'],
                'total_deductions' => max(0, ($payroll['gross_salary'] ?? 0) - $newNet),
                'net_salary' => $newNet,
                'status' => 'paid',
                'approved_at' => now(),
                'paid_at' => now(),
                'approved_by' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Salaire de {$user->full_name} mis à jour : " . number_format($newNet, 0, ',', ' ') . " FCFA",
        ]);
    }

    /**
     * Upload a letterhead image for a specific bank.
     */
    public function uploadBankHeader(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:100',
            'header_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $bankSlug = \Illuminate\Support\Str::slug($request->bank_name);
        $filename = "bank-headers/{$bankSlug}.jpg";

        // Convert to JPG if needed and store
        $image = $request->file('header_image');
        $image->storeAs('public/bank-headers', "{$bankSlug}.jpg");

        return response()->json([
            'success' => true,
            'message' => "En-tete uploade pour {$request->bank_name}.",
        ]);
    }

    /**
     * Delete a bank's letterhead image.
     */
    public function deleteBankHeader(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string',
        ]);

        $bankSlug = \Illuminate\Support\Str::slug($request->bank_name);
        $path = "public/bank-headers/{$bankSlug}.jpg";

        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => "En-tete supprime pour {$request->bank_name}.",
        ]);
    }

    /**
     * Get the header image path for a bank (or null).
     */
    private function getBankHeaderPath(string $bankName): ?string
    {
        $bankSlug = \Illuminate\Support\Str::slug($bankName);
        $path = "public/bank-headers/{$bankSlug}.jpg";

        if (Storage::exists($path)) {
            return storage_path("app/{$path}");
        }

        // Try PNG
        $pathPng = "public/bank-headers/{$bankSlug}.png";
        if (Storage::exists($pathPng)) {
            return storage_path("app/{$pathPng}");
        }

        return null;
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

        // Resolve header image paths per bank
        $bankHeaderPaths = [];
        foreach ($bankGroups as $group) {
            $headerPath = $this->getBankHeaderPath($group['bank_name']);
            if ($headerPath) {
                $bankHeaderPaths[$group['bank_name']] = $headerPath;
            }
        }

        $pdf = Pdf::loadView('admin.payroll.pdf.by-bank', compact(
            'bankGroups',
            'year',
            'month',
            'workingDays',
            'totalEmployees',
            'totalNetSalary',
            'totalGrossSalary',
            'totalBanks',
            'selectedBank',
            'bankHeaderPaths'
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
                $query->whereRaw('UPPER(TRIM(banque)) = ?', [mb_strtoupper(trim($banque))]);
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

        // Group by bank (normaliser la casse pour regrouper ex: "Caisse centrale" = "CAISSE CENTRALE")
        $grouped = $employees->groupBy(function ($emp) {
            return $emp->banque ? mb_strtoupper(trim($emp->banque)) : '__none__';
        })->sortKeys();

        return $grouped->map(function ($group, $bankKey) {
            $paidCount = $group->where('is_paid', true)->count();
            $allPaid = $paidCount === $group->count();
            // Utiliser le nom original du premier employé mais en majuscules
            $displayName = $bankKey === '__none__' ? 'Non assignee' : $bankKey;

            return [
                'bank_key' => $bankKey,
                'bank_name' => $displayName,
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
