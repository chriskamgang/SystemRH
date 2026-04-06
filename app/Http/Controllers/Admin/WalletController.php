<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ElgioPayService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class WalletController extends Controller
{
    /**
     * Liste de tous les employés avec leur solde portefeuille
     */
    public function index(Request $request)
    {
        $query = User::with(['wallet', 'department'])
            ->where('is_active', true)
            ->whereHas('role', function ($q) {
                $q->where('name', '!=', 'admin');
            });

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->employee_type) {
            $query->where('employee_type', $request->employee_type);
        }

        $employees = $query->orderBy('last_name')->paginate(30);

        $totalBalance = Wallet::sum('balance');
        $walletCount = Wallet::count();

        return view('admin.wallets.index', compact('employees', 'totalBalance', 'walletCount'));
    }

    /**
     * Export wallets as PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = User::with(['wallet', 'department'])
            ->where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('name', '!=', 'admin'));

        $filterParts = [];

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
            $filterParts[] = 'Recherche: ' . $search;
        }

        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->employee_type);
            $filterParts[] = 'Type: ' . $request->employee_type;
        }

        $employees = $query->orderBy('last_name')->get();
        $totalBalance = $employees->sum(fn($e) => $e->wallet->balance ?? 0);
        $filters = !empty($filterParts) ? implode(' | ', $filterParts) : null;

        $pdf = Pdf::loadView('admin.wallets.pdf.report', compact('employees', 'totalBalance', 'filters'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rapport-portefeuilles.pdf');
    }

    /**
     * Détail du portefeuille d'un employé
     */
    public function show($userId)
    {
        $user = User::with(['wallet.transactions' => function ($q) {
            $q->latest()->take(50);
        }, 'department'])->findOrFail($userId);

        $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'employee_type' => $user->employee_type,
            ],
            'wallet' => [
                'balance' => (int) $wallet->balance,
            ],
            'transactions' => $transactions->items(),
        ]);
    }

    /**
     * Créditer le portefeuille d'un employé
     */
    public function credit(Request $request, $userId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($userId);
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $description = $request->description ?: 'Crédit manuel par admin';

        $wallet->credit(
            $request->amount,
            $description,
            'salary'
        );

        // Notification push
        try {
            $pushService = new PushNotificationService();
            $pushService->sendToUser(
                $user,
                'Portefeuille crédité',
                number_format($request->amount, 0, ',', '.') . ' FCFA ont été ajoutés à votre portefeuille.',
                [
                    'type' => 'wallet_credited',
                    'amount' => (string) $request->amount,
                ],
                'wallet'
            );
        } catch (\Exception $e) {
            Log::warning('Notification crédit wallet échouée: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => number_format($request->amount, 0, ',', '.') . " FCFA crédités au portefeuille de {$user->full_name}.",
            'new_balance' => (int) $wallet->fresh()->balance,
        ]);
    }

    /**
     * Créditer plusieurs employés en une fois
     */
    public function creditMultiple(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        $description = $request->description ?: 'Paiement groupé par admin';
        $count = 0;
        $errors = [];
        $creditedUsers = [];

        foreach ($request->user_ids as $userId) {
            try {
                $user = User::find($userId);
                if (!$user) continue;

                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $userId],
                    ['balance' => 0]
                );

                $wallet->credit($request->amount, $description, 'salary');
                $creditedUsers[] = $user;
                $count++;
            } catch (\Exception $e) {
                $errors[] = "Erreur pour l'utilisateur #{$userId}: " . $e->getMessage();
                Log::error('Erreur crédit multiple wallet', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notifications push groupées
        if (!empty($creditedUsers)) {
            try {
                $pushService = new PushNotificationService();
                $pushService->sendToMultipleUsers(
                    $creditedUsers,
                    'Portefeuille crédité',
                    number_format($request->amount, 0, ',', '.') . ' FCFA ont été ajoutés à votre portefeuille.',
                    [
                        'type' => 'wallet_credited',
                        'amount' => (string) $request->amount,
                    ],
                    'wallet'
                );
            } catch (\Exception $e) {
                Log::warning('Notification crédit multiple échouée: ' . $e->getMessage());
            }
        }

        $message = "{$count} portefeuille(s) crédité(s) de " . number_format($request->amount, 0, ',', '.') . " FCFA.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' erreur(s).';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'credited' => $count,
            'errors' => $errors,
        ]);
    }

    /**
     * Vérifier le solde ElgioPay (AJAX)
     */
    public function elgiopayBalance()
    {
        try {
            $service = new ElgioPayService();
            $result = $service->getBalance();

            return response()->json([
                'success' => true,
                'balance' => $result['data']['balance'] ?? $result['balance'] ?? 0,
                'currency' => $result['data']['currency'] ?? $result['currency'] ?? 'XAF',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer le solde ElgioPay: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recharger le compte ElgioPay depuis mobile money (AJAX)
     */
    public function elgiopayTopup(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|integer|min:100|max:1000000',
        ]);

        try {
            $service = new ElgioPayService();
            $result = $service->collect(
                $request->phone,
                $request->amount,
                'Rechargement compte ElgioPay - INSAM'
            );

            Log::info('Admin ElgioPay topup initié', [
                'admin_id' => auth()->id(),
                'phone' => $request->phone,
                'amount' => $request->amount,
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Demande de rechargement de " . number_format($request->amount, 0, ',', '.') . " FCFA envoyée. Veuillez confirmer sur votre téléphone.",
                'transaction_id' => $result['data']['id'] ?? $result['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec du rechargement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un paiement ElgioPay (AJAX)
     */
    public function elgiopayPaymentStatus($transactionId)
    {
        try {
            $service = new ElgioPayService();
            $result = $service->getPaymentStatus($transactionId);

            return response()->json([
                'success' => true,
                'status' => $result['data']['status'] ?? $result['status'] ?? 'pending',
                'data' => $result['data'] ?? $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
