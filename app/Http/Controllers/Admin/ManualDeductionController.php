<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDeduction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ManualDeductionController extends Controller
{
    /**
     * Display a listing of manual deductions.
     */
    public function index(Request $request)
    {
        $query = ManualDeduction::with(['user', 'appliedBy', 'cancelledBy'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->where('month', $request->month)->where('year', $request->year);
        }

        $deductions = $query->paginate(20);
        $employees = User::where('role_id', '!=', 1)->orderBy('first_name')->get();

        return view('admin.manual-deductions.index', compact('deductions', 'employees'));
    }

    /**
     * Export manual deductions as PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = ManualDeduction::with(['user', 'appliedBy', 'cancelledBy'])
            ->orderBy('created_at', 'desc');

        $filterParts = [];

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
            $user = User::find($request->user_id);
            if ($user) $filterParts[] = 'Employé: ' . $user->full_name;
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filterParts[] = 'Statut: ' . ucfirst($request->status);
        }
        if ($request->filled('month') && $request->filled('year')) {
            $query->where('month', $request->month)->where('year', $request->year);
            $filterParts[] = 'Période: ' . $request->month . '/' . $request->year;
        }

        $deductions = $query->get();
        $filters = !empty($filterParts) ? implode(' | ', $filterParts) : null;

        $pdf = Pdf::loadView('admin.manual-deductions.pdf.report', compact('deductions', 'filters'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('rapport-deductions-manuelles.pdf');
    }

    /**
     * Store a newly created deduction (avec support des tranches).
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'num_installments' => 'nullable|integer|min:1|max:24',
        ]);

        $numInstallments = $request->num_installments ?? 1;
        $totalAmount = $request->amount;
        $installmentAmount = round($totalAmount / $numInstallments, 0);

        // Générer un group_id unique pour lier les tranches
        $groupId = $numInstallments > 1 ? time() . rand(100, 999) : null;

        $startMonth = $request->month;
        $startYear = $request->year;
        $deductions = [];

        for ($i = 0; $i < $numInstallments; $i++) {
            $month = $startMonth + $i;
            $year = $startYear;

            // Gérer le passage d'année
            while ($month > 12) {
                $month -= 12;
                $year++;
            }

            // La dernière tranche prend le reste (pour éviter les erreurs d'arrondi)
            $amount = ($i === $numInstallments - 1)
                ? $totalAmount - ($installmentAmount * ($numInstallments - 1))
                : $installmentAmount;

            $deductions[] = ManualDeduction::create([
                'user_id' => $request->user_id,
                'amount' => $amount,
                'total_amount' => $numInstallments > 1 ? $totalAmount : null,
                'num_installments' => $numInstallments,
                'installment_number' => $i + 1,
                'group_id' => $groupId,
                'reason' => $request->reason,
                'month' => $month,
                'year' => $year,
                'status' => 'active',
                'applied_by' => auth()->id(),
            ]);
        }

        $message = $numInstallments > 1
            ? "Déduction de " . number_format($totalAmount, 0, ',', ' ') . " FCFA répartie en {$numInstallments} tranches de " . number_format($installmentAmount, 0, ',', ' ') . " FCFA/mois."
            : 'Déduction appliquée avec succès.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'deduction' => $deductions[0]->load(['user', 'appliedBy']),
            'total_installments' => count($deductions),
        ]);
    }

    /**
     * Update the specified deduction.
     */
    public function update(Request $request, $id)
    {
        $deduction = ManualDeduction::findOrFail($id);

        if ($deduction->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une déduction annulée.',
            ], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        $deduction->update([
            'amount' => $request->amount,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Déduction modifiée avec succès.',
            'deduction' => $deduction->load(['user', 'appliedBy']),
        ]);
    }

    /**
     * Cancel a deduction (et toutes ses tranches si c'est un groupe).
     */
    public function cancel($id, Request $request)
    {
        $deduction = ManualDeduction::findOrFail($id);

        if ($deduction->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cette déduction est déjà annulée.',
            ], 400);
        }

        $cancelAll = $request->input('cancel_all', false);
        $count = 1;

        if ($cancelAll && $deduction->group_id) {
            // Annuler toutes les tranches du groupe
            $count = ManualDeduction::where('group_id', $deduction->group_id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                ]);
        } else {
            $deduction->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);
        }

        $message = $count > 1
            ? "{$count} tranches annulées avec succès."
            : 'Déduction annulée avec succès.';

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Delete a deduction (soft delete).
     */
    public function destroy($id)
    {
        $deduction = ManualDeduction::findOrFail($id);
        $deduction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déduction supprimée avec succès.',
        ]);
    }

    /**
     * Get deductions details for a user (API endpoint for modal).
     */
    public function getDeductionsForUser($userId, Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        $deductions = ManualDeduction::with(['appliedBy'])
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'active')
            ->get();

        $formattedDeductions = $deductions->map(function ($deduction) {
            return [
                'id' => $deduction->id,
                'amount' => $deduction->amount,
                'reason' => $deduction->reason,
                'status' => $deduction->status,
                'applied_by' => $deduction->appliedBy->full_name,
                'created_at' => $deduction->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'deductions' => $formattedDeductions,
        ]);
    }
}
