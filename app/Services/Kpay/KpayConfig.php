<?php

namespace App\Services\Kpay;

use App\Exceptions\KpayException;
use App\Models\Parametre;

/**
 * Configuration KPay lue depuis la table `parametres`, editable dans le back-office.
 * Aucune cle n'est stockee dans le .env : l'administrateur les saisit dans le panel.
 */
class KpayConfig
{
    public const GROUPE = 'kpay';

    public const URL_BASE = 'kpay.url_base';
    public const CLE_API = 'kpay.cle_api';
    public const CLE_SECRETE = 'kpay.cle_secrete';
    public const SECRET_WEBHOOK = 'kpay.secret_webhook';
    public const PROVIDER_DEFAUT = 'kpay.provider_defaut';
    public const DEVISE = 'kpay.devise';
    public const ACTIF = 'kpay.actif';
    public const PAYOUT_AUTO = 'kpay.payout_auto';
    public const MONTANT_MIN_PAYOUT = 'kpay.montant_min_payout';

    /**
     * Adresse publique par laquelle KPay atteint l'application.
     *
     * En developpement, le serveur ecoute en local et n'est joignable que par
     * un tunnel (ngrok) : c'est cette adresse, et non celle de l'application,
     * qui doit figurer dans le tableau de bord KPay.
     */
    public const URL_PUBLIQUE = 'kpay.url_publique';

    public function urlBase(): string
    {
        return rtrim(Parametre::lire(self::URL_BASE, 'https://admin.kpay.site'), '/');
    }

    public function cleApi(): ?string
    {
        return Parametre::lire(self::CLE_API);
    }

    public function cleSecrete(): ?string
    {
        return Parametre::lire(self::CLE_SECRETE);
    }

    public function secretWebhook(): ?string
    {
        return Parametre::lire(self::SECRET_WEBHOOK);
    }

    public function providerDefaut(): string
    {
        return Parametre::lire(self::PROVIDER_DEFAUT, 'MTN_MOMO_CMR');
    }

    public function devise(): string
    {
        return Parametre::lire(self::DEVISE, 'XAF');
    }

    public function estActif(): bool
    {
        return Parametre::lire(self::ACTIF, '0') === '1';
    }

    /** Le versement des primes part-il automatiquement a la cloture de paie ? */
    public function payoutAutomatique(): bool
    {
        return Parametre::lire(self::PAYOUT_AUTO, '0') === '1';
    }

    public function montantMinimumPayout(): int
    {
        return (int) Parametre::lire(self::MONTANT_MIN_PAYOUT, '100');
    }

    /** Racine publique des URLs de notification ; l'URL de l'app en repli. */
    public function urlPublique(): string
    {
        return rtrim(Parametre::lire(self::URL_PUBLIQUE) ?: config('app.url'), '/');
    }

    /**
     * URLs de callback a declarer dans le tableau de bord KPay.
     *
     * @return array<string, string>
     */
    public function urlsWebhook(): array
    {
        $racine = $this->urlPublique();

        return [
            'generique' => $racine.'/api/webhook/kpay',
            'depots' => $racine.'/api/webhook/deposit',
            'retraits' => $racine.'/api/webhook/payout',
            'remboursements' => $racine.'/api/webhook/refunds',
        ];
    }

    /** L'environnement decoule du prefixe de la cle publique. */
    public function estModeTest(): bool
    {
        return str_starts_with((string) $this->cleApi(), 'kpay_test_');
    }

    public function environnement(): string
    {
        return $this->estModeTest() ? 'Test (sandbox)' : 'Production';
    }

    public function estConfigure(): bool
    {
        return filled($this->cleApi()) && filled($this->cleSecrete());
    }

    /** @throws KpayException */
    public function assurerConfigure(): void
    {
        if (! $this->estConfigure()) {
            throw new KpayException(
                'Les clés KPay ne sont pas renseignées. Configurez-les dans Paramètres → Configuration KPay.',
            );
        }

        if (! $this->estActif()) {
            throw new KpayException(
                'L’intégration KPay est désactivée. Activez-la dans Paramètres → Configuration KPay.',
            );
        }
    }
}
