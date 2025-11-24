<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDeduction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
     * Store a newly created deduction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        $deduction = ManualDeduction::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'month' => $request->month,
            'year' => $request->year,
            'status' => 'active',
            'applied_by' => auth()->id(),
        ]);

        // TODO: Envoyer notification push à l'employé

        return response()->json([
            'success' => true,
            'message' => 'Déduction appliquée avec succès.',
            'deduction' => $deduction->load(['user', 'appliedBy']),
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
     * Cancel a deduction.
     */
    public function cancel($id)
    {
        $deduction = ManualDeduction::findOrFail($id);

        if ($deduction->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cette déduction est déjà annulée.',
            ], 400);
        }

        $deduction->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Déduction annulée avec succès.',
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
