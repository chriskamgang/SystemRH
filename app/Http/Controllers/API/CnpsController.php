<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CnpsRecord;
use App\Models\CnpsContribution;
use Illuminate\Http\Request;

class CnpsController extends Controller
{
    /**
     * Mon dossier CNPS
     */
    public function myRecord(Request $request)
    {
        $user = $request->user();
        $record = CnpsRecord::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'record' => $record ? [
                'cnps_number' => $record->cnps_number,
                'registration_date' => $record->registration_date?->format('d/m/Y'),
                'status' => $record->status,
            ] : null,
        ]);
    }

    /**
     * Mes cotisations
     */
    public function myContributions(Request $request)
    {
        $user = $request->user();
        $year = $request->query('year', now()->year);

        $contributions = CnpsContribution::where('user_id', $user->id)
            ->where('year', $year)
            ->orderBy('month')
            ->get()
            ->map(function ($c) {
                $months = ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec'];
                return [
                    'month' => $c->month,
                    'month_label' => $months[$c->month - 1] ?? '',
                    'year' => $c->year,
                    'gross_salary' => $c->gross_salary,
                    'employee_contribution' => $c->employee_contribution,
                    'employer_contribution' => $c->employer_contribution,
                    'total_contribution' => $c->total_contribution,
                    'status' => $c->status,
                ];
            });

        // Totaux annuels
        $totals = [
            'employee' => $contributions->sum('employee_contribution'),
            'employer' => $contributions->sum('employer_contribution'),
            'total' => $contributions->sum('total_contribution'),
        ];

        return response()->json([
            'success' => true,
            'year' => $year,
            'contributions' => $contributions,
            'totals' => $totals,
        ]);
    }
}
