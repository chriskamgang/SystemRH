<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gérer la connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            // Espace d'arrivee choisi sur les deux cartes de l'accueil.
            // Il ne conditionne aucun droit : la meme connexion ouvre les
            // deux univers, et le menu bascule de l'un a l'autre.
            'espace' => 'nullable|in:bus,rh',
        ]);

        // Chercher l'utilisateur SANS le scope company (pas de session au login)
        $user = User::withoutGlobalScopes()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis ne correspondent pas a nos enregistrements.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est desactive.'],
            ]);
        }

        // Connecter manuellement (bypass le scope)
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        // L'espace retenu est garde en session : la barre laterale s'y
        // accorde a chaque page, et le bouton de bascule le remplace.
        session(['espace_admin' => $request->input('espace', 'rh')]);

        // Super admin a toujours acces
        if ($user->isSuperAdmin()) {
            return redirect()->intended('/admin/companies');
        }

        // Verifier que l'entreprise est active
        if ($user->company_id) {
            $company = \App\Models\Company::find($user->company_id);
            if ($company && !$company->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Votre entreprise est actuellement desactivee. Contactez l\'administrateur.'],
                ]);
            }
            // Definir le company_id en session
            session(['current_company_id' => $user->company_id]);
        }

        // Admin de l'entreprise a toujours acces
        if ($user->isAdmin()) {
            return $this->versAccueil($request);
        }

        // Verifier can_access_admin ET permission dashboard
        if ($user->can_access_admin && $user->hasPermission('access_dashboard')) {
            return $this->versAccueil($request);
        }

        // Si pas de permission dashboard, deconnecter
        Auth::logout();
        throw ValidationException::withMessages([
            'email' => ['Vous n\'avez pas les droits d\'acces au panneau d\'administration.'],
        ]);
    }

    /**
     * Redirige vers l'espace retenu sur l'ecran de connexion.
     *
     * Un espace explicitement choisi l'emporte sur l'URL memorisee par
     * `intended` : celle-ci vient d'une visite precedente, et renverrait
     * vers l'autre univers que celui qu'on vient de designer.
     */
    private function versAccueil(Request $request): RedirectResponse
    {
        if ($request->input('espace') === 'bus') {
            return redirect('/admin/bus');
        }

        return redirect()->intended('/admin/dashboard');
    }

    /**
     * Gérer la déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // L'espace retenu appartient a la session qui se ferme : le laisser
        // ferait arriver le suivant dans l'univers de son predecesseur.
        $request->session()->forget('espace_admin');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
