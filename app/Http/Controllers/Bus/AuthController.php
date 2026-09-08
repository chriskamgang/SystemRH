<?php

namespace App\Http\Controllers\Bus;

use App\Enums\RoleUtilisateur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bus\ChangerPointRamassageRequest;
use App\Http\Requests\Bus\CompleterProfilRequest;
use App\Http\Requests\Bus\ConnexionPinRequest;
use App\Http\Requests\Bus\ConnexionRequest;
use App\Http\Requests\Bus\DefinirPinRequest;
use App\Http\Requests\Bus\InscriptionPinRequest;
use App\Http\Requests\Bus\AiguillageRequest;
use App\Http\Requests\Bus\VerifierOtpRequest;
use App\Http\Resources\Bus\UserResource;
use App\Models\Etudiant;
use App\Models\User;
use App\Models\WalletChauffeur;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Dit par ou passe la connexion, une fois l'adresse saisie (3.1).
     *
     * Aucun code ne circule par email : la reponse indique seulement si le
     * compte se deverrouille par PIN ou par mot de passe, et s'il reste a
     * creer.
     *
     * Aucune information n'est revelee sur l'existence du compte : une
     * adresse inconnue recoit un code comme une autre, et le compte sera
     * cree a la verification. Un chauffeur, lui, est signale des ici pour
     * que l'application n'affiche pas d'ecran de saisie de code.
     */
    public function aiguillage(AiguillageRequest $request): JsonResponse
    {
        $email = mb_strtolower(trim($request->validated('email')));

        $utilisateur = User::where('email', $email)->first();

        // Les chauffeurs sont crees au back-office : leur compte existe
        // deja et se deverrouille par mot de passe.
        //
        // `role_bus` est nul pour tout compte ne d'Estuaire RH — un etudiant
        // inscrit par la scolarite, un enseignant. Ces comptes-la ne sont pas
        // des chauffeurs : ils passent par le PIN comme les autres etudiants,
        // et lire `->value` sur ce nul faisait tomber l'accueil en erreur 500.
        if (
            $utilisateur
            && $utilisateur->role_bus !== null
            && $utilisateur->role_bus !== RoleUtilisateur::Etudiant
        ) {
            return response()->json([
                'canal' => 'mot_de_passe',
                'role' => $utilisateur->role_bus->value,
                'message' => 'Ce compte se connecte avec son mot de passe.',
            ]);
        }

        // Compte connu : il saisit son PIN. Adresse inconnue : il en
        // choisit un, ce qui vaut inscription. Aucun email n'est envoye.
        return response()->json([
            'canal' => 'pin',
            'inscription' => $utilisateur === null || ! $utilisateur->aUnPin(),
            'message' => 'Ce compte se connecte avec son code à 4 chiffres.',
        ]);
    }

    /**
     * Connexion de l'etudiant : son adresse et son PIN.
     *
     * Quatre chiffres ne font que dix mille combinaisons : la saisie se
     * ferme au bout de [MAX_ESSAIS_PIN] tentatives, sans quoi un essai
     * systematique aboutirait en quelques heures. Le compteur est porte par
     * le compte, non par l'adresse IP, pour qu'un changement de reseau ne
     * remette pas le compteur a zero.
     */
    public function connexionPin(ConnexionPinRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $email = mb_strtolower(trim($donnees['email']));

        $utilisateur = User::where('email', $email)->first();

        // Compte inconnu, chauffeur, ou etudiant sans PIN : une seule et
        // meme reponse, qui ne dit pas laquelle des trois s'applique.
        if (
            ! $utilisateur
            || $utilisateur->role_bus !== RoleUtilisateur::Etudiant
            || ! $utilisateur->aUnPin()
        ) {
            throw ValidationException::withMessages([
                'email' => ['Adresse ou code incorrect.'],
            ]);
        }

        if ($this->pinEstBloque($utilisateur)) {
            throw ValidationException::withMessages([
                'pin' => ['Trop d’essais. Réessaie dans un quart d’heure.'],
            ]);
        }

        if (! Hash::check($donnees['pin'], $utilisateur->pin)) {
            $this->compterEssaiManque($utilisateur);

            throw ValidationException::withMessages([
                'pin' => ['Adresse ou code incorrect.'],
            ]);
        }

        if (! $utilisateur->actif_bus) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est désactivé.'],
            ]);
        }

        // Reussite : le compteur repart de zero.
        $utilisateur->forceFill([
            'pin_essais' => 0,
            'pin_bloque_jusqu_a' => null,
        ])->save();

        return $this->reponseDeSession($utilisateur, $donnees['appareil'] ?? null);
    }

    /**
     * Cree le compte etudiant a partir d'une adresse et d'un PIN.
     *
     * L'adresse n'est pas verifiee : aucun code ne circule plus par email.
     * Elle sert d'identifiant, et le PIN de secret. Une adresse deja prise
     * est refusee — sans quoi n'importe qui ecraserait le PIN d'autrui en
     * se declarant a sa place.
     */
    public function inscriptionPin(InscriptionPinRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $email = mb_strtolower(trim($donnees['email']));

        // Les comptes supprimes sont compris dans la recherche : le
        // modele porte `SoftDeletes`, mais l'unicite de l'adresse est
        // posee en base sans egard pour `deleted_at`. Sans cela l'ancien
        // compte restait invisible ici et l'insertion echouait sur la
        // contrainte, renvoyant une erreur 500 a l'etudiant qui revient
        // apres avoir supprime son compte — un geste que l'App Store
        // impose pourtant d'offrir.
        $existant = User::withTrashed()->where('email', $email)->first();

        // Une adresse deja pourvue d'un PIN appartient a quelqu'un : la
        // laisser se reinscrire ecraserait le code d'autrui. Un compte
        // supprime fait exception : son ancien PIN ne protege plus rien,
        // et son proprietaire doit pouvoir revenir.
        if ($existant?->trashed()) {
            return $this->ouvrirLAccesTransport($existant, $donnees);
        }

        if ($existant && $existant->aUnPin()) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse a déjà un compte.'],
            ]);
        }

        // Un chauffeur ou un gestionnaire se connecte par mot de passe :
        // il n'a rien a faire sur le parcours d'inscription.
        if ($existant && $existant->role_bus !== null && $existant->role_bus !== RoleUtilisateur::Etudiant) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte se connecte avec son mot de passe.'],
            ]);
        }

        // Le transport n'est ouvert qu'aux etudiants. Un enseignant ou un
        // administratif d'Estuaire RH garde son compte tel quel : il n'a
        // pas a s'inscrire ici, et son compte ne doit surtout pas se voir
        // poser un PIN par quiconque connait son adresse.
        if ($existant && $existant->employee_type !== 'etudiant') {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse a déjà un compte.'],
            ]);
        }

        // L'etudiant deja inscrit a la scolarite ouvre son acces au
        // transport sur le compte qu'il a deja : son identite, son niveau
        // et sa specialite sont conserves, et il n'a donc plus qu'a
        // choisir sa ligne de ramassage. Sans cette reprise, l'adresse
        // etait refusee et l'etudiant se retrouvait sans aucun moyen
        // d'entrer, faute d'un PIN a saisir.
        if ($existant) {
            return $this->ouvrirLAccesTransport($existant, $donnees);
        }

        $utilisateur = DB::transaction(function () use ($email, $donnees) {
            $utilisateur = User::create([
                'email' => $email,
                'pin' => $donnees['pin'],
                'role_bus' => RoleUtilisateur::Etudiant,
                'actif_bus' => true,

                // L'etudiant du transport est aussi un etudiant de l'ecole :
                // `espace = mixte` lui ouvre les services d'Estuaire RH, et
                // `employee_type = etudiant` est la cle que lit l'emploi du
                // temps pour retrouver ses UE (UeScheduleApiController).
                //
                // Niveau et specialite restent a renseigner : sans eux
                // l'emploi du temps revient vide, ce que la completion de
                // profil corrige.
                'espace' => 'mixte',
                'employee_type' => 'etudiant',
            ]);

            // La fiche etudiant accompagne le compte des sa creation, meme
            // vide : les ecrans de suivi s'y rattachent.
            Etudiant::firstOrCreate(['user_id' => $utilisateur->id]);

            return $utilisateur;
        });

        return $this->reponseDeSession($utilisateur, $donnees['appareil'] ?? null);
    }

    /**
     * Ouvre l'espace transport sur un compte d'Estuaire RH existant.
     *
     * L'etudiant que la scolarite a deja enregistre garde son compte : on
     * lui pose le PIN qu'il vient de choisir et on bascule son espace en
     * `mixte`, sans toucher a son identite ni a sa scolarite. Il arrive
     * ainsi directement a la completion de profil, ou seule la ligne de
     * ramassage lui reste a renseigner.
     *
     * @param array<string, mixed> $donnees
     */
    private function ouvrirLAccesTransport(User $utilisateur, array $donnees): JsonResponse
    {
        DB::transaction(function () use ($utilisateur, $donnees) {
            // Un compte supprime qui reprend du service revient d'abord
            // parmi les vivants : sans cela il resterait invisible a
            // toutes les requetes, y compris a sa propre connexion.
            if ($utilisateur->trashed()) {
                $utilisateur->restore();
            }

            $utilisateur->forceFill([
                'pin' => $donnees['pin'],
                'role_bus' => RoleUtilisateur::Etudiant,
                'actif_bus' => true,
                'espace' => 'mixte',
            ])->save();

            Etudiant::firstOrCreate(['user_id' => $utilisateur->id]);
        });

        return $this->reponseDeSession($utilisateur, $donnees['appareil'] ?? null);
    }

    /** Token et profil, forme commune a la connexion et a l'inscription. */
    private function reponseDeSession(User $utilisateur, ?string $appareil): JsonResponse
    {
        $utilisateur->load('etudiant.lieuRamassage', 'chauffeur');

        return response()->json([
            'token' => $utilisateur->createToken($appareil ?? 'mobile')->plainTextToken,
            'profil_complet' => $this->profilEstComplet($utilisateur),
            'utilisateur' => new UserResource($utilisateur),
        ]);
    }

    /**
     * Change le PIN du compte connecte.
     *
     * Reserve a qui tient deja une session ouverte : l'etudiant qui a
     * oublie son PIN ne passe pas par ici, il n'a plus de token. Son cas
     * se traite a la regulation.
     */
    public function definirPin(DefinirPinRequest $request): JsonResponse
    {
        $utilisateur = $request->user();

        $utilisateur->forceFill([
            'pin' => $request->validated('pin'),
            'pin_essais' => 0,
            'pin_bloque_jusqu_a' => null,
        ])->save();

        return response()->json(['message' => 'Code enregistré.']);
    }

    /** Nombre d'essais avant fermeture de la saisie. */
    private const MAX_ESSAIS_PIN = 5;

    /** Duree de la fermeture, une fois les essais epuises. */
    private const BLOCAGE_PIN_MINUTES = 15;

    private function pinEstBloque(User $utilisateur): bool
    {
        return $utilisateur->pin_bloque_jusqu_a !== null
            && $utilisateur->pin_bloque_jusqu_a->isFuture();
    }

    /**
     * Compte un essai manque et ferme la saisie au bout du compte.
     */
    private function compterEssaiManque(User $utilisateur): void
    {
        $essais = $utilisateur->pin_essais + 1;

        if ($essais >= self::MAX_ESSAIS_PIN) {
            $utilisateur->forceFill([
                'pin_essais' => 0,
                'pin_bloque_jusqu_a' => now()->addMinutes(self::BLOCAGE_PIN_MINUTES),
            ])->save();

            return;
        }

        $utilisateur->forceFill(['pin_essais' => $essais])->save();
    }

    /**
     * Enregistre le profil complete par l'etudiant (3.1).
     *
     * Le matricule peut rester vide : l'etudiant qui ne le retrouve pas le
     * renseignera plus tard depuis son profil.
     */
    public function completerProfil(CompleterProfilRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $utilisateur = $request->user();

        DB::transaction(function () use ($donnees, $utilisateur) {
            $utilisateur->update([
                // La table fusionnee nomme l'identite a la maniere RH.
                'first_name' => $donnees['prenom'],
                'last_name' => $donnees['nom'],
                'telephone_bus' => $donnees['telephone'],

                // Scolarite : c'est le couple (niveau, specialite) que lit
                // l'emploi du temps d'Estuaire RH pour retrouver les UE de
                // l'etudiant. Laisses tels quels s'ils ne sont pas fournis,
                // pour ne pas effacer ce qu'un precedent envoi a pose.
                ...array_filter([
                    'niveau' => $donnees['niveau'] ?? null,
                    'specialite' => $donnees['specialite'] ?? null,
                ], fn ($valeur) => $valeur !== null),
            ]);

            $utilisateur->etudiant()->updateOrCreate(
                ['user_id' => $utilisateur->id],
                [
                    'matricule_insam' => $donnees['matricule_insam'] ?? null,
                    'lieu_ramassage_id' => $donnees['lieu_ramassage_id'],
                ],
            );
        });

        $utilisateur->refresh()->load('etudiant.lieuRamassage');

        return response()->json([
            'message' => 'Profil enregistré.',
            'profil_complet' => $this->profilEstComplet($utilisateur),
            'utilisateur' => new UserResource($utilisateur),
        ]);
    }

    /**
     * Changement du point de ramassage habituel.
     *
     * L'etudiant qui demenage corrige son arret sans repasser par la
     * completion de profil, qui exigerait de ressaisir toute son identite.
     */
    public function changerPointRamassage(ChangerPointRamassageRequest $request): JsonResponse
    {
        $utilisateur = $request->user();
        $etudiant = $utilisateur->etudiant
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil étudiant.');

        $etudiant->update([
            'lieu_ramassage_id' => $request->validated('lieu_ramassage_id'),
        ]);

        $utilisateur->refresh()->load('etudiant.lieuRamassage');

        return response()->json([
            'message' => 'Point de ramassage mis à jour.',
            // L'application relit ce drapeau a chaque reponse : l'omettre
            // renverrait l'etudiant vers la completion de profil.
            'profil_complet' => $this->profilEstComplet($utilisateur),
            'utilisateur' => new UserResource($utilisateur),
        ]);
    }

    /** Connexion par telephone + mot de passe, reservee aux chauffeurs et au back-office. */
    public function connexion(ConnexionRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $user = User::where('telephone_bus', $donnees['telephone'])->first();

        if (! $user || ! $user->aUnPin()) {
            throw ValidationException::withMessages([
                'telephone' => ['Identifiants incorrects.'],
            ]);
        }

        if ($this->pinEstBloque($user)) {
            throw ValidationException::withMessages([
                'pin' => ['Trop d’essais. Rapproche-toi de la régulation.'],
            ]);
        }

        if (! Hash::check($donnees['pin'], $user->pin)) {
            $this->compterEssaiManque($user);

            throw ValidationException::withMessages([
                'pin' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->actif_bus) {
            throw ValidationException::withMessages([
                'telephone' => ['Ce compte est désactivé.'],
            ]);
        }

        $user->forceFill([
            'pin_essais' => 0,
            'pin_bloque_jusqu_a' => null,
        ])->save();

        return $this->reponseDeSession($user, $donnees['appareil'] ?? null);
    }

    /** Profil de l'utilisateur connecte. */
    public function profil(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->load('etudiant.lieuRamassage', 'chauffeur');

        return response()->json([
            'profil_complet' => $this->profilEstComplet($utilisateur),
            'utilisateur' => new UserResource($utilisateur),
        ]);
    }

    /** Revoque le token courant. */
    public function deconnexion(Request $request, PresenceService $presences): JsonResponse
    {
        $utilisateur = $request->user();

        // Un chauffeur qui se deconnecte doit quitter la carte des
        // etudiants sur-le-champ : attendre l'expiration du delai de grace
        // laisserait un bus fantome stationne a sa derniere position.
        if ($chauffeur = $utilisateur->chauffeur) {
            $presences->quitter($chauffeur);
        }

        $utilisateur->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }

    /**
     * Supprime definitivement le compte et tout ce qui s'y rattache.
     *
     * Apple impose ce geste des lors qu'une application ouvre des comptes
     * (App Store Review Guidelines 5.1.1(v)). La suppression est reelle,
     * pas un `SoftDeletes` : la ligne quitte la table, et les cascades
     * declarees en base emportent l'etudiant, ses abonnements et ses
     * embarquements, ou le chauffeur et son historique.
     *
     * Un chauffeur dont le wallet n'est pas solde fait exception. Son
     * solde et ses retraits sont des ecritures qui engagent l'entreprise :
     * les effacer sur simple demande depuis le telephone ferait disparaitre
     * la preuve d'une dette. Le compte reste donc ouvert jusqu'a ce que la
     * regulation ait solde le compte.
     */
    public function supprimerCompte(Request $request, PresenceService $presences): JsonResponse
    {
        $utilisateur = $request->user();

        if ($chauffeur = $utilisateur->chauffeur) {
            // Lecture directe plutot que `WalletService::wallet()`, qui
            // creerait un wallet vide juste avant de le supprimer.
            $wallet = WalletChauffeur::where('chauffeur_id', $chauffeur->id)->first();

            $dette = $wallet
                && ($wallet->solde_fcfa > 0 || $wallet->solde_reserve_fcfa > 0);

            if ($dette) {
                return response()->json([
                    'message' => 'Votre cagnotte n’est pas soldée : '
                        .'rapprochez-vous de la régulation avant de supprimer '
                        .'votre compte.',
                ], 409);
            }

            // Le bus doit quitter la carte des etudiants sur-le-champ, sans
            // attendre l'expiration du delai de grace.
            $presences->quitter($chauffeur);
        }

        DB::transaction(function () use ($utilisateur) {
            // Coupe l'acces avant tout : les autres appareils de la meme
            // personne ne doivent plus rien pouvoir faire.
            $utilisateur->tokens()->delete();
            $utilisateur->appareils()->delete();

            // `SoftDeletes` est arme sur le modele : sans `forceDelete`, la
            // ligne resterait en base et l'adresse resterait prise.
            $utilisateur->forceDelete();
        });

        return response()->json(['message' => 'Compte supprimé.']);
    }

    /**
     * Un chauffeur est renseigne par l'administration : son profil est
     * complet par construction. Un etudiant doit avoir son identite, son
     * telephone et son point de ramassage.
     */
    private function profilEstComplet(User $utilisateur): bool
    {
        if ($utilisateur->role_bus !== RoleUtilisateur::Etudiant) {
            return true;
        }

        $etudiant = $utilisateur->etudiant;

        // La table fusionnee garde les noms d'Estuaire RH : `nom` et
        // `prenom` n'existent pas sur le modele, et les lire renverrait
        // null a tous les coups — le profil n'aurait jamais ete complet,
        // et l'application aurait renvoye l'etudiant sans fin vers le
        // formulaire qu'il venait de remplir.
        return filled($utilisateur->last_name)
            && filled($utilisateur->first_name)
            && filled($utilisateur->telephone_bus)
            && $etudiant?->lieu_ramassage_id !== null;
    }
}
