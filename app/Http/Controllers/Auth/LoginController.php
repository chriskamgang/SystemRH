<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        // Tenter la connexion
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

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
                return redirect()->intended('/admin/dashboard');
            }

            // Verifier can_access_admin ET permission dashboard
            if ($user->can_access_admin && $user->hasPermission('access_dashboard')) {
                return redirect()->intended('/admin/dashboard');
            }

            // Si pas de permission dashboard, deconnecter
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Vous n\'avez pas les droits d\'acces au panneau d\'administration.'],
            ]);
        }

        // Échec de connexion
        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis ne correspondent pas à nos enregistrements.'],
        ]);
    }

    /**
     * Gérer la déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
