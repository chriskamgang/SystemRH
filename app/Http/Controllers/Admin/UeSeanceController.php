<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UniteEnseignement;
use App\Models\UeSeance;
use App\Models\UeValidation;
use App\Models\User;
use Illuminate\Http\Request;

class UeSeanceController extends Controller
{
    // Voir les séances d'une UE + les validations étudiants
    public function show(UniteEnseignement $ue)
    {
        $ue->load(['seances.validations.user', 'enseignant']);

        // Trouver les étudiants concernés par cette UE (même spécialité + niveau)
        $studentsQuery = User::where('role', 'employee')
            ->where('employee_type', 'permanent')
            ->where('niveau', $ue->niveau);

        if ($ue->type_ue === 'tronc_commun' && $ue->groupes) {
            $studentsQuery->whereIn('specialite', $ue->groupes);
        } elseif ($ue->specialite) {
            $studentsQuery->where('specialite', $ue->specialite);
        }

        $students = $studentsQuery->orderBy('last_name')->get();

        return view('admin.ue-seances.show', compact('ue', 'students'));
    }

    // Formulaire pour ajouter/modifier les séances d'une UE
    public function edit(UniteEnseignement $ue)
    {
        $ue->load('seances');
        return view('admin.ue-seances.edit', compact('ue'));
    }

    // Sauvegarder les séances
    public function update(Request $request, UniteEnseignement $ue)
    {
        $request->validate([
            'seances' => 'required|array|min:1',
            'seances.*.titre' => 'required|string|max:255',
            'seances.*.objectif' => 'required|string',
        ]);

        // Supprimer les anciennes séances et recréer
        $ue->seances()->delete();

        foreach ($request->seances as $index => $seanceData) {
            if (!empty($seanceData['titre']) && !empty($seanceData['objectif'])) {
                $ue->seances()->create([
                    'numero' => $index + 1,
                    'titre' => $seanceData['titre'],
                    'objectif' => $seanceData['objectif'],
                ]);
            }
        }

        return redirect()->route('admin.ue-seances.show', $ue)
            ->with('success', count($request->seances) . ' séance(s) enregistrée(s).');
    }

    // Tableau de bord des compétences TP (vue globale)
    public function dashboard(Request $request)
    {
        $query = UniteEnseignement::whereHas('seances')
            ->with(['seances', 'enseignant'])
            ->withCount('seances');

        if ($request->filled('specialite')) {
            $query->pourSpecialite($request->specialite);
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        $ues = $query->orderBy('code_ue')->paginate(20);

        // Stats globales
        $totalSeances = UeSeance::count();
        $totalValidations = UeValidation::where('tp_effectue', true)->count();
        $totalObjectifs = UeValidation::where('objectif_atteint', true)->count();

        // Liste des spécialités et niveaux pour les filtres
        $specialites = UniteEnseignement::whereHas('seances')
            ->distinct()->pluck('specialite')->filter()->sort()->values();
        $niveaux = UniteEnseignement::whereHas('seances')
            ->distinct()->pluck('niveau')->filter()->sort()->values();

        return view('admin.ue-seances.dashboard', compact(
            'ues', 'totalSeances', 'totalValidations', 'totalObjectifs',
            'specialites', 'niveaux'
        ));
    }

    // Carnet de compétences d'un étudiant
    public function carnetEtudiant(User $student)
    {
        // Toutes les UEs avec séances qui concernent cet étudiant
        $ues = UniteEnseignement::whereHas('seances')
            ->where('niveau', $student->niveau)
            ->pourSpecialite($student->specialite ?? '')
            ->with(['seances' => function ($q) use ($student) {
                $q->with(['validations' => function ($q2) use ($student) {
                    $q2->where('user_id', $student->id);
                }]);
            }])
            ->orderBy('code_ue')
            ->get();

        return view('admin.ue-seances.carnet', compact('student', 'ues'));
    }
}
