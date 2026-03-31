<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvanceRequest;
use App\Models\Loan;
use Illuminate\Http\Request;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryAdvanceRequest::with(['user', 'reviewer'])->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $requests = $query->paginate(20);
        $pendingCount = SalaryAdvanceRequest::pending()->count();

        return view('admin.salary-advances.index', compact('requests', 'pendingCount'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
            'monthly_amount' => 'required|numeric|min:1000',
        ]);

        $advance = SalaryAdvanceRequest::findOrFail($id);

        if ($advance->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Cette demande a déjà été traitée.'], 400);
        }

        $advance->update([
            'status' => 'approved',
            'admin_note' => $request->admin_note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Créer automatiquement un prêt (Loan) pour le remboursement
        Loan::create([
            'user_id' => $advance->user_id,
            'total_amount' => $advance->amount,
            'monthly_amount' => $request->monthly_amount,
            'amount_paid' => 0,
            'start_date' => now()->startOfMonth()->addMonth(),
            'reason' => 'Avance sur salaire - ' . $advance->reason,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Avance de {$advance->amount} FCFA approuvée. Un prêt a été créé pour le remboursement.",
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        $advance = SalaryAdvanceRequest::findOrFail($id);

        if ($advance->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Cette demande a déjà été traitée.'], 400);
        }

        $advance->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande rejetée.',
        ]);
    }
}
