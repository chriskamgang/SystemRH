<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Campus;
use App\Models\Department;
use App\Imports\StudentsImport;
use App\Exports\StudentsTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * Liste tous les étudiants
     */
    public function index(Request $request)
    {
        $query = User::where('employee_type', 'etudiant');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Pour un regroupement propre, on récupère tout ou on pagine largement
        // Ici on va grouper la collection
        $allStudents = $query->orderBy('niveau')->orderBy('specialite')->orderBy('last_name')->get();
        
        $groupedStudents = $allStudents->groupBy([
            function ($item) use ($request) {
                return $request->search ? 'Résultats de recherche' : ($item->niveau ?? 'Niveau non défini');
            },
            function ($item) {
                return $item->specialite ?? 'Spécialité non définie';
            }
        ]);

        $campuses = Campus::where('is_active', true)->get();

        return view('admin.students.index', compact('groupedStudents', 'campuses', 'allStudents'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(Request $request)
    {
        $departments = \App\Models\Department::where('is_active', true)->get();
        $prefilledNiveau = $request->niveau;
        $prefilledSpecialite = $request->specialite;
        
        // On récupère aussi la liste des niveaux et spécialités existants pour les selects
        $levels = \App\Models\Level::where('is_active', true)->orderBy('name')->get();
        $specialties = \App\Models\Specialty::where('is_active', true)->orderBy('name')->get();

        return view('admin.students.create', compact('departments', 'prefilledNiveau', 'prefilledSpecialite', 'levels', 'specialties'));
    }

    /**
     * Générer un matricule automatique
     */
    private function generateMatricule($level, $departmentId)
    {
        $year = date('y');
        $levelChar = $level ? strtoupper(substr($level, 0, 1)) : 'S';
        
        $deptCode = 'GS'; // Code par défaut
        if ($departmentId) {
            $dept = Department::find($departmentId);
            if ($dept && $dept->code) {
                $deptCode = strtoupper($dept->code);
            }
        }

        // Trouver le dernier numéro pour cette année
        $lastStudent = User::where('employee_id', 'like', $year . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastStudent && preg_match('/^\d{2}[A-Z](\d{3})/', $lastStudent->employee_id, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        return sprintf('%02d%s%03d%s', $year, $levelChar, $sequence, $deptCode);
    }

    /**
     * Créer un nouvel étudiant
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_id' => 'nullable|string|unique:users,employee_id', // Facultatif car auto
            'specialite' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $role = Role::where('name', 'employee')->first();
        
        $matricule = $request->employee_id ?: $this->generateMatricule($request->niveau, $request->department_id);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password ?: 'password123'),
            'employee_id' => $matricule,
            'employee_type' => 'etudiant',
            'specialite' => $request->specialite,
            'niveau' => $request->niveau,
            'phone' => $request->phone,
            'department_id' => $request->department_id,
            'role_id' => $role ? $role->id : 2,
            'is_active' => true,
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        User::create($data);

        return redirect()->route('admin.students.index')->with('success', "Étudiant créé avec succès. Matricule : {$matricule}");
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        $student = User::where('employee_type', 'etudiant')->findOrFail($id);
        $departments = \App\Models\Department::where('is_active', true)->get();
        $levels = \App\Models\Level::where('is_active', true)->orderBy('name')->get();
        $specialties = \App\Models\Specialty::where('is_active', true)->orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'departments', 'levels', 'specialties'));
    }

    /**
     * Mettre à jour l'étudiant
     */
    public function update(Request $request, $id)
    {
        $student = User::where('employee_type', 'etudiant')->findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'employee_id' => 'required|string|unique:users,employee_id,' . $id,
            'specialite' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'specialite' => $request->specialite,
            'niveau' => $request->niveau,
            'phone' => $request->phone,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $student->update($data);

        return redirect()->route('admin.students.index')->with('success', 'Informations de l\'étudiant mises à jour.');
    }

    /**
     * Attribution massive de campus
     */
    public function assignCampusesBulk(Request $request)
    {
        $request->validate([
            'campus_ids' => 'required|array',
            'campus_ids.*' => 'exists:campuses,id',
            'assign_to' => 'required|string|in:all_students',
        ]);

        $students = User::where('employee_type', 'etudiant')->get();
        $campusIds = $request->campus_ids;

        foreach ($students as $student) {
            // Détacher les anciens campus si on veut écraser, ou juste ajouter
            // Ici on va ajouter sans écraser, sauf si spécifié (mais on reste simple)
            foreach ($campusIds as $index => $campusId) {
                if (!$student->campuses()->where('campus_id', $campusId)->exists()) {
                    $student->campuses()->attach($campusId, ['is_primary' => $index === 0]);
                }
            }
        }

        return redirect()->back()->with('success', "Campus assignés avec succès à tous les étudiants.");
    }

    /**
     * Télécharger le template d'import
     */
    public function downloadTemplate()
    {
        return Excel::download(new StudentsTemplateExport, 'template_import_etudiants.xlsx');
    }

    /**
     * Importer des étudiants
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new StudentsImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $message = "{$results['success']} étudiant(s) importé(s) avec succès.";
            
            if ($results['updated'] > 0) {
                $message .= " {$results['updated']} étudiant(s) mis à jour.";
            }

            if (!empty($results['errors'])) {
                return redirect()->back()->with('warning', $message . " Cependant, des erreurs ont été rencontrées : " . implode(', ', $results['errors']));
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
