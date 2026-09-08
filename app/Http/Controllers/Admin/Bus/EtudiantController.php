<?php

namespace App\Http\Controllers\Admin\Bus;

use App\Enums\StatutAbonnement;
use App\Enums\TypeLieu;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Etudiant;
use App\Models\Lieu;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Les etudiants du transport et leurs abonnements.
 *
 * Ces comptes ne se creent pas ici : l'etudiant s'inscrit lui-meme depuis
 * l'application. La regulation les consulte, corrige un point de
 * ramassage, et suit les abonnements.
 */
class EtudiantController extends Controller
{
    public function index(Request $requete): View
    {
        $etudiants = Etudiant::query()
            ->with(['user', 'lieuRamassage', 'ligne'])
            ->when($requete->filled('recherche'), fn ($q) => $q->where(
                fn ($r) => $r
                    ->where('matricule_insam', 'like', '%'.$requete->recherche.'%')
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('first_name', 'like', '%'.$requete->recherche.'%')
                        ->orWhere('last_name', 'like', '%'.$requete->recherche.'%')
                        ->orWhere('email', 'like', '%'.$requete->recherche.'%')),
            ))
            ->when($requete->filled('lieu'), fn ($q) => $q->where('lieu_ramassage_id', $requete->lieu))
            // Un profil sans point de ramassage n'est pas alle au bout de
            // son inscription : la regulation peut vouloir les relancer.
            ->when($requete->boolean('sans_lieu'), fn ($q) => $q->whereNull('lieu_ramassage_id'))
            ->paginate(25)
            ->withQueryString();

        // L'abonnement en cours est charge a part : la relation ne sait pas
        // filtrer sur « le plus recent encore valide ».
        $abonnements = Abonnement::whereIn('etudiant_id', $etudiants->pluck('id'))
            ->where('statut', StatutAbonnement::Actif)
            ->whereDate('date_fin', '>=', today())
            ->get()
            ->keyBy('etudiant_id');

        return view('admin.bus.etudiants.index', [
            'etudiants' => $etudiants,
            'abonnements' => $abonnements,
            'lieux' => Lieu::where('type', TypeLieu::Ramassage)
                ->where('actif', true)
                ->orderBy('nom')
                ->get(),
        ]);
    }

    /**
     * Corrige le point de ramassage depuis la regulation.
     *
     * L'etudiant le fait lui-meme depuis l'application ; ce geste sert
     * quand il appelle parce qu'il n'y arrive pas.
     */
    public function changerLieu(Request $requete, Etudiant $etudiant): RedirectResponse
    {
        $donnees = $requete->validate([
            'lieu_ramassage_id' => ['required', 'exists:lieux,id'],
        ]);

        $etudiant->update($donnees);

        return back()->with('success', 'Point de ramassage mis à jour.');
    }

    public function abonnements(Request $requete): View
    {
        $abonnements = Abonnement::query()
            ->with(['etudiant.user', 'tarif'])
            ->when($requete->filled('statut'), fn ($q) => $q->where('statut', $requete->statut))
            ->when($requete->boolean('expires'), fn ($q) => $q->whereDate('date_fin', '<', today()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.bus.etudiants.abonnements', [
            'abonnements' => $abonnements,
            'statuts' => StatutAbonnement::cases(),
            'recetteMois' => (int) Abonnement::whereMonth('paye_le', now()->month)
                ->whereYear('paye_le', now()->year)
                ->sum('montant_paye_fcfa'),
        ]);
    }
}
