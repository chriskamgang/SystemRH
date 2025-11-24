<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Viewing permissions
            ['name' => 'view_own_history', 'display_name' => 'Voir son propre historique', 'category' => 'viewing', 'description' => 'Permet de voir son propre historique de pointages'],
            ['name' => 'view_department', 'display_name' => 'Voir son département', 'category' => 'viewing', 'description' => 'Permet de voir les données de son département'],
            ['name' => 'view_campus', 'display_name' => 'Voir son campus', 'category' => 'viewing', 'description' => 'Permet de voir les données de son campus'],
            ['name' => 'view_all_campuses', 'display_name' => 'Voir tous les campus', 'category' => 'viewing', 'description' => 'Permet de voir les données de tous les campus'],
            ['name' => 'view_realtime_presence', 'display_name' => 'Voir les présences en temps réel', 'category' => 'viewing', 'description' => 'Permet de voir qui est présent en temps réel'],

            // Management permissions
            ['name' => 'manage_employees', 'display_name' => 'Gérer les employés', 'category' => 'management', 'description' => 'Permet de créer, modifier et supprimer des employés'],
            ['name' => 'manage_campuses', 'display_name' => 'Gérer les campus', 'category' => 'management', 'description' => 'Permet de créer, modifier et supprimer des campus'],
            ['name' => 'manage_departments', 'display_name' => 'Gérer les départements', 'category' => 'management', 'description' => 'Permet de créer, modifier et supprimer des départements'],
            ['name' => 'manage_roles', 'display_name' => 'Gérer les rôles', 'category' => 'management', 'description' => 'Permet de gérer les rôles et permissions'],
            ['name' => 'manage_settings', 'display_name' => 'Gérer les paramètres', 'category' => 'management', 'description' => 'Permet de modifier les paramètres système'],

            // Reporting permissions
            ['name' => 'generate_reports', 'display_name' => 'Générer des rapports', 'category' => 'reporting', 'description' => 'Permet de générer des rapports de présence'],
            ['name' => 'export_data', 'display_name' => 'Exporter les données', 'category' => 'reporting', 'description' => 'Permet d\'exporter les données en Excel/PDF'],
            ['name' => 'view_statistics', 'display_name' => 'Voir les statistiques', 'category' => 'reporting', 'description' => 'Permet de voir les statistiques globales'],
        ];

        foreach ($permissions as &$permission) {
            $permission['created_at'] = now();
            $permission['updated_at'] = now();
        }

        DB::table('permissions')->insert($permissions);

        $this->command->info('✅ ' . count($permissions) . ' permissions créées avec succès');
    }
}
