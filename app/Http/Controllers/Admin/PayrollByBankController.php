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

        // Check which banks have DOCX templates uploaded
        $bankHeaders = [];
        foreach ($bankGroups as $group) {
            $bankHeaders[$group['bank_name']] = $this->hasBankTemplate($group['bank_name']);
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
     * Upload a DOCX template for a specific bank.
     */
    public function uploadBankHeader(Request $request)
    {
        \Log::info('Upload bank template', [
            'bank_name' => $request->bank_name,
            'has_file' => $request->hasFile('header_image'),
            'file_name' => $request->file('header_image')?->getClientOriginalName(),
        ]);

        $request->validate([
            'bank_name' => 'required|string|max:100',
            'header_image' => 'required|file|max:5120',
        ]);

        $bankSlug = \Illuminate\Support\Str::slug($request->bank_name);
        $file = $request->file('header_image');

        // Accept DOCX only, store as .docx
        $file->storeAs('public/bank-templates', "{$bankSlug}.docx");

        \Log::info('Template saved', ['path' => "public/bank-templates/{$bankSlug}.docx"]);

        return response()->json([
            'success' => true,
            'message' => "Template DOCX uploade pour {$request->bank_name}.",
        ]);
    }

    /**
     * Delete a bank's DOCX template.
     */
    public function deleteBankHeader(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string',
        ]);

        $bankSlug = \Illuminate\Support\Str::slug($request->bank_name);
        $path = "public/bank-templates/{$bankSlug}.docx";

        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => "Template supprime pour {$request->bank_name}.",
        ]);
    }

    /**
     * Check if a DOCX template exists for a bank.
     */
    private function hasBankTemplate(string $bankName): bool
    {
        $bankSlug = \Illuminate\Support\Str::slug($bankName);
        return Storage::exists("public/bank-templates/{$bankSlug}.docx");
    }

    /**
     * Get the DOCX template path for a bank.
     */
    private function getBankTemplatePath(string $bankName): ?string
    {
        $bankSlug = \Illuminate\Support\Str::slug($bankName);
        $path = "public/bank-templates/{$bankSlug}.docx";

        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        return null;
    }

    /**
     * Export using the bank's DOCX template with employee data inserted.
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

        // If a single bank with a DOCX template, use PhpWord
        if ($bankGroups->count() === 1) {
            $group = $bankGroups->first();
            $templatePath = $this->getBankTemplatePath($group['bank_name']);

            if ($templatePath && file_exists($templatePath)) {
                return $this->exportFromDocxTemplate($templatePath, $group, $year, $month, $workingDays);
            }
        }

        // Fallback: DomPDF for banks without template or multi-bank export
        return $this->exportFallbackPdf($bankGroups, $year, $month, $workingDays, $selectedBank);
    }

    /**
     * Generate DOCX from bank template by injecting table XML directly into the ZIP.
     * This preserves all original formatting, images, headers, backgrounds.
     */
    private function exportFromDocxTemplate(string $templatePath, array $group, int $year, int $month, float $workingDays)
    {
        $monthName = Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY');
        $bankSlug = \Illuminate\Support\Str::slug($group['bank_name']);

        // Copy template to temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'payroll_') . '.docx';
        copy($templatePath, $tmpFile);

        // Build the table XML
        $tableXml = $this->buildSalaryTableXml($group, $monthName, $workingDays);

        // Open the DOCX (ZIP) and inject table into document.xml
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            return $this->exportFallbackPdf(collect([$group]), $year, $month, $workingDays, $group['bank_name']);
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if (!$documentXml) {
            $zip->close();
            return $this->exportFallbackPdf(collect([$group]), $year, $month, $workingDays, $group['bank_name']);
        }

        // Insert table XML before the last </w:body> closing tag
        // Find the sectPr (section properties) and insert before it
        $insertPos = strrpos($documentXml, '<w:sectPr');
        if ($insertPos === false) {
            // Fallback: insert before </w:body>
            $insertPos = strrpos($documentXml, '</w:body>');
        }

        if ($insertPos !== false) {
            $documentXml = substr($documentXml, 0, $insertPos) . $tableXml . substr($documentXml, $insertPos);
        }

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $filename = "salaires-{$bankSlug}-" . Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM') . "-{$year}.docx";

        return response()->download($tmpFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build Word XML for the salary table.
     */
    private function buildSalaryTableXml(array $group, string $monthName, float $workingDays): string
    {
        $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        // Period info paragraph
        $xml = '<w:p xmlns:w="' . $w . '"><w:pPr><w:spacing w:after="100"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr>';
        $xml .= '<w:t xml:space="preserve">Periode : ' . htmlspecialchars($monthName) . ' | Jours ouvrables : ' . number_format($workingDays, 1) . ' | Employes : ' . $group['count'] . ' | Edition : ' . date('d/m/Y H:i') . '</w:t>';
        $xml .= '</w:r></w:p>';

        // Table
        $xml .= '<w:tbl xmlns:w="' . $w . '">';

        // Table properties
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="5000" w:type="pct"/>';
        $xml .= '<w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:color="999999"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '<w:tblCellMar><w:top w:w="30" w:type="dxa"/><w:left w:w="60" w:type="dxa"/><w:bottom w:w="30" w:type="dxa"/><w:right w:w="60" w:type="dxa"/></w:tblCellMar>';
        $xml .= '</w:tblPr>';

        // Column widths (total ~10000 dxa for A4)
        $cols = [400, 1100, 2400, 1400, 800, 700, 700, 900, 700, 900];
        $xml .= '<w:tblGrid>';
        foreach ($cols as $c) {
            $xml .= '<w:gridCol w:w="' . $c . '"/>';
        }
        $xml .= '</w:tblGrid>';

        // Header row
        $headers = ['#', 'Matricule', 'Nom & Prenom', 'N Compte', 'Jrs Trav.', 'Heures', 'Retards', 'Sal. Brut', 'Ded.', 'Sal. Net'];
        $xml .= '<w:tr>';
        foreach ($headers as $i => $h) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $cols[$i] . '" w:type="dxa"/><w:shd w:val="clear" w:fill="1e40af"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>';
            $xml .= '<w:t>' . htmlspecialchars($h) . '</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows
        foreach ($group['employees'] as $empIndex => $employee) {
            $fill = ($empIndex % 2 === 1) ? 'f3f4f6' : 'FFFFFF';
            $cells = [
                $empIndex + 1,
                $employee->employee_id,
                $employee->full_name,
                $employee->numero_compte ?: '-',
                number_format($employee->days_worked, 1) . '/' . number_format($employee->working_days ?? 0, 1),
                number_format($employee->total_hours_worked ?? 0, 1) . 'h',
                ($employee->total_late_minutes ?? 0) . 'min',
                number_format($employee->gross_salary, 0, ',', ' '),
                number_format($employee->total_deductions, 0, ',', ' '),
                number_format($employee->net_salary, 0, ',', ' '),
            ];

            $xml .= '<w:tr>';
            foreach ($cells as $ci => $val) {
                $color = '333333';
                $bold = '';
                if ($ci === 8) $color = 'dc2626'; // Deductions in red
                if ($ci === 9) { $color = '059669'; $bold = '<w:b/>'; } // Net in green bold
                if ($ci === 2) $bold = '<w:b/>'; // Name bold

                $align = ($ci >= 4) ? 'center' : 'left';
                if ($ci >= 7) $align = 'right';

                $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $cols[$ci] . '" w:type="dxa"/><w:shd w:val="clear" w:fill="' . $fill . '"/></w:tcPr>';
                $xml .= '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr>';
                $xml .= '<w:r><w:rPr>' . $bold . '<w:color w:val="' . $color . '"/><w:sz w:val="16"/></w:rPr>';
                $xml .= '<w:t xml:space="preserve">' . htmlspecialchars((string) $val) . '</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        // Total row
        $xml .= '<w:tr>';
        // Merged cell for "TOTAL" label (7 columns)
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="7500" w:type="dxa"/><w:gridSpan w:val="7"/><w:shd w:val="clear" w:fill="1e40af"/></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>';
        $xml .= '<w:t>TOTAL</w:t></w:r></w:p></w:tc>';
        // Gross
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/><w:shd w:val="clear" w:fill="1e40af"/></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>';
        $xml .= '<w:t>' . number_format($group['total_gross'], 0, ',', ' ') . '</w:t></w:r></w:p></w:tc>';
        // Deductions
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:shd w:val="clear" w:fill="1e40af"/></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>';
        $xml .= '<w:t>' . number_format($group['total_deductions'], 0, ',', ' ') . '</w:t></w:r></w:p></w:tc>';
        // Net
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/><w:shd w:val="clear" w:fill="1e40af"/></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>';
        $xml .= '<w:t>' . number_format($group['total_net'], 0, ',', ' ') . '</w:t></w:r></w:p></w:tc>';
        $xml .= '</w:tr>';

        $xml .= '</w:tbl>';

        // Signatures
        $xml .= '<w:p xmlns:w="' . $w . '"><w:pPr><w:spacing w:before="400"/></w:pPr></w:p>';
        $xml .= '<w:tbl xmlns:w="' . $w . '"><w:tblPr><w:tblW w:w="5000" w:type="pct"/></w:tblPr>';
        $xml .= '<w:tblGrid><w:gridCol w:w="5000"/><w:gridCol w:w="5000"/></w:tblGrid>';
        $xml .= '<w:tr>';
        // Left signature
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="5000" w:type="dxa"/><w:tcBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/></w:tcBorders></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="16"/></w:rPr><w:t>Prepare par :</w:t></w:r></w:p>';
        $xml .= '<w:p/><w:p/>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t>____________________</w:t></w:r></w:p>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t>Signature &amp; Cachet</w:t></w:r></w:p>';
        $xml .= '</w:tc>';
        // Right signature
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="5000" w:type="dxa"/><w:tcBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/></w:tcBorders></w:tcPr>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="16"/></w:rPr><w:t>Verifie et approuve par :</w:t></w:r></w:p>';
        $xml .= '<w:p/><w:p/>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t>____________________</w:t></w:r></w:p>';
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t>Signature &amp; Cachet</w:t></w:r></w:p>';
        $xml .= '</w:tc>';
        $xml .= '</w:tr></w:tbl>';

        return $xml;
    }

    /**
     * Fallback PDF export for banks without DOCX template.
     */
    private function exportFallbackPdf($bankGroups, int $year, int $month, float $workingDays, ?string $selectedBank)
    {
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

        $pdf->setPaper('A4', 'portrait');

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
