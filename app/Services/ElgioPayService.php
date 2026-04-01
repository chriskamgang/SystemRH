<?php

namespace App\Services;

use ElgioPay\SDK\ElgioPayClient;
use ElgioPay\SDK\ElgioPayException;
use Illuminate\Support\Facades\Log;

class ElgioPayService
{
    private ElgioPayClient $client;

    public function __construct()
    {
        $this->client = new ElgioPayClient(
            config('services.elgiopay.environment', 'prod'),
            config('services.elgiopay.api_key')
        );
    }

    /**
     * Effectuer un transfert (payout) vers un numéro mobile money
     */
    public function transfer(string $phone, int $amount, string $recipientName, string $description): array
    {
        try {
            $result = $this->client->createPayout([
                'amount' => $amount,
                'currency' => 'XAF',
                'payout_method' => ElgioPayClient::detectPaymentMethod($phone),
                'recipient_phone' => $phone,
                'recipient_name' => $recipientName,
                'description' => $description,
            ]);

            Log::info('ElgioPay payout créé', [
                'phone' => $phone,
                'amount' => $amount,
                'result' => $result,
            ]);

            return $result;
        } catch (ElgioPayException $e) {
            Log::error('ElgioPay payout échoué', [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]);

            throw $e;
        }
    }

    /**
     * Recharger le compte ElgioPay (collecte depuis mobile money)
     */
    public function collect(string $phone, int $amount, string $description): array
    {
        try {
            $result = $this->client->initiatePayment([
                'amount' => $amount,
                'currency' => 'XAF',
                'payment_method' => ElgioPayClient::detectPaymentMethod($phone),
                'customer_phone' => $phone,
                'description' => $description,
            ]);

            Log::info('ElgioPay collecte initiée', [
                'phone' => $phone,
                'amount' => $amount,
                'result' => $result,
            ]);

            return $result;
        } catch (ElgioPayException $e) {
            Log::error('ElgioPay collecte échouée', [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]);

            throw $e;
        }
    }

    /**
     * Vérifier le statut d'un paiement (collecte)
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            return $this->client->getPaymentStatus($paymentId);
        } catch (ElgioPayException $e) {
            Log::error('ElgioPay getPaymentStatus échoué', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Vérifier le statut d'un transfert (payout)
     */
    public function getTransferStatus(string $payoutId): array
    {
        try {
            return $this->client->getPayoutStatus($payoutId);
        } catch (ElgioPayException $e) {
            Log::error('ElgioPay getPayoutStatus échoué', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir le solde du compte ElgioPay
     */
    public function getBalance(): array
    {
        try {
            return $this->client->getBalance();
        } catch (ElgioPayException $e) {
            Log::error('ElgioPay getBalance échoué', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
