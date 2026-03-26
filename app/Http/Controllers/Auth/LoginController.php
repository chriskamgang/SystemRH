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

            // Admin (role_id=1) a toujours accès
            if ($user->isAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }

            // Vérifier si l'utilisateur a la permission d'accéder au dashboard
            if ($user->hasPermission('access_dashboard')) {
                return redirect()->intended('/admin/dashboard');
            }

            // Si pas de permission dashboard, déconnecter
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Vous n\'avez pas les droits d\'accès au panneau d\'administration.'],
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
