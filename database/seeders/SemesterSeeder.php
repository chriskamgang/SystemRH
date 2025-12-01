<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Semester;
use Carbon\Carbon;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Année académique 2024-2025
        Semester::create([
            'name' => 'Semestre 1 (2024-2025)',
            'code' => 'S1-2024-2025',
            'annee_academique' => '2024-2025',
            'numero_semestre' => 1,
            'date_debut' => Carbon::create(2024, 9, 1),
            'date_fin' => Carbon::create(2025, 1, 31),
            'is_active' => false,
            'description' => 'Premier semestre de l\'année académique 2024-2025',
        ]);

        Semester::create([
            'name' => 'Semestre 2 (2024-2025)',
            'code' => 'S2-2024-2025',
            'annee_academique' => '2024-2025',
            'numero_semestre' => 2,
            'date_debut' => Carbon::create(2025, 2, 1),
            'date_fin' => Carbon::create(2025, 7, 31),
            'is_active' => true, // Semestre actif par défaut
            'description' => 'Deuxième semestre de l\'année académique 2024-2025',
        ]);

        // Année académique 2025-2026
        Semester::create([
            'name' => 'Semestre 1 (2025-2026)',
            'code' => 'S1-2025-2026',
            'annee_academique' => '2025-2026',
            'numero_semestre' => 1,
            'date_debut' => Carbon::create(2025, 9, 1),
            'date_fin' => Carbon::create(2026, 1, 31),
            'is_active' => false,
            'description' => 'Premier semestre de l\'année académique 2025-2026',
        ]);

        Semester::create([
            'name' => 'Semestre 2 (2025-2026)',
            'code' => 'S2-2025-2026',
            'annee_academique' => '2025-2026',
            'numero_semestre' => 2,
            'date_debut' => Carbon::create(2026, 2, 1),
            'date_fin' => Carbon::create(2026, 7, 31),
            'is_active' => false,
            'description' => 'Deuxième semestre de l\'année académique 2025-2026',
        ]);

        $this->command->info('✅ Semestres créés avec succès!');
    }
}
