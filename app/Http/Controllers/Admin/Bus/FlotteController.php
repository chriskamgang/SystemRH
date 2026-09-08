<?php

namespace App\Http\Controllers\Admin\Bus;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutBus;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Chauffeur;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Vehicules et conducteurs.
 *
 * Le chauffeur n'a pas de parcours d'inscription : son compte est cree
 * ici, avec le mot de passe que la regulation lui communique. C'est le
 * pendant de l'etudiant, qui lui s'inscrit seul depuis l'application.
 */
class FlotteController extends Controller
{
    // --- Vehicules --------------------------------------------------------

    public function bus(Request $requete): View
    {
        $bus = Bus::query()
            ->when($requete->filled('recherche'), fn ($q) => $q->where(
                fn ($r) => $r
                    ->where('immatriculation', 'like', '%'.$requete->recherche.'%')
                    ->orWhere('modele', 'like', '%'.$requete->recherche.'%'),
            ))
            ->when($requete->filled('statut'), fn ($q) => $q->where('statut', $requete->statut))
            ->withCount('affectations')
            ->orderBy('immatriculation')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bus.flotte.bus', [
            'bus' => $bus,
            'statuts' => StatutBus::cases(),
        ]);
    }

    public function enregistrerBus(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'immatriculation' => ['required', 'string', 'max:20', Rule::unique('bus', 'immatriculation')],
            'modele' => ['nullable', 'string', 'max:100'],
            'capacite' => ['required', 'integer', 'min:1', 'max:200'],
            'statut' => ['required', Rule::enum(StatutBus::class)],
        ]);

        Bus::create($donnees + ['actif' => true]);

        return back()->with('success', 'Bus ajouté à la flotte.');
    }

    public function modifierBus(Request $requete, Bus $bus): RedirectResponse
    {
        $donnees = $requete->validate([
            'immatriculation' => ['required', 'string', 'max:20', Rule::unique('bus', 'immatriculation')->ignore($bus->id)],
            'modele' => ['nullable', 'string', 'max:100'],
            'capacite' => ['required', 'integer', 'min:1', 'max:200'],
            'statut' => ['required', Rule::enum(StatutBus::class)],
            'actif' => ['nullable', 'boolean'],
        ]);

        $bus->update($donnees + ['actif' => $requete->boolean('actif')]);

        return back()->with('success', 'Bus mis à jour.');
    }

    public function supprimerBus(Bus $bus): RedirectResponse
    {
        // Un bus qui a servi porte un historique de tours : le retirer de
        // la circulation vaut mieux que d'effacer ce qu'il a fait. Le
        // modele porte `SoftDeletes`, la ligne reste donc consultable.
        $bus->delete();

        return back()->with('success', 'Bus retiré de la flotte.');
    }

    // --- Conducteurs ------------------------------------------------------

    public function chauffeurs(Request $requete): View
    {
        $chauffeurs = Chauffeur::query()
            ->with('user')
            ->when($requete->filled('recherche'), fn ($q) => $q->where(
                fn ($r) => $r
                    ->where('matricule', 'like', '%'.$requete->recherche.'%')
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('first_name', 'like', '%'.$requete->recherche.'%')
                        ->orWhere('last_name', 'like', '%'.$requete->recherche.'%')
                        ->orWhere('telephone_bus', 'like', '%'.$requete->recherche.'%')),
            ))
            ->withCount('affectations')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bus.flotte.chauffeurs', ['chauffeurs' => $chauffeurs]);
    }

    public function enregistrerChauffeur(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'string', 'max:20', Rule::unique('users', 'telephone_bus')],
            // Le chauffeur saisit quatre chiffres sur son telephone
            // (`ConnexionRequest`) : un secret d'une autre forme serait
            // impossible a taper, et le compte resterait inutilisable.
            'code' => ['required', 'string', 'regex:/^\d{4}$/'],
            'matricule' => ['required', 'string', 'max:50', Rule::unique('chauffeurs', 'matricule')],
            'numero_permis' => ['nullable', 'string', 'max:50'],
            'permis_expire_le' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($donnees) {
            // Le chauffeur se connecte par telephone et mot de passe : la
            // colonne `pin` porte ce secret, hachee comme celui de
            // l'etudiant — c'est elle que lit `AuthController::connexion`.
            $utilisateur = User::create([
                'first_name' => $donnees['prenom'],
                'last_name' => $donnees['nom'],
                'telephone_bus' => $donnees['telephone'],
                'pin' => Hash::make($donnees['code']),
                'role_bus' => RoleUtilisateur::Chauffeur,
                'actif_bus' => true,
                'espace' => 'bus',
            ]);

            Chauffeur::create([
                'user_id' => $utilisateur->id,
                'matricule' => $donnees['matricule'],
                'numero_permis' => $donnees['numero_permis'] ?? null,
                'permis_expire_le' => $donnees['permis_expire_le'] ?? null,
            ]);
        });

        return back()->with('success', 'Chauffeur enregistré. Communiquez-lui son code à 4 chiffres.');
    }

    public function modifierChauffeur(Request $requete, Chauffeur $chauffeur): RedirectResponse
    {
        $donnees = $requete->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'string', 'max:20', Rule::unique('users', 'telephone_bus')->ignore($chauffeur->user_id)],
            'code' => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'matricule' => ['required', 'string', 'max:50', Rule::unique('chauffeurs', 'matricule')->ignore($chauffeur->id)],
            'numero_permis' => ['nullable', 'string', 'max:50'],
            'permis_expire_le' => ['nullable', 'date'],
            'actif' => ['nullable', 'boolean'],
            'disponible_secours' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($donnees, $requete, $chauffeur) {
            $champs = [
                'first_name' => $donnees['prenom'],
                'last_name' => $donnees['nom'],
                'telephone_bus' => $donnees['telephone'],
                'actif_bus' => $requete->boolean('actif'),
            ];

            // Un champ laisse vide ne touche pas au code en place : le
            // remplacer par un hache de chaine vide fermerait le compte.
            if (filled($donnees['code'] ?? null)) {
                $champs['pin'] = Hash::make($donnees['code']);
            }

            $chauffeur->user->forceFill($champs)->save();

            $chauffeur->update([
                'matricule' => $donnees['matricule'],
                'numero_permis' => $donnees['numero_permis'] ?? null,
                'permis_expire_le' => $donnees['permis_expire_le'] ?? null,
                'disponible_secours' => $requete->boolean('disponible_secours'),
            ]);
        });

        return back()->with('success', 'Chauffeur mis à jour.');
    }
}
