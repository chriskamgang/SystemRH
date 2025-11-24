<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request)
    {
        $query = Loan::with(['user', 'createdBy', 'completedBy'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->paginate(20);
        $employees = User::where('role_id', '!=', 1)->orderBy('first_name')->get();

        return view('admin.loans.index', compact('loans', 'employees'));
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'total_amount' => 'required|numeric|min:1',
            'monthly_amount' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Vérifier que le montant mensuel n'est pas supérieur au montant total
        if ($request->monthly_amount > $request->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant mensuel ne peut pas être supérieur au montant total.',
            ], 400);
        }

        $loan = Loan::create([
            'user_id' => $request->user_id,
            'total_amount' => $request->total_amount,
            'monthly_amount' => $request->monthly_amount,
            'start_date' => $request->start_date,
            'reason' => $request->reason,
            'status' => 'active',
            'created_by' => auth()->id(),
            'amount_paid' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prêt créé avec succès.',
            'loan' => $loan->load(['user', 'createdBy']),
        ]);
    }

    /**
     * Update the specified loan.
     */
    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier un prêt non actif.',
            ], 400);
        }

        $request->validate([
            'monthly_amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Vérifier que le montant mensuel n'est pas supérieur au montant restant
        if ($request->monthly_amount > $loan->remaining_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant mensuel ne peut pas être supérieur au montant restant.',
            ], 400);
        }

        $loan->update([
            'monthly_amount' => $request->monthly_amount,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prêt modifié avec succès.',
            'loan' => $loan->load(['user', 'createdBy']),
        ]);
    }

    /**
     * Mark a loan as completed (remboursement anticipé).
     */
    public function markAsCompleted($id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Ce prêt est déjà marqué comme terminé.',
            ], 400);
        }

        $loan->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
            'amount_paid' => $loan->total_amount, // Marquer comme entièrement payé
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prêt marqué comme terminé avec succès.',
        ]);
    }

    /**
     * Cancel a loan.
     */
    public function cancel($id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Ce prêt est déjà annulé.',
            ], 400);
        }

        $loan->update([
            'status' => 'cancelled',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prêt annulé avec succès.',
        ]);
    }

    /**
     * Delete a loan (soft delete).
     */
    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prêt supprimé avec succès.',
        ]);
    }

    /**
     * Record a manual payment for a loan.
     */
    public function recordPayment(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'enregistrer un paiement pour un prêt non actif.',
            ], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $newAmountPaid = $loan->amount_paid + $request->amount;

        // Vérifier que le paiement ne dépasse pas le montant total
        if ($newAmountPaid > $loan->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant du paiement dépasse le montant restant.',
            ], 400);
        }

        $loan->update([
            'amount_paid' => $newAmountPaid,
        ]);

        // Si entièrement payé, marquer comme terminé
        if ($newAmountPaid >= $loan->total_amount) {
            $loan->update([
                'status' => 'completed',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès.',
            'loan' => $loan->fresh(['user', 'createdBy', 'completedBy']),
        ]);
    }
}
