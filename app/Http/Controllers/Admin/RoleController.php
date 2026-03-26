<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    /**
     * Liste des rôles
     */
    public function index()
    {
        $roles = Role::withCount('users')->with('permissions')->get();

        // Grouper les permissions par module
        $permissions = Permission::where('name', '!=', 'access_dashboard')
            ->orderBy('category')
            ->get();

        $modules = $permissions->groupBy('category')->map(function ($perms, $module) {
            return [
                'name' => $module,
                'label' => $this->getModuleLabel($module),
                'icon' => $this->getModuleIcon($module),
                'permissions' => $perms,
            ];
        });

        return view('admin.roles.index', compact('roles', 'modules'));
    }

    /**
     * Créer un rôle
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'access_dashboard' => 'nullable|boolean',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Attacher les permissions
        $permissionIds = $request->input('permissions', []);

        // Ajouter access_dashboard si coché
        if ($request->boolean('access_dashboard')) {
            $accessPerm = Permission::where('name', 'access_dashboard')->first();
            if ($accessPerm) {
                $permissionIds[] = $accessPerm->id;
            }
        }

        $role->permissions()->sync($permissionIds);

        return back()->with('success', "Rôle \"{$role->display_name}\" créé avec succès.");
    }

    /**
     * Modifier un rôle
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Empêcher la modification du rôle admin
        if ($role->name === 'admin') {
            return back()->with('error', 'Le rôle Administrateur ne peut pas être modifié.');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'access_dashboard' => 'nullable|boolean',
        ]);

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Synchroniser les permissions
        $permissionIds = $request->input('permissions', []);

        if ($request->boolean('access_dashboard')) {
            $accessPerm = Permission::where('name', 'access_dashboard')->first();
            if ($accessPerm) {
                $permissionIds[] = $accessPerm->id;
            }
        }

        $role->permissions()->sync($permissionIds);

        return back()->with('success', "Rôle \"{$role->display_name}\" mis à jour.");
    }

    /**
     * Supprimer un rôle
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            return back()->with('error', 'Le rôle Administrateur ne peut pas être supprimé.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Ce rôle est assigné à {$role->users()->count()} utilisateur(s). Réassignez-les d'abord.");
        }

        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', "Rôle supprimé.");
    }

    /**
     * Liste des utilisateurs admin (ceux avec accès dashboard)
     */
    public function admins()
    {
        $admins = User::whereHas('role', function ($q) {
            $q->whereHas('permissions', function ($q2) {
                $q2->where('name', 'access_dashboard');
            });
        })->orWhere('role_id', 1)->with('role')->orderBy('first_name')->get();

        $roles = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'access_dashboard');
        })->orWhere('name', 'admin')->get();

        return view('admin.roles.admins', compact('admins', 'roles'));
    }

    /**
     * Créer un compte administrateur
     */
    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'password123'),
            'role_id' => $validated['role_id'],
            'employee_type' => 'administratif',
            'is_active' => true,
        ]);

        return back()->with('success', "Compte créé pour {$user->full_name}.");
    }

    /**
     * Changer le rôle d'un utilisateur
     */
    public function updateAdmin(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return back()->with('success', "Rôle de {$user->full_name} mis à jour.");
    }

    /**
     * Labels des modules
     */
    private function getModuleLabel($module)
    {
        return [
            'dashboard' => 'Tableau de bord',
            'employees' => 'Employés',
            'campus' => 'Campus',
            'attendance' => 'Présences',
            'manual_attendance' => 'Présences Manuelles',
            'ue' => 'Unités d\'Enseignement',
            'schedule' => 'Emploi du Temps',
            'vacataires' => 'Vacataires',
            'semi_permanents' => 'Semi-permanents',
            'realtime_map' => 'Carte en temps réel',
            'reports' => 'Rapports',
            'payroll' => 'Paie & Paiements',
            'deductions' => 'Déductions Manuelles',
            'loans' => 'Prêts',
            'presence_alerts' => 'Alertes de Présence',
            'realtime_tracking' => 'Suivi en Temps Réel',
            'security' => 'Sécurité Anti-Fraude',
            'firebase' => 'Firebase',
            'settings' => 'Paramètres',
            'roles' => 'Gestion des Rôles',
        ][$module] ?? ucfirst($module);
    }

    private function getModuleIcon($module)
    {
        return [
            'dashboard' => 'fas fa-tachometer-alt',
            'employees' => 'fas fa-users',
            'campus' => 'fas fa-university',
            'attendance' => 'fas fa-calendar-check',
            'manual_attendance' => 'fas fa-hand-pointer',
            'ue' => 'fas fa-book',
            'schedule' => 'fas fa-clock',
            'vacataires' => 'fas fa-user-clock',
            'semi_permanents' => 'fas fa-user-tie',
            'realtime_map' => 'fas fa-map-marked-alt',
            'reports' => 'fas fa-chart-bar',
            'payroll' => 'fas fa-money-bill-wave',
            'deductions' => 'fas fa-minus-circle',
            'loans' => 'fas fa-hand-holding-usd',
            'presence_alerts' => 'fas fa-bell',
            'realtime_tracking' => 'fas fa-satellite-dish',
            'security' => 'fas fa-shield-alt',
            'firebase' => 'fas fa-fire',
            'settings' => 'fas fa-cog',
            'roles' => 'fas fa-user-shield',
        ][$module] ?? 'fas fa-circle';
    }
}
