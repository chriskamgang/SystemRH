<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    /**
     * Liste des entreprises (super admin uniquement)
     */
    public function index()
    {
        // withoutGlobalScopes sur le count des users pour le super admin
        $companies = Company::withCount(['users' => function ($q) {
            $q->withoutGlobalScopes();
        }])->orderBy('name')->get();

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Formulaire de creation
     */
    public function create()
    {
        return view('admin.companies.create');
    }

    /**
     * Enregistrer une nouvelle entreprise
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:100',
            'max_employees' => 'required|integer|min:1',
            'subscription_plan' => 'required|in:basic,pro,enterprise',
            'logo' => 'nullable|image|max:2048',
            // Admin de l'entreprise
            'admin_first_name' => 'required|string|max:255',
            'admin_last_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('companies/logos', 'public');
        }

        $company = Company::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'sector' => $request->sector,
            'max_employees' => $request->max_employees,
            'subscription_plan' => $request->subscription_plan,
            'logo' => $logoPath,
            'is_active' => true,
        ]);

        // Creer le role admin pour cette entreprise
        $adminRole = Role::withoutGlobalScopes()->create([
            'name' => 'admin',
            'display_name' => 'Administrateur',
            'description' => 'Administrateur de ' . $company->name,
            'company_id' => $company->id,
        ]);

        // Creer le role employe pour cette entreprise
        Role::withoutGlobalScopes()->create([
            'name' => 'employe',
            'display_name' => 'Employe',
            'description' => 'Employe de ' . $company->name,
            'company_id' => $company->id,
        ]);

        // Creer l'admin de l'entreprise
        User::withoutGlobalScopes()->create([
            'first_name' => $request->admin_first_name,
            'last_name' => $request->admin_last_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'employee_id' => 'ADM-' . strtoupper(Str::random(6)),
            'role_id' => $adminRole->id,
            'company_id' => $company->id,
            'is_active' => true,
            'can_access_admin' => true,
            'employee_type' => 'permanent',
        ]);

        return redirect()->route('admin.companies.index')
            ->with('success', 'Entreprise "' . $company->name . '" creee avec succes.');
    }

    /**
     * Voir les details d'une entreprise
     */
    public function show($id)
    {
        $company = Company::withCount(['users' => fn($q) => $q->withoutGlobalScopes()])->findOrFail($id);

        $stats = [
            'total_employees' => User::withoutGlobalScopes()->where('company_id', $id)->where('is_active', true)->count(),
            'total_departments' => \App\Models\Department::withoutGlobalScopes()->where('company_id', $id)->count(),
            'total_campuses' => \App\Models\Campus::withoutGlobalScopes()->where('company_id', $id)->count(),
        ];

        $admins = User::withoutGlobalScopes()
            ->where('company_id', $id)
            ->whereHas('role', fn($q) => $q->withoutGlobalScopes()->where('name', 'admin'))
            ->get();

        return view('admin.companies.show', compact('company', 'stats', 'admins'));
    }

    /**
     * Modifier une entreprise
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:100',
            'max_employees' => 'required|integer|min:1',
            'subscription_plan' => 'required|in:basic,pro,enterprise',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'address', 'city', 'sector', 'max_employees', 'subscription_plan']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        return back()->with('success', 'Entreprise mise a jour.');
    }

    /**
     * Activer/desactiver une entreprise
     */
    public function toggle($id)
    {
        $company = Company::findOrFail($id);
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'activee' : 'desactivee';
        return back()->with('success', "Entreprise {$status}.");
    }

    /**
     * Super admin switch : se connecter en tant qu'entreprise
     */
    public function switchTo($id)
    {
        $company = Company::findOrFail($id);

        if (!$company->is_active) {
            return back()->with('error', 'Cette entreprise est desactivee.');
        }

        session(['current_company_id' => $company->id, 'switched_company_name' => $company->name]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Vous etes maintenant dans l\'espace de "' . $company->name . '".');
    }

    /**
     * Super admin : revenir a la vue globale
     */
    public function switchBack()
    {
        session()->forget(['current_company_id', 'switched_company_name']);

        return redirect()->route('admin.companies.index')
            ->with('success', 'Retour a la vue super admin.');
    }
}
