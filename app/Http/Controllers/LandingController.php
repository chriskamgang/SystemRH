<?php

namespace App\Http\Controllers;

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
}
