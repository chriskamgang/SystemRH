<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        } else {
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
        } else {
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

        // Mettre à jour l'employé
        $employee->update($validated);

        // Synchroniser les campus
        if ($request->has('campuses')) {
            $employee->campuses()->sync($request->campuses);
        } else {
            $employee->campuses()->detach();
        }

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
}
