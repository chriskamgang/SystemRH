<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UniteEnseignement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UniteEnseignementController extends Controller
{
    /**
     * Liste toutes les UE
     */
    public function index(Request $request)
    {
        $query = UniteEnseignement::with(['vacataire', 'creator', 'activator']);

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par vacataire
        if ($request->has('vacataire_id')) {
            $query->where('vacataire_id', $request->vacataire_id);
        }

        // Filtre par année académique
        if ($request->has('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $unites = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.unites-enseignement.index', compact('unites'));
    }

    /**
     * Liste des UE d'un vacataire spécifique
     */
    public function vacataireUnites($vacataireId)
    {
        $vacataire = User::where('employee_type', 'enseignant_vacataire')
            ->findOrFail($vacataireId);

        $unitesActivees = UniteEnseignement::where('vacataire_id', $vacataireId)
            ->where('statut', 'activee')
            ->with(['presenceIncidents'])
            ->get();

        $unitesNonActivees = UniteEnseignement::where('vacataire_id', $vacataireId)
            ->where('statut', 'non_activee')
            ->get();

        // Calcul des totaux
        $totalHeuresEffectuees = 0;
        $totalMontantPaye = 0;

        foreach ($unitesActivees as $ue) {
            $totalHeuresEffectuees += $ue->heures_effectuees;
            $totalMontantPaye += $ue->montant_paye;
        }

        return view('admin.vacataires.unites', compact(
            'vacataire',
            'unitesActivees',
            'unitesNonActivees',
            'totalHeuresEffectuees',
            'totalMontantPaye'
        ));
    }

    /**
     * Formulaire de création d'UE
     */
    public function create(Request $request)
    {
        $vacataireId = $request->get('vacataire_id');
        $vacataires = User::where('employee_type', 'enseignant_vacataire')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('admin.unites-enseignement.create', compact('vacataires', 'vacataireId'));
    }

    /**
     * Enregistrer une nouvelle UE
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vacataire_id' => 'required|exists:users,id',
            'code_ue' => 'nullable|string|max:50',
            'nom_matiere' => 'required|string|max:255',
            'volume_horaire_total' => 'required|numeric|min:0.5|max:999',
            'annee_academique' => 'nullable|string|max:20',
            'semestre' => 'nullable|integer|in:1,2',
            'activer_immediatement' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except('activer_immediatement');
        $data['created_by'] = Auth::id();
        $data['date_attribution'] = now();

        // Si activation immédiate demandée
        if ($request->boolean('activer_immediatement')) {
            $data['statut'] = 'activee';
            $data['date_activation'] = now();
            $data['activated_by'] = Auth::id();
        }

        $ue = UniteEnseignement::create($data);

        return redirect()
            ->route('admin.vacataires.unites', $ue->vacataire_id)
            ->with('success', 'Unité d\'enseignement attribuée avec succès');
    }

    /**
     * Formulaire d'édition
     */
    public function edit($id)
    {
        $ue = UniteEnseignement::with('vacataire')->findOrFail($id);

        return view('admin.unites-enseignement.edit', compact('ue'));
    }

    /**
     * Mettre à jour une UE
     */
    public function update(Request $request, $id)
    {
        $ue = UniteEnseignement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code_ue' => 'nullable|string|max:50',
            'nom_matiere' => 'required|string|max:255',
            'volume_horaire_total' => 'required|numeric|min:0.5|max:999',
            'annee_academique' => 'nullable|string|max:20',
            'semestre' => 'nullable|integer|in:1,2',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ue->update($request->only([
            'code_ue',
            'nom_matiere',
            'volume_horaire_total',
            'annee_academique',
            'semestre'
        ]));

        return redirect()
            ->route('admin.vacataires.unites', $ue->vacataire_id)
            ->with('success', 'Unité d\'enseignement modifiée avec succès');
    }

    /**
     * Activer une UE
     */
    public function activer($id)
    {
        $ue = UniteEnseignement::findOrFail($id);

        if ($ue->isActivee()) {
            return redirect()->back()
                ->with('warning', 'Cette UE est déjà activée');
        }

        $ue->activer(Auth::id());

        return redirect()->back()
            ->with('success', 'UE activée avec succès. Le vacataire peut maintenant pointer pour cette matière.');
    }

    /**
     * Désactiver une UE
     */
    public function desactiver($id)
    {
        $ue = UniteEnseignement::findOrFail($id);

        if ($ue->isNonActivee()) {
            return redirect()->back()
                ->with('warning', 'Cette UE est déjà désactivée');
        }

        // Vérifier s'il y a déjà des heures pointées
        if ($ue->heures_effectuees > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de désactiver une UE avec des heures déjà pointées');
        }

        $ue->desactiver();

        return redirect()->back()
            ->with('success', 'UE désactivée avec succès');
    }

    /**
     * Supprimer une UE
     */
    public function destroy($id)
    {
        $ue = UniteEnseignement::findOrFail($id);

        // Vérifier s'il y a des heures pointées
        if ($ue->heures_effectuees > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer une UE avec des heures déjà pointées');
        }

        $vacataireId = $ue->vacataire_id;
        $ue->delete();

        return redirect()
            ->route('admin.vacataires.unites', $vacataireId)
            ->with('success', 'UE supprimée avec succès');
    }

    /**
     * Détails d'une UE avec historique des pointages
     */
    public function show($id)
    {
        $ue = UniteEnseignement::with([
            'vacataire',
            'presenceIncidents' => function ($query) {
                $query->orderBy('incident_date', 'desc');
            },
            'presenceIncidents.campus'
        ])->findOrFail($id);

        return view('admin.unites-enseignement.show', compact('ue'));
    }
}
