<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les IDs des rôles et permissions
        $admin = DB::table('roles')->where('name', 'admin')->first();
        $chefDept = DB::table('roles')->where('name', 'chef_departement')->first();
        $respCampus = DB::table('roles')->where('name', 'responsable_campus')->first();
        $employe = DB::table('roles')->where('name', 'employe')->first();

        // Récupérer toutes les permissions
        $permissions = DB::table('permissions')->get()->keyBy('name');

        $rolePermissions = [];

        // ADMIN : Toutes les permissions
        foreach ($permissions as $permission) {
            $rolePermissions[] = [
                'role_id' => $admin->id,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // CHEF DE DÉPARTEMENT : Permissions limitées au département
        $chefDeptPermissions = [
            'view_own_history',
            'view_department',
            'view_campus',
            'view_realtime_presence',
            'manage_employees', // Peut gérer les employés de son département
            'generate_reports',
            'export_data',
            'view_statistics',
        ];
        foreach ($chefDeptPermissions as $permName) {
            if (isset($permissions[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $chefDept->id,
                    'permission_id' => $permissions[$permName]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // RESPONSABLE CAMPUS : Permissions limitées au campus
        $respCampusPermissions = [
            'view_own_history',
            'view_campus',
            'view_realtime_presence',
            'generate_reports',
            'export_data',
            'view_statistics',
        ];
        foreach ($respCampusPermissions as $permName) {
            if (isset($permissions[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $respCampus->id,
                    'permission_id' => $permissions[$permName]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // EMPLOYÉ : Permissions basiques
        $employePermissions = [
            'view_own_history',
        ];
        foreach ($employePermissions as $permName) {
            if (isset($permissions[$permName])) {
                $rolePermissions[] = [
                    'role_id' => $employe->id,
                    'permission_id' => $permissions[$permName]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('role_permissions')->insert($rolePermissions);

        $this->command->info('✅ Permissions attribuées aux rôles avec succès');
        $this->command->info('   - Admin : ' . count($permissions) . ' permissions');
        $this->command->info('   - Chef département : ' . count($chefDeptPermissions) . ' permissions');
        $this->command->info('   - Responsable campus : ' . count($respCampusPermissions) . ' permissions');
        $this->command->info('   - Employé : ' . count($employePermissions) . ' permission(s)');
    }
}
