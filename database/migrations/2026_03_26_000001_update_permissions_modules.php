<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les anciennes permissions
        DB::table('role_permissions')->truncate();
        DB::table('user_permissions')->truncate();
        DB::table('permissions')->truncate();

        // Nouvelles permissions par module avec actions CRUD
        $modules = [
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
        ];

        $actions = [
            'view' => 'Voir',
            'create' => 'Créer',
            'edit' => 'Modifier',
            'delete' => 'Supprimer',
        ];

        $permissions = [];
        foreach ($modules as $module => $moduleName) {
            foreach ($actions as $action => $actionName) {
                $permissions[] = [
                    'name' => "{$module}.{$action}",
                    'display_name' => "{$actionName} - {$moduleName}",
                    'description' => "{$actionName} dans le module {$moduleName}",
                    'category' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Permission spéciale pour accéder au dashboard admin
        $permissions[] = [
            'name' => 'access_dashboard',
            'display_name' => 'Accès au panneau d\'administration',
            'description' => 'Permet de se connecter au panneau d\'administration',
            'category' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('permissions')->insert($permissions);

        // Donner TOUTES les permissions au rôle admin (id=1)
        $allPermissionIds = DB::table('permissions')->pluck('id');
        $adminRole = DB::table('roles')->where('name', 'admin')->first();

        if ($adminRole) {
            $rolePermissions = $allPermissionIds->map(function ($permId) use ($adminRole) {
                return [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DB::table('role_permissions')->insert($rolePermissions);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->truncate();
        DB::table('user_permissions')->truncate();
        DB::table('permissions')->truncate();
    }
};
