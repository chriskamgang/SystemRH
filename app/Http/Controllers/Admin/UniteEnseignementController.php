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

        // Filtre par enseignant (vacataire ou semi-permanent)
        if ($request->has('vacataire_id') || $request->has('enseignant_id')) {
            $enseignantId = $request->get('enseignant_id', $request->get('vacataire_id'));
            $query->where('enseignant_id', $enseignantId);
        }

        // Filtre par année académique
        if ($request->has('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $unites = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.unites-enseignement.index', compact('unites'));
    }

    /**
     * Liste des UE d'un enseignant spécifique (vacataire ou semi-permanent)
     */
    public function vacataireUnites($vacataireId)
    {
        $vacataire = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent'])
            ->findOrFail($vacataireId);

        $unitesActivees = UniteEnseignement::where('enseignant_id', $vacataireId)
            ->where('statut', 'activee')
            ->with(['presenceIncidents'])
            ->get();

        $unitesNonActivees = UniteEnseignement::where('enseignant_id', $vacataireId)
            ->where('statut', 'non_activee')
            ->get();

        // Calcul des totaux (utilise les heures validées manuellement)
        $totalHeuresEffectuees = 0;
        $totalMontantPaye = 0;

        foreach ($unitesActivees as $ue) {
            // Utiliser les heures validées manuellement au lieu des heures calculées automatiquement
            $totalHeuresEffectuees += $ue->heures_effectuees_validees;
            $totalMontantPaye += $ue->total_paye;
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

        // Récupérer les vacataires ET les semi-permanents
        $vacataires = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent'])
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
            'semestre' => 'nullable|integer|between:1,9',
            'activer_immediatement' => 'boolean',
        ]);

        // Valider que l'utilisateur est bien un enseignant (vacataire ou semi-permanent)
        $enseignant = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent'])
            ->find($request->vacataire_id);

        if (!$enseignant) {
            return redirect()->back()
                ->withErrors(['vacataire_id' => 'Cet employé doit être un enseignant (vacataire ou semi-permanent)'])
                ->withInput();
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except('activer_immediatement');
        // Renommer vacataire_id en enseignant_id pour la base de données
        $data['enseignant_id'] = $data['vacataire_id'];
        unset($data['vacataire_id']);
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
            ->route('admin.vacataires.unites', $ue->enseignant_id)
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
            'semestre' => 'nullable|integer|between:1,9',
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
            ->route('admin.vacataires.unites', $ue->enseignant_id)
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
            ->with('success', 'UE activée avec succès. L\'enseignant peut maintenant pointer pour cette matière.');
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

        $enseignantId = $ue->enseignant_id;
        $ue->delete();

        return redirect()
            ->route('admin.vacataires.unites', $enseignantId)
            ->with('success', 'UE supprimée avec succès');
    }

    /**
     * Détails d'une UE avec historique des pointages
     */
    public function show($id)
    {
        $ue = UniteEnseignement::with([
            'enseignant',
            'creator',
            'activator',
            'paymentDetails.payment',
            'presenceIncidents' => function ($query) {
                $query->orderBy('incident_date', 'desc');
            },
            'presenceIncidents.campus'
        ])->findOrFail($id);

        return view('admin.unites-enseignement.show', compact('ue'));
    }

    /**
     * ===========================================================
     * NOUVELLE FONCTIONNALITÉ : GESTION CENTRALISÉE DES UE
     * ===========================================================
     */

    /**
     * Liste des UE dans la bibliothèque (non attribuées ou toutes)
     */
    public function catalog(Request $request)
    {
        $query = UniteEnseignement::query();

        // Filtre par spécialité
        if ($request->has('specialite') && $request->specialite) {
            $query->where('specialite', $request->specialite);
        }

        // Filtre par niveau
        if ($request->has('niveau') && $request->niveau) {
            $query->where('niveau', $request->niveau);
        }

        // Filtre par année académique
        if ($request->has('annee_academique') && $request->annee_academique) {
            $query->where('annee_academique', $request->annee_academique);
        }

        // Filtre par semestre
        if ($request->has('semestre') && $request->semestre !== '') {
            $query->where('semestre', $request->semestre);
        }

        // Recherche par code ou nom
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code_ue', 'like', "%{$search}%")
                  ->orWhere('nom_matiere', 'like', "%{$search}%");
            });
        }

        $unites = $query->with(['enseignant'])
            ->orderBy('code_ue')
            ->paginate(50); // Augmenté à 50 pour meilleure UX

        // Récupérer les valeurs uniques pour les filtres (OPTIMISÉ avec whereNotNull)
        $specialites = UniteEnseignement::select('specialite')
            ->whereNotNull('specialite')
            ->where('specialite', '!=', '')
            ->distinct()
            ->limit(100)
            ->pluck('specialite');

        $niveaux = UniteEnseignement::select('niveau')
            ->whereNotNull('niveau')
            ->where('niveau', '!=', '')
            ->distinct()
            ->limit(100)
            ->pluck('niveau');

        $anneesAcademiques = UniteEnseignement::select('annee_academique')
            ->whereNotNull('annee_academique')
            ->where('annee_academique', '!=', '')
            ->distinct()
            ->limit(20)
            ->pluck('annee_academique');

        return view('admin.unites-enseignement.catalog', compact(
            'unites',
            'specialites',
            'niveaux',
            'anneesAcademiques'
        ));
    }

    /**
     * Formulaire de création d'UE (sans enseignant)
     */
    public function createStandalone()
    {
        return view('admin.unites-enseignement.create-standalone');
    }

    /**
     * Enregistrer une UE dans la bibliothèque (sans enseignant)
     */
    public function storeStandalone(Request $request)
    {
        $validated = $request->validate([
            'code_ue' => 'required|string|max:50|unique:unites_enseignement,code_ue',
            'nom_matiere' => 'required|string|max:255',
            'volume_horaire_total' => 'required|numeric|min:0.5|max:999',
            'annee_academique' => 'required|string|max:20',
            'semestre' => 'nullable|integer|in:1,2',
            'specialite' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:255',
        ]);

        $validated['statut'] = 'non_activee';
        $validated['created_by'] = Auth::id();

        UniteEnseignement::create($validated);

        return redirect()
            ->route('admin.unites-enseignement.catalog')
            ->with('success', 'UE créée avec succès dans la bibliothèque');
    }

    /**
     * API : Rechercher une UE par code (pour l'auto-complétion)
     */
    public function searchByCode(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Code requis']);
        }

        $ue = UniteEnseignement::where('code_ue', $code)
            ->whereNull('enseignant_id')
            ->first();

        if (!$ue) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune UE trouvée avec ce code ou UE déjà attribuée'
            ]);
        }

        return response()->json([
            'success' => true,
            'ue' => [
                'id' => $ue->id,
                'code_ue' => $ue->code_ue,
                'nom_matiere' => $ue->nom_matiere,
                'volume_horaire_total' => $ue->volume_horaire_total,
                'annee_academique' => $ue->annee_academique,
                'semestre' => $ue->semestre,
                'specialite' => $ue->specialite,
                'niveau' => $ue->niveau,
            ]
        ]);
    }

    /**
     * API : Rechercher plusieurs UE par codes (pour l'attribution multiple)
     */
    public function searchMultipleCodes(Request $request)
    {
        $codes = $request->get('codes'); // Array de codes

        if (!$codes || !is_array($codes)) {
            return response()->json(['success' => false, 'message' => 'Codes requis']);
        }

        // Rechercher toutes les UE avec ces codes (même celles déjà attribuées)
        $ues = UniteEnseignement::whereIn('code_ue', $codes)
            ->with('enseignant')
            ->get();

        $found = $ues->pluck('code_ue')->unique()->toArray();
        $notFound = array_diff($codes, $found);

        return response()->json([
            'success' => true,
            'ues' => $ues->map(function($ue) {
                $isAssigned = !is_null($ue->enseignant_id);
                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'volume_horaire_total' => $ue->volume_horaire_total,
                    'annee_academique' => $ue->annee_academique,
                    'semestre' => $ue->semestre,
                    'specialite' => $ue->specialite,
                    'niveau' => $ue->niveau,
                    'is_assigned' => $isAssigned,
                    'enseignant' => $isAssigned && $ue->enseignant ? [
                        'id' => $ue->enseignant->id,
                        'full_name' => $ue->enseignant->full_name,
                    ] : null,
                ];
            }),
            'not_found' => $notFound,
        ]);
    }

    /**
     * Formulaire d'attribution rapide (par code UE)
     */
    public function assignForm()
    {
        // Récupérer les vacataires ET les semi-permanents
        $enseignants = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent'])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('admin.unites-enseignement.assign', compact('enseignants'));
    }

    /**
     * Attribuer une ou plusieurs UE à un enseignant (par IDs sélectionnés)
     */
    public function assignToTeacher(Request $request)
    {
        $validated = $request->validate([
            'ue_ids' => 'required|array|min:1',
            'ue_ids.*' => 'required|integer|exists:unites_enseignement,id',
            'enseignant_id' => 'required|exists:users,id',
            'activer_immediatement' => 'boolean',
        ], [
            'ue_ids.required' => 'Veuillez sélectionner au moins une UE à attribuer',
            'ue_ids.min' => 'Veuillez sélectionner au moins une UE à attribuer',
        ]);

        // Valider que c'est bien un enseignant
        $enseignant = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent'])
            ->find($validated['enseignant_id']);

        if (!$enseignant) {
            return redirect()->back()
                ->withErrors(['enseignant_id' => 'Cet employé doit être un enseignant'])
                ->withInput();
        }

        // Récupérer toutes les UE sélectionnées qui ne sont PAS encore attribuées
        $ues = UniteEnseignement::whereIn('id', $validated['ue_ids'])
            ->whereNull('enseignant_id')
            ->get();

        if ($ues->isEmpty()) {
            return redirect()->back()
                ->withErrors(['ue_ids' => 'Aucune UE valide sélectionnée ou toutes sont déjà attribuées'])
                ->withInput();
        }

        // Vérifier s'il y a des UE déjà attribuées dans la sélection
        $alreadyAssignedIds = UniteEnseignement::whereIn('id', $validated['ue_ids'])
            ->whereNotNull('enseignant_id')
            ->pluck('id')
            ->toArray();

        // Attribuer toutes les UE disponibles
        $count = 0;
        foreach ($ues as $ue) {
            $ue->enseignant_id = $validated['enseignant_id'];
            $ue->date_attribution = now();

            if ($request->boolean('activer_immediatement')) {
                $ue->statut = 'activee';
                $ue->date_activation = now();
                $ue->activated_by = Auth::id();
            }

            $ue->save();
            $count++;
        }

        $message = "{$count} UE(s) attribuée(s) à {$enseignant->full_name} avec succès";

        if (!empty($alreadyAssignedIds)) {
            $message .= " | " . count($alreadyAssignedIds) . " UE(s) ignorée(s) (déjà attribuées)";
        }

        return redirect()
            ->route('admin.unites-enseignement.catalog')
            ->with('success', $message);
    }

    /**
     * Afficher le formulaire d'import
     */
    public function importForm()
    {
        return view('admin.unites-enseignement.import');
    }

    /**
     * Importer des UE depuis Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV',
            'file.max' => 'La taille du fichier ne doit pas dépasser 10 Mo',
        ]);

        try {
            $import = new \App\Imports\UnitesEnseignementImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $imported = $import->getRowCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();
            $totalErrors = count($import->failures());

            // LOG DE DEBUG
            \Log::info("Import UE terminé", [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $totalErrors,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);

            // Message de succès avec détails
            $message = "Import terminé : {$imported} UE importée(s)";

            if ($skipped > 0) {
                $message .= ", {$skipped} UE ignorée(s) (codes déjà existants)";
            }

            if ($totalErrors > 0) {
                $message .= ". Attention : {$totalErrors} erreur(s) détectée(s)";
            }

            // Si beaucoup d'erreurs, suggérer de vérifier le fichier
            if ($totalErrors > 100) {
                $message .= " (fichier probablement mal formaté, vérifiez les colonnes)";
            }

            // CAS SPÉCIAL : Aucune UE importée et beaucoup ignorées = tout existe déjà
            if ($imported == 0 && $skipped > 10) {
                return redirect()
                    ->route('admin.unites-enseignement.catalog')
                    ->with('info', "❌ Aucune nouvelle UE importée : Les {$skipped} UE du fichier existent déjà dans la base de données. Vérifiez que vous n'importez pas un fichier déjà traité.");
            }

            // Si des erreurs, les afficher (LIMITÉ à 100 erreurs maximum)
            if (count($errors) > 0) {
                return redirect()
                    ->route('admin.unites-enseignement.catalog')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            // Import réussi
            $statusType = $imported > 0 ? 'success' : 'info';
            return redirect()
                ->route('admin.unites-enseignement.catalog')
                ->with($statusType, $message);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
            }

            return redirect()->back()
                ->withErrors(['file' => 'Erreurs de validation détectées'])
                ->with('import_errors', $errorMessages)
                ->withInput();

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['file' => 'Erreur lors de l\'import : ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Télécharger le template d'import
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UnitesEnseignementTemplateExport(),
            'template_import_ue.xlsx'
        );
    }
}
