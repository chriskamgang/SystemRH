<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Campus;
use App\Models\UserCampusShift;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'campuses'])
            ->where('role_id', '!=', 1); // Exclure les admins

        // Recherche
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->has('employee_type') && $request->employee_type) {
            $query->where('employee_type', $request->employee_type);
        }

        if ($request->has('campus') && $request->campus) {
            $query->whereHas('campuses', function($q) use ($request) {
                $q->where('campuses.id', $request->campus);
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::where('id', '!=', 1)->get(); // Exclure le role admin
        $campuses = Campus::all();

        return view('admin.employees.index', compact('employees', 'roles', 'campuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::where('id', '!=', 1)->get();
        $campuses = Campus::all();
        return view('admin.employees.create', compact('roles', 'campuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_type' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'campuses' => 'nullable|array',
            'campuses.*' => 'exists:campuses,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
            'monthly_salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'volume_horaire_hebdomadaire' => 'nullable|numeric|min:0|max:168',
            'jours_travail' => 'nullable|array',
            'jours_travail.*' => 'string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
        ], [
            'volume_horaire_hebdomadaire.max' => 'Le volume horaire hebdomadaire ne peut pas dépasser 168 heures (nombre d\'heures dans une semaine).',
        ]);

        // Si pas de role_id fourni, assigner le rôle "Employé Standard" par défaut
        if (!$request->filled('role_id')) {
            $defaultRole = \App\Models\Role::where('name', 'employe')->first();
            $validated['role_id'] = $defaultRole ? $defaultRole->id : 4;
        }

        // Validation conditionnelle selon le type d'employé
        if ($request->employee_type === 'enseignant_vacataire') {
            $request->validate([
                'hourly_rate' => 'required|numeric|min:0',
            ]);
        } elseif ($request->employee_type === 'semi_permanent') {
            // Semi-permanent : salaire mensuel + volume horaire + jours de travail OBLIGATOIRES
            $request->validate([
                'monthly_salary' => 'required|numeric|min:0',
                'volume_horaire_hebdomadaire' => 'required|numeric|min:0',
                'jours_travail' => 'required|array|min:1',
            ]);
        } else {
            // Autres types : salaire mensuel uniquement
            $request->validate([
                'monthly_salary' => 'required|numeric|min:0',
            ]);
        }

        // Générer automatiquement l'employee_id
        $validated['employee_id'] = $this->generateEmployeeId();

        // Upload photo si présente
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo_url'] = $path;
        }

        // Hasher le mot de passe
        $validated['password'] = Hash::make($validated['password']);

        // Créer l'employé
        $employee = User::create($validated);

        // Attacher les campus
        if ($request->has('campuses')) {
            $employee->campuses()->attach($request->campuses);
        }

        // Gérer les plages horaires pour les permanents enseignants
        if ($request->employee_type === 'enseignant_titulaire' && $request->has('shifts')) {
            foreach ($request->shifts as $campusId => $shifts) {
                if (isset($shifts['morning']) || isset($shifts['evening'])) {
                    UserCampusShift::create([
                        'user_id' => $employee->id,
                        'campus_id' => $campusId,
                        'works_morning' => isset($shifts['morning']) && $shifts['morning'] == '1',
                        'works_evening' => isset($shifts['evening']) && $shifts['evening'] == '1',
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employé créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $employee = User::with(['role', 'campuses', 'attendances' => function($query) {
            $query->orderBy('timestamp', 'desc')->limit(20);
        }])->findOrFail($id);

        // Statistiques de l'employé
        $stats = [
            'total_checkins' => $employee->attendances()->where('type', 'check-in')->count(),
            'late_count' => $employee->attendances()->where('type', 'check-in')->where('is_late', true)->count(),
            'this_month_checkins' => $employee->attendances()
                ->where('type', 'check-in')
                ->whereMonth('timestamp', now()->month)
                ->count(),
            'avg_late_minutes' => $employee->attendances()
                ->where('type', 'check-in')
                ->where('is_late', true)
                ->avg('late_minutes') ?? 0,
        ];

        return view('admin.employees.show', compact('employee', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $employee = User::with('campuses')->findOrFail($id);
        $roles = Role::where('id', '!=', 1)->get();
        $campuses = Campus::all();

        return view('admin.employees.edit', compact('employee', 'roles', 'campuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        \Log::info('=== UPDATE EMPLOYEE START ===', [
            'employee_id' => $id,
            'employee_type' => $request->employee_type,
            'has_jours_travail' => $request->has('jours_travail'),
            'jours_travail' => $request->jours_travail,
        ]);

        $employee = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($employee->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_type' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'campuses' => 'nullable|array',
            'campuses.*' => 'exists:campuses,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'monthly_salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'volume_horaire_hebdomadaire' => 'nullable|numeric|min:0|max:168',
            'jours_travail' => 'nullable|array',
            'jours_travail.*' => 'string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
        ], [
            'volume_horaire_hebdomadaire.max' => 'Le volume horaire hebdomadaire ne peut pas dépasser 168 heures (nombre d\'heures dans une semaine).',
        ]);

        // Si pas de role_id fourni, assigner le rôle "Employé Standard" par défaut
        if (!$request->filled('role_id')) {
            $defaultRole = \App\Models\Role::where('name', 'employe')->first();
            $validated['role_id'] = $defaultRole ? $defaultRole->id : 4;
        }

        // Validation conditionnelle selon le type d'employé
        if ($request->employee_type === 'enseignant_vacataire') {
            $request->validate([
                'hourly_rate' => 'required|numeric|min:0',
            ]);
        } elseif ($request->employee_type === 'semi_permanent') {
            // Semi-permanent : salaire mensuel + volume horaire + jours de travail OBLIGATOIRES
            $request->validate([
                'monthly_salary' => 'required|numeric|min:0',
                'volume_horaire_hebdomadaire' => 'required|numeric|min:0',
                'jours_travail' => 'required|array|min:1',
            ]);
        } else {
            // Autres types : salaire mensuel uniquement
            $request->validate([
                'monthly_salary' => 'required|numeric|min:0',
            ]);
        }

        // Gérer is_active (checkbox)
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        // Upload nouvelle photo si présente
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo
            if ($employee->photo_url) {
                Storage::disk('public')->delete($employee->photo_url);
            }
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo_url'] = $path;
        }

        // Hasher le mot de passe si fourni
        if ($request->password) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Nettoyer les champs selon le type d'employé
        if ($request->employee_type !== 'semi_permanent') {
            // Si ce n'est pas un semi-permanent, nettoyer les champs spécifiques
            $validated['volume_horaire_hebdomadaire'] = null;
            $validated['jours_travail'] = null;
        }

        if ($request->employee_type !== 'enseignant_vacataire') {
            // Si ce n'est pas un vacataire, nettoyer le taux horaire
            $validated['hourly_rate'] = null;
        }

        // Mettre à jour l'employé
        \Log::info('Updating employee with data:', $validated);
        $employee->update($validated);
        \Log::info('Employee updated successfully');

        // Synchroniser les campus
        if ($request->has('campuses')) {
            $employee->campuses()->sync($request->campuses);
        } else {
            $employee->campuses()->detach();
        }

        // Gérer les plages horaires pour les permanents enseignants
        if ($request->employee_type === 'enseignant_titulaire' && $request->has('shifts')) {
            // Supprimer toutes les anciennes assignations de plages
            UserCampusShift::where('user_id', $employee->id)->delete();

            // Ajouter les nouvelles assignations
            foreach ($request->shifts as $campusId => $shifts) {
                if (isset($shifts['morning']) || isset($shifts['evening'])) {
                    UserCampusShift::create([
                        'user_id' => $employee->id,
                        'campus_id' => $campusId,
                        'works_morning' => isset($shifts['morning']) && $shifts['morning'] == '1',
                        'works_evening' => isset($shifts['evening']) && $shifts['evening'] == '1',
                    ]);
                }
            }
        }

        \Log::info('=== UPDATE EMPLOYEE SUCCESS ===');

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employé mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = User::findOrFail($id);

        // Supprimer la photo si présente
        if ($employee->photo_url) {
            Storage::disk('public')->delete($employee->photo_url);
        }

        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employé supprimé avec succès.');
    }

    /**
     * Reset device for an employee
     */
    public function resetDevice(string $id)
    {
        $employee = User::findOrFail($id);

        $employee->update([
            'device_id' => null,
            'device_model' => null,
            'device_os' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Appareil réinitialisé. L\'employé pourra se connecter depuis un nouvel appareil.');
    }

    /**
     * Generate a unique employee ID
     * Format: EMP-YYYY-XXXX (ex: EMP-2025-0001)
     */
    private function generateEmployeeId()
    {
        $year = date('Y');
        $prefix = "EMP-{$year}-";

        // Trouver le dernier employee_id de l'année en cours
        $lastEmployee = User::where('employee_id', 'like', "{$prefix}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extraire le numéro et l'incrémenter
            $lastNumber = intval(substr($lastEmployee->employee_id, -4));
            $newNumber = $lastNumber + 1;
        } else {
            // Premier employé de l'année
            $newNumber = 1;
        }

        // Formater avec des zéros devant (0001, 0002, etc.)
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Afficher la page d'import
     */
    public function showImportForm()
    {
        return view('admin.employees.import');
    }

    /**
     * Télécharger le template CSV/Excel
     */
    public function downloadTemplate()
    {
        return Excel::download(new EmployeesTemplateExport, 'template_import_employes.xlsx');
    }

    /**
     * Importer des employés depuis un fichier CSV/Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // 5MB max
        ], [
            'file.required' => 'Veuillez sélectionner un fichier à importer',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV (.csv)',
            'file.max' => 'Le fichier ne doit pas dépasser 5 Mo',
        ]);

        try {
            $import = new EmployeesImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $skipCount = $import->getSkipCount();
            $errors = $import->getErrors();

            // Préparer le message de résultat
            $message = '';

            if ($successCount > 0) {
                $message .= "{$successCount} employé(s) importé(s) avec succès. ";
            }

            if ($skipCount > 0) {
                $message .= "{$skipCount} ligne(s) ignorée(s) (emails déjà existants). ";
            }

            if (!empty($errors)) {
                $message .= "Erreurs rencontrées : " . implode(' | ', $errors);
                return redirect()->route('admin.employees.import-form')
                    ->with('warning', $message);
            }

            if ($successCount == 0) {
                return redirect()->route('admin.employees.import-form')
                    ->with('error', 'Aucun employé n\'a été importé. Vérifiez le format du fichier.');
            }

            return redirect()->route('admin.employees.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('admin.employees.import-form')
                ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
