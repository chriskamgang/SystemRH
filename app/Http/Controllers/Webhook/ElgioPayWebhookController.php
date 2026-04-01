<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ElgioPayWebhookController extends Controller
{
    /**
     * Handle ElgioPay webhook notifications
     * POST /webhooks/elgiopay
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('ElgioPay Webhook reçu', $payload);

        $payoutId = $payload['payout_id'] ?? $payload['data']['id'] ?? $payload['id'] ?? null;
        $status = $payload['status'] ?? $payload['data']['status'] ?? null;

        if (!$payoutId || !$status) {
            Log::warning('ElgioPay Webhook: payload invalide', $payload);
            return response()->json(['message' => 'Payload invalide'], 400);
        }

        // Trouver la transaction correspondante
        $transaction = WalletTransaction::where('elgiopay_payout_id', $payoutId)->first();

        if (!$transaction) {
            Log::warning('ElgioPay Webhook: transaction non trouvée', ['payout_id' => $payoutId]);
            return response()->json(['message' => 'Transaction non trouvée'], 404);
        }

        $previousStatus = $transaction->elgiopay_status;
        $transaction->update(['elgiopay_status' => $status]);

        Log::info('ElgioPay Webhook: statut mis à jour', [
            'payout_id' => $payoutId,
            'previous_status' => $previousStatus,
            'new_status' => $status,
            'transaction_id' => $transaction->id,
        ]);

        // Si le transfert a échoué, rembourser le portefeuille
        if (in_array($status, ['failed', 'rejected', 'cancelled', 'reversed'])) {
            $wallet = Wallet::find($transaction->wallet_id);

            if ($wallet && $transaction->type !== 'credit') {
                $wallet->credit(
                    $transaction->amount,
                    "Remboursement - Transfert échoué ({$status})",
                    'refund',
                    'REF-' . $transaction->reference
                );

                Log::info('ElgioPay Webhook: portefeuille remboursé', [
                    'wallet_id' => $wallet->id,
                    'amount' => $transaction->amount,
                    'reason' => $status,
                ]);
            }
        }

        return response()->json(['message' => 'Webhook traité avec succès']);
    }
}
