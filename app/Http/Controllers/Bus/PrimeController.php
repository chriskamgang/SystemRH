<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\PrimeResource;
use App\Services\PrimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cagnotte et historique transparent des primes chauffeur (section 3.3).
 */
class PrimeController extends Controller
{
    public function __construct(private readonly PrimeService $primes) {}

    /** Tableau de bord de la cagnotte du mois demande (par defaut le mois courant). */
    public function cagnotte(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $periode = $request->validate([
            'periode' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ])['periode'] ?? now()->format('Y-m');

        $recap = $this->primes->recapitulatif($chauffeur, $periode);

        return response()->json([
            'periode' => $recap['periode'],
            'tours_valides' => $recap['tours_valides'],
            'secours_realises' => $recap['secours_realises'],
            'jours_assiduite' => $recap['jours_assiduite'],
            'total_primes_fcfa' => $recap['total_primes_fcfa'],
            'total_penalites_fcfa' => $recap['total_penalites_fcfa'],
            'net_a_payer_fcfa' => $recap['net_a_payer_fcfa'],
            'detail' => PrimeResource::collection($recap['detail']),
        ]);
    }

    /** Historique pagine de toutes les primes du chauffeur. */
    public function historique(Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeur($request);

        $primes = $chauffeur->primes()
            ->latest('date_acquisition')
            ->paginate(30);

        return response()->json($primes);
    }

    private function chauffeur(Request $request)
    {
        return $request->user()->chauffeur
            ?? abort(403, 'Votre compte n’est pas rattaché à un profil chauffeur.');
    }
}
