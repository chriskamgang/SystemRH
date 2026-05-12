<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetupMultiTenant extends Command
{
    protected $signature = 'tenant:setup {--company-name=IUEs/INSAM : Nom de l\'entreprise par defaut}';
    protected $description = 'Configure le multi-tenant : cree l\'entreprise par defaut, assigne les donnees existantes, et cree le super admin';

    /**
     * Tables qui doivent recevoir le company_id de l'entreprise par defaut.
     */
    private array $tables = [
        'users', 'departments', 'campuses', 'roles', 'permissions',
        'attendances', 'tickets', 'ticket_services', 'ticket_categories',
        'leave_requests', 'justification_requests', 'work_certificates',
        'salary_advance_requests', 'payroll_records', 'vacataire_payments',
        'unites_enseignement', 'presence_incidents', 'tardiness', 'absences',
        'notifications', 'settings', 'loans', 'manual_deductions',
        'manual_payroll_adjustments', 'manual_attendances', 'security_violations',
        'wallets', 'tasks', 'complaints', 'employee_documents', 'job_positions',
        'moratorium_requests', 'evaluation_campaigns', 'job_postings',
        'training_programs', 'conversations', 'levels', 'specialties',
        'onboarding_templates',
    ];

    public function handle(): int
    {
        $companyName = $this->option('company-name');

        $this->info('=== Configuration Multi-Tenant ===');
        $this->newLine();

        // 1. Creer l'entreprise par defaut
        $this->info('1. Creation de l\'entreprise par defaut...');
        $company = Company::firstOrCreate(
            ['slug' => Str::slug($companyName)],
            [
                'name' => $companyName,
                'email' => 'contact@insam.cm',
                'city' => 'Douala',
                'country' => 'Cameroun',
                'sector' => 'Education',
                'subscription_plan' => 'enterprise',
                'max_employees' => 9999,
                'is_active' => true,
            ]
        );
        $this->line("   Entreprise '{$company->name}' (ID: {$company->id}) " . ($company->wasRecentlyCreated ? 'creee' : 'existante'));

        // 2. Assigner toutes les donnees existantes a cette entreprise
        $this->info('2. Assignation des donnees existantes...');
        foreach ($this->tables as $table) {
            if (\Schema::hasTable($table) && \Schema::hasColumn($table, 'company_id')) {
                $updated = DB::table($table)
                    ->whereNull('company_id')
                    ->update(['company_id' => $company->id]);

                if ($updated > 0) {
                    $this->line("   {$table}: {$updated} enregistrement(s) assigne(s)");
                }
            }
        }

        // 3. Creer ou promouvoir le super admin
        $this->info('3. Configuration du super admin...');

        // Chercher l'admin existant (role_id = 1 habituellement)
        $existingAdmin = User::withoutGlobalScopes()
            ->whereHas('role', fn($q) => $q->withoutGlobalScopes()->where('name', 'admin'))
            ->first();

        if ($existingAdmin) {
            $existingAdmin->update(['is_super_admin' => true]);
            $this->line("   Utilisateur '{$existingAdmin->email}' promu super admin");
        } else {
            $this->warn('   Aucun admin existant trouve.');
        }

        // Creer un super admin dedie si demande
        $createNew = $this->confirm('Creer un nouveau compte super admin dedie ?', false);
        if ($createNew) {
            $email = $this->ask('Email du super admin', 'superadmin@estuairerh.com');
            $password = $this->secret('Mot de passe') ?? 'password123';

            $adminRole = \App\Models\Role::withoutGlobalScopes()->where('name', 'admin')->first();

            $superAdmin = User::withoutGlobalScopes()->create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'employee_id' => 'SA-001',
                'role_id' => $adminRole ? $adminRole->id : 1,
                'is_active' => true,
                'is_super_admin' => true,
                'can_access_admin' => true,
                'employee_type' => 'permanent',
                // Pas de company_id = super admin global
            ]);

            $this->line("   Super admin cree: {$superAdmin->email}");
        }

        $this->newLine();
        $this->info('=== Configuration terminee ===');
        $this->newLine();
        $this->table(
            ['Element', 'Statut'],
            [
                ['Entreprise par defaut', $company->name . ' (ID: ' . $company->id . ')'],
                ['Super admin', $existingAdmin ? $existingAdmin->email : 'Non configure'],
                ['Donnees migrees', 'Oui'],
            ]
        );

        return Command::SUCCESS;
    }
}
