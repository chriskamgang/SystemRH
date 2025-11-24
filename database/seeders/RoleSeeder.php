<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur Central',
                'description' => 'Accès complet à tous les campus et données. Peut gérer tous les aspects du système.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'chef_departement',
                'display_name' => 'Chef de Département',
                'description' => 'Accès limité à son département. Peut voir et gérer les employés de son département.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'responsable_campus',
                'display_name' => 'Responsable de Campus',
                'description' => 'Accès limité à son campus. Peut voir les présences et générer des rapports pour son campus.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'employe',
                'display_name' => 'Employé Standard',
                'description' => 'Accès uniquement à ses propres données de pointage et historique.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);

        $this->command->info('✅ 4 rôles créés avec succès');
    }
}
