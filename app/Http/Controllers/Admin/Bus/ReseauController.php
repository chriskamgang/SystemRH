<?php

namespace App\Http\Controllers\Admin\Bus;

use App\Enums\SensParcours;
use App\Enums\TypeLieu;
use App\Http\Controllers\Controller;
use App\Models\EtapeParcours;
use App\Models\Lieu;
use App\Models\Ligne;
use App\Models\Parcours;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Le reseau : lignes, lieux et parcours.
 *
 * C'est ce catalogue que l'application mobile presente a l'etudiant au
 * moment de choisir son point de ramassage. Un lieu inactif disparait de
 * son ecran sans que son choix passe soit efface.
 */
class ReseauController extends Controller
{
    // --- Lignes -----------------------------------------------------------

    public function lignes(): View
    {
        return view('admin.bus.reseau.lignes', [
            'lignes' => Ligne::withCount(['parcours', 'affectations'])
                ->orderBy('code')
                ->paginate(20),
        ]);
    }

    public function enregistrerLigne(Request $requete): RedirectResponse
    {
        Ligne::create($requete->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('lignes', 'code')],
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'duree_trajet_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'tours_prevus_par_jour' => ['required', 'integer', 'min:1', 'max:20'],
        ]) + ['actif' => true]);

        return back()->with('success', 'Ligne créée.');
    }

    public function modifierLigne(Request $requete, Ligne $ligne): RedirectResponse
    {
        $ligne->update($requete->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('lignes', 'code')->ignore($ligne->id)],
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'duree_trajet_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'tours_prevus_par_jour' => ['required', 'integer', 'min:1', 'max:20'],
        ]) + ['actif' => $requete->boolean('actif')]);

        return back()->with('success', 'Ligne mise à jour.');
    }

    // --- Lieux ------------------------------------------------------------

    public function lieux(Request $requete): View
    {
        $lieux = Lieu::query()
            ->when($requete->filled('type'), fn ($q) => $q->where('type', $requete->type))
            ->when($requete->filled('recherche'), fn ($q) => $q->where('nom', 'like', '%'.$requete->recherche.'%'))
            ->withCount('etudiants')
            ->orderBy('type')
            ->orderBy('nom')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bus.reseau.lieux', [
            'lieux' => $lieux,
            'types' => TypeLieu::cases(),
        ]);
    }

    public function enregistrerLieu(Request $requete): RedirectResponse
    {
        Lieu::create($this->reglesLieu($requete) + ['actif' => true]);

        return back()->with('success', 'Lieu ajouté au réseau.');
    }

    public function modifierLieu(Request $requete, Lieu $lieu): RedirectResponse
    {
        $lieu->update($this->reglesLieu($requete) + ['actif' => $requete->boolean('actif')]);

        return back()->with('success', 'Lieu mis à jour.');
    }

    /** @return array<string, mixed> */
    private function reglesLieu(Request $requete): array
    {
        return $requete->validate([
            'nom' => ['required', 'string', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TypeLieu::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            // Le rayon decide de la distance a laquelle le bus previent les
            // etudiants de son approche : trop large, il alerte tout le
            // quartier ; trop etroit, l'alerte arrive apres le bus.
            'rayon_validation_metres' => ['required', 'integer', 'min:20', 'max:2000'],
        ]);
    }

    // --- Parcours ---------------------------------------------------------

    public function parcours(): View
    {
        return view('admin.bus.reseau.parcours', [
            'parcours' => Parcours::with(['ligne', 'etapes.lieu'])
                ->orderBy('ligne_id')
                ->paginate(15),
            'lignes' => Ligne::where('actif', true)->orderBy('code')->get(),
            'lieux' => Lieu::where('actif', true)->orderBy('nom')->get(),
            'sens' => SensParcours::cases(),
        ]);
    }

    public function enregistrerParcours(Request $requete): RedirectResponse
    {
        $donnees = $requete->validate([
            'ligne_id' => ['required', 'exists:lignes,id'],
            'libelle' => ['required', 'string', 'max:150'],
            'sens' => ['required', Rule::enum(SensParcours::class)],
            'heure_depart' => ['nullable', 'date_format:H:i'],
            'duree_reference_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'etapes' => ['required', 'array', 'min:2'],
            'etapes.*' => ['required', 'exists:lieux,id'],
        ]);

        // Une ligne ne porte qu'un parcours par sens : c'est une contrainte
        // d'unicite en base. Sans ce controle, la seconde tentative tombait
        // en erreur 500 au lieu de dire ce qui n'allait pas.
        $dejaPris = Parcours::where('ligne_id', $donnees['ligne_id'])
            ->where('sens', $donnees['sens'])
            ->exists();

        if ($dejaPris) {
            throw ValidationException::withMessages([
                'sens' => ['Cette ligne a déjà un parcours dans ce sens. Supprimez-le avant d’en créer un autre.'],
            ]);
        }

        DB::transaction(function () use ($donnees) {
            // `duree_reference_minutes` est NOT NULL avec un defaut en base :
            // lui passer explicitement null ecrase ce defaut et fait echouer
            // l'insertion. Le champ n'est donc transmis que s'il est rempli,
            // et la duree de la ligne sert de repli — c'est elle que le
            // parcours decline.
            $parcours = Parcours::create(array_filter([
                'ligne_id' => $donnees['ligne_id'],
                'libelle' => $donnees['libelle'],
                'sens' => $donnees['sens'],
                'heure_depart' => $donnees['heure_depart'] ?? null,
                'duree_reference_minutes' => $donnees['duree_reference_minutes']
                    ?? Ligne::whereKey($donnees['ligne_id'])->value('duree_trajet_minutes'),
                'actif' => true,
            ], fn ($valeur) => $valeur !== null));

            // L'ordre des etapes est celui de la saisie : c'est lui qui
            // dicte la sequence de pointage du chauffeur.
            $etapes = array_values($donnees['etapes']);
            $derniere = count($etapes) - 1;

            foreach ($etapes as $rang => $lieuId) {
                EtapeParcours::create([
                    'parcours_id' => $parcours->id,
                    'lieu_id' => $lieuId,
                    'ordre' => $rang + 1,
                    // Le terminus clot la sequence de pointage : c'est lui
                    // qui permet au chauffeur de fermer son tour.
                    'est_terminus' => $rang === $derniere,
                ]);
            }
        });

        return back()->with('success', 'Parcours créé.');
    }

    public function supprimerParcours(Parcours $parcours): RedirectResponse
    {
        $parcours->delete();

        return back()->with('success', 'Parcours supprimé.');
    }
}
