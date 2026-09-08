<?php

namespace App\Http\Controllers\Admin\Bus;

use App\Enums\StatutAbonnement;
use App\Enums\StatutBus;
use App\Enums\StatutPanne;
use App\Enums\StatutTournee;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Affectation;
use App\Models\Bus;
use App\Models\Chauffeur;
use App\Models\Etudiant;
use App\Models\Ligne;
use App\Models\Panne;
use App\Models\Tournee;
use App\Services\PresenceService;
use Illuminate\Contracts\View\View;

/**
 * Tableau de bord de la regulation du transport.
 *
 * Donne en une page l'etat du service au moment ou on l'ouvre : ce qui
 * roule, ce qui est immobilise, et ce qui reste a faire dans la journee.
 */
class TableauBordController extends Controller
{
    public function __construct(private readonly PresenceService $presences)
    {
    }

    public function index(): View
    {
        $aujourdhui = today();

        // Les bus en ligne viennent du service de presence et non d'un
        // comptage en base : c'est le battement du chauffeur qui fait foi,
        // un bus dont l'application s'est fermee n'etant plus joignable.
        $enLigne = $this->presences->busEnLigne();

        $affectations = Affectation::whereDate('date_service', $aujourdhui)
            ->with(['bus', 'chauffeur.user', 'ligne'])
            ->get();

        $tourneesDuJour = Tournee::whereHas(
            'affectation',
            fn ($q) => $q->whereDate('date_service', $aujourdhui),
        )->get();

        return view('admin.bus.tableau-bord', [
            'busEnLigne' => $enLigne,
            'nbBusEnLigne' => $enLigne->count(),

            'nbBus' => Bus::where('actif', true)->count(),
            'nbBusPanne' => Bus::where('statut', StatutBus::EnPanne)->count(),
            'nbChauffeurs' => Chauffeur::count(),
            'nbLignes' => Ligne::where('actif', true)->count(),
            'nbEtudiants' => Etudiant::count(),

            'affectations' => $affectations,
            'nbAffectations' => $affectations->count(),

            'toursPrevus' => (int) $affectations->sum('tours_prevus'),
            'toursTermines' => $tourneesDuJour
                ->where('statut', StatutTournee::Termine)
                ->count(),
            'toursEnCours' => $tourneesDuJour
                ->whereIn('statut', [StatutTournee::Embarquement, StatutTournee::EnTransit])
                ->count(),

            // Un ecart d'effectif signale un tour ou le compte des scans ne
            // tombe pas juste : c'est ce que la regulation vient verifier.
            'anomalies' => $tourneesDuJour->where('anomalie_duree', true)->count(),

            'pannesOuvertes' => Panne::whereIn('statut', [
                StatutPanne::Declaree,
                StatutPanne::PriseEnCharge,
            ])->with(['bus', 'chauffeur.user'])->latest()->take(5)->get(),

            'abonnesActifs' => Abonnement::where('statut', StatutAbonnement::Actif)
                ->whereDate('date_fin', '>=', $aujourdhui)
                ->count(),

            'recetteDuMois' => (int) Abonnement::where('statut', StatutAbonnement::Actif)
                ->whereMonth('paye_le', now()->month)
                ->whereYear('paye_le', now()->year)
                ->sum('montant_paye_fcfa'),

            'derniersTours' => Tournee::with(['affectation.ligne', 'affectation.chauffeur.user', 'affectation.bus'])
                ->whereNotNull('termine_le')
                ->latest('termine_le')
                ->take(8)
                ->get(),
        ]);
    }
}
