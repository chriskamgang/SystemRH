<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class SeedDefaultRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:seed-defaults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cree les roles et permissions par defaut si manquants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seed des roles et permissions par defaut...');

        // Roles a creer
        $rolesToCreate = [
            [
                'name' => 'receptionniste',
                'display_name' => 'Receptionniste',
                'description' => 'Gere les tickets, accueil et transmission',
            ],
            [
                'name' => 'rh',
                'display_name' => 'Responsable RH',
                'description' => 'Gere les employes, conges, paie, justifications',
            ],
            [
                'name' => 'chef_service',
                'display_name' => 'Chef de Service',
                'description' => 'Voit les membres de son departement, repond aux tickets',
            ],
        ];

        foreach ($rolesToCreate as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                ]
            );
            $this->line("  Role '{$role->name}' : " . ($role->wasRecentlyCreated ? 'cree' : 'existant'));
        }

        // Permissions a creer
        $permissionsToCreate = [
            ['name' => 'access_dashboard', 'display_name' => 'Acces au tableau de bord', 'description' => 'Permet de se connecter au site web', 'category' => 'dashboard'],
            ['name' => 'dashboard.view', 'display_name' => 'Voir le dashboard', 'description' => 'Voir la page d\'accueil du dashboard', 'category' => 'dashboard'],
            ['name' => 'tickets.view', 'display_name' => 'Voir les tickets', 'description' => 'Voir et gerer les tickets', 'category' => 'tickets'],
            ['name' => 'employees.view', 'display_name' => 'Voir les employes', 'description' => 'Voir la liste des employes', 'category' => 'employees'],
            ['name' => 'attendance.view', 'display_name' => 'Voir les presences', 'description' => 'Voir les pointages et presences', 'category' => 'attendance'],
            ['name' => 'reports.view', 'display_name' => 'Voir les rapports', 'description' => 'Voir les rapports et statistiques', 'category' => 'reports'],
            ['name' => 'campus.view', 'display_name' => 'Voir les campus', 'description' => 'Voir et gerer les campus', 'category' => 'campus'],
            ['name' => 'leaves.view', 'display_name' => 'Voir les conges', 'description' => 'Voir et gerer les demandes de conge', 'category' => 'leaves'],
            ['name' => 'justifications.view', 'display_name' => 'Voir les justifications', 'description' => 'Voir et gerer les justifications de retard', 'category' => 'justifications'],
            ['name' => 'certificates.view', 'display_name' => 'Voir les attestations', 'description' => 'Voir et gerer les demandes d\'attestation', 'category' => 'certificates'],
            ['name' => 'salary_advances.view', 'display_name' => 'Voir les avances salaire', 'description' => 'Voir et gerer les avances sur salaire', 'category' => 'salary_advances'],
        ];

        foreach ($permissionsToCreate as $permData) {
            $perm = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'display_name' => $permData['display_name'],
                    'description' => $permData['description'],
                    'category' => $permData['category'],
                ]
            );
            $this->line("  Permission '{$perm->name}' : " . ($perm->wasRecentlyCreated ? 'creee' : 'existante'));
        }

        // Assignation des permissions par role
        $rolePermissions = [
            'receptionniste' => ['access_dashboard', 'dashboard.view', 'tickets.view'],
            'rh' => ['access_dashboard', 'dashboard.view', 'employees.view', 'attendance.view', 'reports.view', 'leaves.view', 'justifications.view', 'certificates.view', 'salary_advances.view'],
            'chef_service' => ['access_dashboard', 'dashboard.view', 'tickets.view', 'attendance.view'],
            'chef_departement' => ['access_dashboard', 'dashboard.view', 'employees.view', 'attendance.view'],
            'responsable_campus' => ['access_dashboard', 'dashboard.view', 'attendance.view', 'campus.view'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->warn("  Role '{$roleName}' introuvable, ignore.");
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
            $role->permissions()->syncWithoutDetaching($permissionIds);
            $this->line("  Role '{$roleName}' : " . count($permissionIds) . ' permission(s) assignee(s)');
        }

        $this->info('Seed termine avec succes.');

        return Command::SUCCESS;
    }
}
