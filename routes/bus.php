<?php

use App\Http\Controllers\Bus\AbonnementController;
use App\Http\Controllers\Bus\AuthController;
use App\Http\Controllers\Bus\EmbarquementController;
use App\Http\Controllers\Bus\KpayWebhookController;
use App\Http\Controllers\Bus\NotificationController;
use App\Http\Controllers\Bus\PointageController;
use App\Http\Controllers\Bus\PresenceController;
use App\Http\Controllers\Bus\PrimeController;
use App\Http\Controllers\Bus\ReseauController;
use App\Http\Controllers\Bus\SecoursController;
use App\Http\Controllers\Bus\SuiviController;
use App\Http\Controllers\Bus\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API INSAM BUS
|--------------------------------------------------------------------------
| Consommee par les applications mobiles Flutter (etudiant et chauffeur).
|
| Ces routes sont montees sous le prefixe /api/bus par bootstrap/app.php :
| elles vivent aux cotes de l'API d'Estuaire RH sans jamais la croiser, les
| deux espaces n'ayant en commun que la table `users`.
|
| Le back-office de regulation est servi par les vues Blade de /admin/bus.
*/

// --- Acces public ---------------------------------------------------------

// Connexion courante de l'etudiant : son adresse et son PIN a 4 chiffres.
// Le debit est serre — quatre chiffres ne font que dix mille combinaisons —
// et le compte se ferme en plus au bout de cinq essais manques.
Route::post('connexion-pin', [AuthController::class, 'connexionPin'])
    ->middleware('throttle:10,1');

// Inscription : adresse et PIN choisi. Aucun code ne circule par email.
Route::post('inscription-pin', [AuthController::class, 'inscriptionPin'])
    ->middleware('throttle:6,1');

// Aiguillage de l'accueil : dit par ou passe le compte saisi (PIN pour un
// etudiant, mot de passe pour un chauffeur) et s'il reste a creer.
Route::post('aiguillage', [AuthController::class, 'aiguillage'])
    ->middleware('throttle:20,1');

// Connexion par mot de passe : chauffeurs et back-office.
Route::post('connexion', [AuthController::class, 'connexion'])
    ->middleware('throttle:10,1');

// Catalogue du reseau. Les points de ramassage alimentent le choix fait a
// l'inscription ; le campus n'est pas demande, il change d'un jour a l'autre.
Route::get('points-ramassage', [ReseauController::class, 'pointsRamassage']);
Route::get('lieux', [ReseauController::class, 'lieux']);
Route::get('parcours', [ReseauController::class, 'parcours']);
Route::get('lignes', [SuiviController::class, 'lignes']);

// Niveaux et specialites d'Estuaire RH, proposes a la completion de profil :
// c'est ce couple qui rattache l'etudiant a son emploi du temps.
Route::get('scolarite', [ReseauController::class, 'scolarite']);

// Bus actuellement en ligne, stationnes comme en circulation. Publique :
// elle ne porte qu'une immatriculation et une position, soit ce qu'un
// passant lit sur le flanc du vehicule.
Route::get('bus-en-ligne', [PresenceController::class, 'busEnLigne']);

// Parametres de connexion temps reel (cle publique uniquement).
Route::get('temps-reel', [SuiviController::class, 'configTempsReel']);
Route::get('tarifs', [AbonnementController::class, 'tarifs']);

// Notifications KPay : authentifiees par signature HMAC, pas par token Sanctum.
//
// KPay adresse jusqu'a quatre URLs, une par famille d'evenements plus une
// generique en repli. Les chemins ci-dessous sont ceux declares dans le
// tableau de bord KPay ; la famille attendue est passee au controleur, qui
// refuse un evenement adresse a la mauvaise URL.
Route::post('webhook/kpay', KpayWebhookController::class);
Route::post('webhook/deposit', [KpayWebhookController::class, '__invoke'])
    ->defaults('famille', 'payment');
Route::post('webhook/payout', [KpayWebhookController::class, '__invoke'])
    ->defaults('famille', 'payout');
Route::post('webhook/refunds', [KpayWebhookController::class, '__invoke'])
    ->defaults('famille', 'refund');

// Ancien chemin, conserve le temps que la configuration KPay soit reprise.
Route::post('webhooks/kpay', KpayWebhookController::class);

Route::middleware('auth:sanctum')->group(function () {

    // --- Commun ----------------------------------------------------------

    Route::get('profil', [AuthController::class, 'profil']);
    Route::put('profil', [AuthController::class, 'completerProfil']);
    Route::post('deconnexion', [AuthController::class, 'deconnexion']);

    // Choix du PIN, juste apres la verification du code : le token delivre
    // par `otp/verifier` fait office de preuve de l'adresse.
    Route::put('pin', [AuthController::class, 'definirPin'])
        ->middleware('throttle:10,1');

    // Suppression definitive du compte, exigee par l'App Store des lors que
    // l'application en ouvre (5.1.1(v)). Geste irreversible, donc peu
    // d'essais autorises.
    Route::delete('compte', [AuthController::class, 'supprimerCompte'])
        ->middleware('throttle:3,1');

    // Jeton FCM de l'appareil : pose a la connexion, retire a la
    // deconnexion, remis a jour a chaque rotation par Firebase.
    Route::post('appareils', [NotificationController::class, 'enregistrerAppareil']);
    Route::delete('appareils', [NotificationController::class, 'oublierAppareil']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('non-lues', [NotificationController::class, 'nonLues']);
        Route::post('tout-lire', [NotificationController::class, 'toutMarquerLu']);
        // Declaree avant la route parametree, sinon « tout » serait pris
        // pour un identifiant de notification.
        Route::delete('tout', [NotificationController::class, 'toutSupprimer']);
        Route::post('{notification}/lire', [NotificationController::class, 'marquerLue']);
        Route::delete('{notification}', [NotificationController::class, 'supprimer']);
    });

    // --- Espace etudiant (3.1) -------------------------------------------

    Route::middleware('role:etudiant')->prefix('etudiant')->group(function () {
        Route::get('mon-bus', [SuiviController::class, 'monBus']);

        // Changement d'arret : l'etudiant qui demenage n'a pas a refaire
        // toute la completion de profil pour corriger son point de montee.
        Route::put('point-ramassage', [AuthController::class, 'changerPointRamassage']);
        Route::get('mes-trajets', [SuiviController::class, 'mesTrajets']);

        Route::get('abonnements', [AbonnementController::class, 'index']);
        Route::post('abonnements', [AbonnementController::class, 'store']);
        Route::get('abonnements/actif', [AbonnementController::class, 'actif']);

        // QR presente au chauffeur a la montee : rotatif, donc regenere a
        // chaque affichage de l'ecran du pass.
        Route::get('pass/qr', [AbonnementController::class, 'jetonQr']);
        Route::post('abonnements/{abonnement}/paiement', [AbonnementController::class, 'confirmerPaiement']);

        // Paiement Mobile Money via KPay : push USSD sur le telephone de l'etudiant.
        Route::post('abonnements/{abonnement}/payer', [AbonnementController::class, 'payerParMobileMoney']);
        Route::get('abonnements/{abonnement}/statut-paiement', [AbonnementController::class, 'statutPaiement']);
    });

    // --- Espace chauffeur (3.2 et 3.3) -----------------------------------

    Route::middleware('role:chauffeur')->prefix('chauffeur')->group(function () {

        // Presence en ligne : le chauffeur diffuse sa position des
        // l'ouverture de l'application, sans attendre un tour demarre. Le
        // debit autorise couvre un battement toutes les dix secondes.
        Route::post('presence', [PresenceController::class, 'battre'])
            ->middleware('throttle:30,1');
        Route::delete('presence', [PresenceController::class, 'quitter']);

        Route::get('service-du-jour', [PointageController::class, 'serviceDuJour']);

        // Cycle de pointage sequentiel des tours.
        Route::post('tournees/demarrer', [PointageController::class, 'demarrer']);
        Route::post('tournees/{tournee}/arrivee-point', [PointageController::class, 'arriverAuPoint']);
        Route::post('tournees/{tournee}/effectif', [PointageController::class, 'saisirEffectif']);
        Route::post('tournees/{tournee}/depart', [PointageController::class, 'partir']);

        // Billettique : le scan du QR consomme un trajet du pass etudiant et
        // alimente l'ecart confronte a l'effectif declare.
        Route::post('tournees/{tournee}/scanner', [EmbarquementController::class, 'scanner']);
        Route::get('tournees/{tournee}/comptage', [EmbarquementController::class, 'comptageDuTour']);
        Route::post('tournees/{tournee}/justifier-ecart', [EmbarquementController::class, 'justifierEcart']);
        Route::get('motifs-ecart', [EmbarquementController::class, 'motifs']);
        Route::post('tournees/{tournee}/terminer', [PointageController::class, 'terminer']);
        Route::post('tournees/{tournee}/position', [PointageController::class, 'remonterPosition']);

        // Signalement d'un retard : previent les etudiants desservis (US-04).
        Route::post('tournees/{tournee}/retard', [PointageController::class, 'signalerRetard'])
            ->middleware('throttle:10,1');

        // Pannes et missions de secours.
        Route::post('pannes', [SecoursController::class, 'declarerPanne']);
        Route::get('missions', [SecoursController::class, 'mesMissions']);
        Route::post('missions/{mission}/accepter', [SecoursController::class, 'accepter']);
        Route::post('missions/{mission}/demarrer', [SecoursController::class, 'demarrer']);
        Route::post('missions/{mission}/terminer', [SecoursController::class, 'terminer']);

        // Cagnotte et historique des primes.
        Route::get('cagnotte', [PrimeController::class, 'cagnotte']);
        Route::get('primes', [PrimeController::class, 'historique']);

        // Wallet : la recette des trajets scannes tombe ici au fil de l'eau,
        // et le chauffeur declenche son retrait quand il le souhaite.
        Route::get('wallet', [WalletController::class, 'index']);
        Route::get('wallet/retraits', [WalletController::class, 'retraits']);
        Route::post('wallet/retirer', [WalletController::class, 'retirer'])
            ->middleware('throttle:6,1');
    });
});
