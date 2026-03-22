<?php

namespace App\Http\Controllers;

use App\Models\IosBetaRequest;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Page d'accueil
     */
    public function index()
    {
        return view('landing.index');
    }

    /**
     * Page Fonctionnalités
     */
    public function features()
    {
        return view('landing.features');
    }

    /**
     * Page Avantages
     */
    public function advantages()
    {
        return view('landing.advantages');
    }

    /**
     * Page Tarifs
     */
    public function pricing()
    {
        return view('landing.pricing');
    }

    /**
     * Page Témoignages
     */
    public function testimonials()
    {
        return view('landing.testimonials');
    }

    /**
     * Page FAQ
     */
    public function faq()
    {
        return view('landing.faq');
    }

    /**
     * Page Contact/Téléchargement
     */
    public function download()
    {
        return view('landing.download');
    }

    /**
     * Inscription beta iOS
     */
    public function registerIosBeta(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'full_name' => 'nullable|string|max:255',
        ]);

        $existing = IosBetaRequest::where('email', $request->email)->first();

        if ($existing) {
            return back()->with('ios_beta_info', 'Cet email est déjà enregistré. Vous serez notifié dès que votre accès sera prêt.');
        }

        IosBetaRequest::create([
            'email' => $request->email,
            'full_name' => $request->full_name,
        ]);

        return back()->with('ios_beta_success', 'Merci ! Votre email a été enregistré. Vous recevrez une invitation TestFlight prochainement.');
    }
}
