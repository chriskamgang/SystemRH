<?php

namespace App\Http\Controllers\Admin\Bus;

use App\Exceptions\KpayException;
use App\Http\Controllers\Controller;
use App\Models\Parametre;
use App\Models\Tarif;
use App\Services\Kpay\KpayClient;
use App\Services\Kpay\KpayConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Parametres du transport : grille tarifaire et passerelle de paiement.
 *
 * Ces deux ecrans viennent du back-office d'origine d'INSAM BUS, ou ils
 * etaient rendus par Filament. Ils sont ici reecrits dans le gabarit
 * d'Estuaire RH, mais s'appuient sur les memes services : `KpayConfig`
 * pour les cles, `Tarif` pour les formules.
 */
class ParametreController extends Controller
{
    // --- Grille tarifaire -------------------------------------------------

    /**
     * Les formules proposees a l'etudiant.
     *
     * Le montant est celui d'une journee : le prix du pass en decoule, tout
     * comme le nombre de trajets qu'il ouvre. C'est ce calcul que
     * `AbonnementService` rejoue a la souscription.
     */
    public function tarifs(): View
    {
        return view('admin.bus.parametres.tarifs', [
            'tarifs' => Tarif::withCount('abonnements')->orderBy('code')->get(),
        ]);
    }

    public function enregistrerTarif(Request $requete): RedirectResponse
    {
        Tarif::create($this->reglesTarif($requete) + ['actif' => true]);

        return back()->with('success', 'Formule créée.');
    }

    public function modifierTarif(Request $requete, Tarif $tarif): RedirectResponse
    {
        $tarif->update($this->reglesTarif($requete, $tarif) + ['actif' => $requete->boolean('actif')]);

        return back()->with('success', 'Formule mise à jour.');
    }

    public function supprimerTarif(Tarif $tarif): RedirectResponse
    {
        // Un tarif deja souscrit est reference par des abonnements en cours :
        // l'effacer priverait ces pass de leur grille de calcul. On le retire
        // du catalogue plutot que de la base.
        if ($tarif->abonnements()->exists()) {
            $tarif->update(['actif' => false]);

            return back()->with('success', 'Formule retirée du catalogue (des abonnements y sont rattachés).');
        }

        $tarif->delete();

        return back()->with('success', 'Formule supprimée.');
    }

    /** @return array<string, mixed> */
    private function reglesTarif(Request $requete, ?Tarif $tarif = null): array
    {
        return $requete->validate([
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('tarifs', 'code')->ignore($tarif?->id),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            // Prix d'une journee : le total facture vaut ce montant
            // multiplie par le nombre de jours couverts.
            'montant_fcfa' => ['required', 'integer', 'min:0'],
            'jours_couverts' => ['required', 'integer', 'min:1', 'max:365'],
            'trajets_par_jour' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
    }

    // --- Passerelle de paiement -------------------------------------------

    /**
     * Configuration KPay.
     *
     * Les cles vivent en base, chiffrees, et non dans le .env : elles se
     * remplacent sans redeploiement. L'environnement — bac a sable ou
     * production — se lit sur le prefixe de la cle publique.
     */
    public function kpay(KpayConfig $config): View
    {
        return view('admin.bus.parametres.kpay', [
            'config' => $config,
            'providers' => self::PROVIDERS,
            'urls' => $config->urlsWebhook(),
        ]);
    }

    public function enregistrerKpay(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'url_base' => ['required', 'url', 'max:255'],
            'cle_api' => ['nullable', 'string', 'max:255'],
            'cle_secrete' => ['nullable', 'string', 'max:255'],
            'secret_webhook' => ['nullable', 'string', 'max:255'],
            'provider_defaut' => ['required', Rule::in(array_keys(self::PROVIDERS))],
            'devise' => ['required', 'string', 'max:5'],
            'montant_min_payout' => ['required', 'integer', 'min:100'],
            'url_publique' => ['nullable', 'url', 'max:255'],
        ]);

        Parametre::ecrire(KpayConfig::URL_BASE, $donnees['url_base'], KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::PROVIDER_DEFAUT, $donnees['provider_defaut'], KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::DEVISE, $donnees['devise'], KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::ACTIF, $requete->boolean('actif') ? '1' : '0', KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::PAYOUT_AUTO, $requete->boolean('payout_auto') ? '1' : '0', KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::MONTANT_MIN_PAYOUT, (string) $donnees['montant_min_payout'], KpayConfig::GROUPE);
        Parametre::ecrire(KpayConfig::URL_PUBLIQUE, $donnees['url_publique'] ?? '', KpayConfig::GROUPE);

        // Un secret laisse vide n'est pas reecrit : le formulaire ne
        // reaffiche jamais les cles en clair, et un champ vide signifie
        // « ne touche pas », non « efface ».
        foreach ([
            KpayConfig::CLE_API => 'cle_api',
            KpayConfig::CLE_SECRETE => 'cle_secrete',
            KpayConfig::SECRET_WEBHOOK => 'secret_webhook',
        ] as $cle => $champ) {
            if (filled($donnees[$champ] ?? null)) {
                Parametre::ecrire($cle, $donnees[$champ], KpayConfig::GROUPE, chiffre: true);
            }
        }

        return back()->with('success', 'Configuration enregistrée — environnement : '
            .app(KpayConfig::class)->environnement());
    }

    /** Verifie les cles en interrogeant KPay. */
    public function testerKpay(KpayClient $client, KpayConfig $config): RedirectResponse
    {
        try {
            $infos = $client->informationsApplication();
        } catch (KpayException $e) {
            return back()->with('error', 'Connexion échouée : '.$e->getMessage());
        }

        return back()->with('success', sprintf(
            'Connexion réussie — application « %s », environnement %s.',
            $infos['name'] ?? $infos['applicationName'] ?? 'inconnue',
            $config->environnement(),
        ));
    }

    /** Operateurs Mobile Money desservis par KPay. */
    public const PROVIDERS = [
        'MTN_MOMO_CMR' => 'Cameroun — MTN MoMo',
        'ORANGE_CMR' => 'Cameroun — Orange Money',
        'AIRTEL_GAB' => 'Gabon — Airtel Money',
        'AIRTEL_COG' => 'Congo — Airtel Money',
        'MTN_MOMO_COG' => 'Congo — MTN MoMo',
        'MTN_MOMO_BEN' => 'Bénin — MTN MoMo',
        'MOOV_BEN' => 'Bénin — Moov',
        'MTN_MOMO_CIV' => 'Côte d’Ivoire — MTN MoMo',
        'ORANGE_CIV' => 'Côte d’Ivoire — Orange Money',
        'ORANGE_SEN' => 'Sénégal — Orange Money',
        'FREE_SEN' => 'Sénégal — Free',
        'MPESA_KEN' => 'Kenya — M-Pesa',
        'VODACOM_MPESA_COD' => 'RD Congo — Vodacom M-Pesa',
        'AIRTEL_COD' => 'RD Congo — Airtel Money',
        'ORANGE_COD' => 'RD Congo — Orange Money',
        'MTN_MOMO_RWA' => 'Rwanda — MTN MoMo',
        'AIRTEL_RWA' => 'Rwanda — Airtel Money',
        'ORANGE_SLE' => 'Sierra Leone — Orange Money',
        'MTN_MOMO_UGA' => 'Ouganda — MTN MoMo',
        'AIRTEL_OAPI_UGA' => 'Ouganda — Airtel Money',
        'MTN_MOMO_ZMB' => 'Zambie — MTN MoMo',
        'AIRTEL_OAPI_ZMB' => 'Zambie — Airtel Money',
        'ZAMTEL_ZMB' => 'Zambie — Zamtel',
    ];
}
