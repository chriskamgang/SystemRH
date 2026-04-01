<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\ElgioPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Afficher le solde et les transactions récentes
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $transactions = $wallet->transactions()
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'wallet' => [
                'balance' => (int) $wallet->balance,
                'currency' => 'XAF',
            ],
            'transactions' => $transactions->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => (int) $t->amount,
                    'balance_before' => (int) $t->balance_before,
                    'balance_after' => (int) $t->balance_after,
                    'description' => $t->description,
                    'reference' => $t->reference,
                    'source_type' => $t->source_type,
                    'elgiopay_status' => $t->elgiopay_status,
                    'transfer_phone' => $t->transfer_phone,
                    'transfer_method' => $t->transfer_method,
                    'created_at' => $t->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Transférer de l'argent du portefeuille vers un numéro mobile money
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|integer|min:100',
            'method' => 'required|in:mtn_mobile_money,orange_money',
        ]);

        $user = $request->user();
        $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant.',
            ], 422);
        }

        try {
            // Étape 1 : Débiter le portefeuille
            $transaction = $wallet->debit(
                $request->amount,
                "Transfert vers {$request->phone}",
                'transfer'
            );

            // Étape 2 : Appeler ElgioPay pour le payout
            $elgioPayService = new ElgioPayService();
            $result = $elgioPayService->transfer(
                $request->phone,
                $request->amount,
                $user->full_name,
                "Transfert portefeuille - {$user->full_name}"
            );

            $payoutId = $result['data']['id'] ?? $result['payout_id'] ?? $result['id'] ?? null;
            $payoutStatus = $result['data']['status'] ?? $result['status'] ?? 'pending';

            $transaction->update([
                'type' => 'transfer',
                'elgiopay_payout_id' => $payoutId,
                'elgiopay_status' => $payoutStatus,
                'transfer_phone' => $request->phone,
                'transfer_method' => $request->method,
            ]);

            // Étape 3 : Polling du statut (max 30s, toutes les 3s)
            if ($payoutId && in_array($payoutStatus, ['pending', 'processing'])) {
                for ($i = 0; $i < 10; $i++) {
                    sleep(3);
                    try {
                        $statusResult = $elgioPayService->getTransferStatus($payoutId);
                        $payoutStatus = $statusResult['data']['status'] ?? $statusResult['status'] ?? $payoutStatus;
                        $transaction->update(['elgiopay_status' => $payoutStatus]);

                        if (!in_array($payoutStatus, ['pending', 'processing'])) {
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::warning('ElgioPay poll status échoué', ['error' => $e->getMessage()]);
                    }
                }
            }

            // Étape 4 : Gérer le résultat final
            if (in_array($payoutStatus, ['failed', 'rejected', 'cancelled'])) {
                // Rembourser le portefeuille
                $wallet->credit(
                    $request->amount,
                    "Remboursement - Transfert échoué ({$payoutStatus})",
                    'refund',
                    'REF-' . $transaction->reference
                );

                return response()->json([
                    'success' => false,
                    'message' => "Le transfert a échoué ({$payoutStatus}). Votre portefeuille a été remboursé.",
                ], 422);
            }

            $message = in_array($payoutStatus, ['successful', 'completed', 'success'])
                ? 'Transfert effectué avec succès.'
                : 'Transfert en cours de traitement.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'transaction' => [
                    'id' => $transaction->id,
                    'amount' => (int) $transaction->amount,
                    'balance_after' => (int) $transaction->balance_after,
                    'reference' => $transaction->reference,
                    'elgiopay_status' => $payoutStatus,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Wallet transfer error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le transfert a échoué. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historique paginé des transactions
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'transactions' => $transactions->through(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => (int) $t->amount,
                    'balance_before' => (int) $t->balance_before,
                    'balance_after' => (int) $t->balance_after,
                    'description' => $t->description,
                    'reference' => $t->reference,
                    'source_type' => $t->source_type,
                    'elgiopay_status' => $t->elgiopay_status,
                    'transfer_phone' => $t->transfer_phone,
                    'transfer_method' => $t->transfer_method,
                    'created_at' => $t->created_at->toISOString(),
                ];
            }),
        ]);
    }
}
