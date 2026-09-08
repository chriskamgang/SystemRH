<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\TransactionKpay;
use App\Models\WebhookKpay;
use App\Services\Kpay\KpayConfig;
use App\Services\Kpay\KpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Reception des notifications KPay (source d'autorite du statut final).
 *
 * KPay adresse jusqu'a quatre URLs : une par famille d'evenements
 * (`payment.*`, `payout.*`, `refund.*`) et une generique en repli. Toutes
 * aboutissent ici ; la famille attendue est passee en argument de route, ce
 * qui permet de rejeter un evenement adresse a la mauvaise URL.
 *
 * La signature est un HMAC-SHA256 calcule sur le corps BRUT recu : il ne faut
 * donc jamais re-serialiser le JSON avant de la verifier.
 */
class KpayWebhookController extends Controller
{
    public function __construct(
        private readonly KpayService $kpay,
        private readonly KpayConfig $config,
    ) {}

    /** Statuts sur lesquels une decision metier se joue. */
    private const STATUTS_TERMINAUX = ['COMPLETED', 'FAILED', 'CANCELLED'];

    /**
     * @param  string|null  $famille  payment | payout | refund ; null pour l'URL generique.
     */
    public function __invoke(Request $request, ?string $famille = null): JsonResponse
    {
        $corpsBrut = $request->getContent();
        $signature = $request->header('X-KPAY-Signature');
        $evenement = $request->header('X-KPAY-Event') ?? $request->input('event', 'inconnu');

        $charge = json_decode($corpsBrut, true) ?? [];
        $signatureValide = $this->verifierSignature($corpsBrut, $signature);

        $journal = WebhookKpay::create([
            'evenement' => $evenement,
            'kpay_id' => $this->identifiantKpay($charge),
            'external_id' => $charge['externalId'] ?? null,
            'statut' => $charge['status'] ?? null,
            'charge_utile' => $charge,
            'signature_valide' => $signatureValide,
            'recu_le' => now(),
        ]);

        if (! $signatureValide) {
            Log::warning('KPay : signature de webhook invalide', ['evenement' => $evenement]);

            $journal->update(['erreur_traitement' => 'Signature invalide.']);

            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        // Une URL dediee ne doit traiter que sa famille : un evenement mal
        // aiguille serait applique au mauvais flux d'argent.
        if ($famille !== null && ! str_starts_with($evenement, $famille.'.')) {
            $journal->update([
                'traite' => true,
                'erreur_traitement' => "Événement « {$evenement} » reçu sur l’URL {$famille}.",
            ]);

            return response()->json(['message' => 'Événement hors périmètre de cette URL.'], 202);
        }

        try {
            $this->traiter($evenement, $charge, $journal);
        } catch (\Throwable $e) {
            Log::error('KPay : échec du traitement du webhook', [
                'evenement' => $evenement,
                'erreur' => $e->getMessage(),
            ]);

            $journal->update(['erreur_traitement' => $e->getMessage()]);

            // On repond 200 : un 5xx declencherait un reessai sur une erreur qui nous est propre.
            return response()->json(['message' => 'Reçu.']);
        }

        return response()->json(['message' => 'Reçu.']);
    }

    private function traiter(string $evenement, array $charge, WebhookKpay $journal): void
    {
        $statut = $charge['status'] ?? $this->statutDepuisEvenement($evenement);

        // `payment.initiated` et `payment.processing` sont informatifs : ils
        // ne closent rien et ne doivent jamais valoir paiement.
        if (! in_array($statut, self::STATUTS_TERMINAUX, true)) {
            $journal->update(['traite' => true]);

            return;
        }

        // Un remboursement porte lui aussi le statut COMPLETED : le confondre
        // avec un encaissement reactiverait un abonnement rembourse.
        if (str_starts_with($evenement, 'refund.')) {
            $this->traiterRemboursement($evenement, $charge, $journal);

            return;
        }

        $transaction = $this->retrouverTransaction($charge);

        if (! $transaction) {
            $journal->update([
                'traite' => true,
                'erreur_traitement' => 'Transaction locale introuvable.',
            ]);

            return;
        }

        // Un meme evenement peut arriver plusieurs fois : ne rien rejouer si deja terminal.
        if ($transaction->statut === $statut) {
            $journal->update(['traite' => true]);

            return;
        }

        $this->kpay->appliquerStatut($transaction, $statut, $charge);

        $journal->update(['traite' => true]);
    }

    /**
     * Remboursement abouti : le paiement d'origine cesse d'ouvrir des droits.
     *
     * La charge porte l'identifiant du remboursement ; c'est
     * `originalPaymentId` qui designe l'encaissement a neutraliser.
     */
    private function traiterRemboursement(string $evenement, array $charge, WebhookKpay $journal): void
    {
        if ($evenement !== 'refund.completed') {
            // Un remboursement echoue ou annule laisse le paiement en l'etat.
            $journal->update(['traite' => true]);

            return;
        }

        $origine = $charge['originalPaymentId'] ?? $charge['paymentId'] ?? null;

        $transaction = TransactionKpay::query()
            ->when($origine, fn ($q) => $q->where('kpay_id', $origine))
            ->when(
                ! $origine && ($charge['externalId'] ?? null),
                fn ($q) => $q->where('external_id', $charge['externalId']),
            )
            ->first();

        if (! $transaction) {
            $journal->update([
                'traite' => true,
                'erreur_traitement' => 'Paiement remboursé introuvable localement.',
            ]);

            return;
        }

        $this->kpay->appliquerRemboursement($transaction, $charge);

        $journal->update(['traite' => true]);
    }

    /** Retrouve la transaction visee, par externalId puis par identifiant KPay. */
    private function retrouverTransaction(array $charge): ?TransactionKpay
    {
        $externalId = $charge['externalId'] ?? null;
        $kpayId = $this->identifiantKpay($charge);

        return TransactionKpay::query()
            ->when($externalId, fn ($q) => $q->where('external_id', $externalId))
            ->when(! $externalId && $kpayId, fn ($q) => $q->where('kpay_id', $kpayId))
            ->first();
    }

    /** Les retraits portent `payoutId`, les encaissements `paymentId`. */
    private function identifiantKpay(array $charge): ?string
    {
        return $charge['paymentId']
            ?? $charge['payoutId']
            ?? $charge['refundId']
            ?? $charge['id']
            ?? null;
    }

    /** Statut deduit du seul nom de l'evenement, si la charge ne le porte pas. */
    private function statutDepuisEvenement(string $evenement): ?string
    {
        return match (true) {
            str_ends_with($evenement, '.completed') => 'COMPLETED',
            str_ends_with($evenement, '.failed') => 'FAILED',
            str_ends_with($evenement, '.cancelled') => 'CANCELLED',
            default => null,
        };
    }

    /** Comparaison en temps constant du HMAC-SHA256 sur le corps brut. */
    private function verifierSignature(string $corpsBrut, ?string $signature): bool
    {
        $secret = $this->config->secretWebhook();

        if (blank($secret) || blank($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $corpsBrut, $secret), $signature);
    }
}
