<?php

namespace App\Services\Kpay;

use App\Exceptions\KpayException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP de l'API KPay.
 *
 * L'environnement (sandbox / production) n'est pas une URL mais un prefixe de cle :
 * `kpay_test_` route vers le bac a sable, `kpay_live_` vers la production.
 */
class KpayClient
{
    public function __construct(private readonly KpayConfig $config) {}

    /** Encaissement Mobile Money — POST /api/v1/payments/init */
    public function initierPaiement(array $donnees): array
    {
        return $this->post('/api/v1/payments/init', $donnees);
    }

    /** Versement vers un compte Mobile Money — POST /api/v1/payments/withdraw */
    public function initierRetrait(array $donnees): array
    {
        return $this->post('/api/v1/payments/withdraw', $donnees);
    }

    /** Statut d'un paiement — GET /api/v1/payments/:id */
    public function consulterPaiement(string $id): array
    {
        return $this->get("/api/v1/payments/{$id}");
    }

    /** Informations de l'application, utilise pour tester les cles saisies. */
    public function informationsApplication(): array
    {
        return $this->get('/api/v1/payments/me');
    }

    /** Solde du wallet, controle avant de lancer une campagne de payouts. */
    public function solde(): array
    {
        return $this->get('/api/v1/payments/balance');
    }

    /** Deduit l'operateur a partir d'un numero de telephone. */
    public function deduireProvider(string $telephone): array
    {
        return $this->post('/api/v1/payments/predict-provider', ['phoneNumber' => $telephone]);
    }

    private function get(string $chemin): array
    {
        return $this->executer('get', $chemin);
    }

    private function post(string $chemin, array $donnees = []): array
    {
        return $this->executer('post', $chemin, $donnees);
    }

    private function executer(string $methode, string $chemin, array $donnees = []): array
    {
        $this->config->assurerConfigure();

        $url = $this->config->urlBase().$chemin;

        try {
            $reponse = $this->requete()->{$methode}($url, $donnees);
        } catch (\Throwable $e) {
            Log::error('KPay : échec réseau', ['url' => $url, 'erreur' => $e->getMessage()]);

            throw new KpayException(
                'Le service de paiement est momentanément injoignable. Réessayez dans un instant.',
            );
        }

        $corps = $reponse->json() ?? [];

        if ($reponse->failed()) {
            $message = $corps['message'] ?? 'Erreur inconnue du service de paiement.';

            Log::warning('KPay : réponse en erreur', [
                'url' => $url,
                'statut' => $reponse->status(),
                'message' => $message,
            ]);

            throw new KpayException($message, $reponse->status(), $corps);
        }

        return $corps;
    }

    private function requete(): PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => $this->config->cleApi(),
            'X-Secret-Key' => $this->config->cleSecrete(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            // Backoff exponentiel : recommande par KPay sur 429 et erreurs serveur.
            ->retry(3, 1000, function ($exception, $request) {
                $statut = $exception instanceof \Illuminate\Http\Client\RequestException
                    ? $exception->response->status()
                    : null;

                return $statut === 429 || $statut >= 500;
            }, throw: false);
    }
}
