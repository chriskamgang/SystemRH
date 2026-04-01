<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvanceRequest;
use App\Models\Loan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PushNotificationService;
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

        // La mensualité ne peut pas dépasser le montant de l'avance
        if ($request->monthly_amount > $advance->amount) {
            return response()->json([
                'success' => false,
                'message' => 'La mensualité (' . number_format($request->monthly_amount, 0, ',', '.') . ' FCFA) ne peut pas dépasser le montant de l\'avance (' . number_format($advance->amount, 0, ',', '.') . ' FCFA).',
            ], 422);
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

        // Créditer le portefeuille de l'employé
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $advance->user_id],
            ['balance' => 0]
        );
        $wallet->credit(
            $advance->amount,
            'Avance sur salaire approuvée - ' . $advance->reason,
            'advance',
            'ADV-' . $advance->id
        );

        // Notification push à l'employé
        try {
            $employee = User::find($advance->user_id);
            $pushService = new PushNotificationService();
            $pushService->sendToUser(
                $employee,
                'Avance sur salaire approuvée',
                number_format($advance->amount, 0, ',', '.') . ' FCFA ont été crédités dans votre portefeuille.',
                [
                    'type' => 'salary_advance_approved',
                    'amount' => (string) $advance->amount,
                ],
                'salary_advance'
            );
        } catch (\Exception $e) {
            \Log::warning('Notification avance approuvée échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Avance de {$advance->amount} FCFA approuvée. Un prêt a été créé et le portefeuille a été crédité.",
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

        // Notification push à l'employé
        try {
            $employee = User::find($advance->user_id);
            $pushService = new PushNotificationService();
            $pushService->sendToUser(
                $employee,
                'Demande d\'avance refusée',
                'Votre demande d\'avance de ' . number_format($advance->amount, 0, ',', '.') . ' FCFA a été refusée.',
                [
                    'type' => 'salary_advance_rejected',
                    'reason' => $request->admin_note,
                ],
                'salary_advance'
            );
        } catch (\Exception $e) {
            \Log::warning('Notification avance rejetée échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande rejetée.',
        ]);
    }
}
