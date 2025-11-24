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

            // Vérifier que l'utilisateur est admin (role_id = 1)
            if (Auth::user()->role_id == 1) {
                return redirect()->intended('/admin/dashboard');
            }

            // Si pas admin, déconnecter
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Accès réservé aux administrateurs.'],
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
