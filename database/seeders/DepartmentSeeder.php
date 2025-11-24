<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les campus
        $campusSci = DB::table('campuses')->where('code', 'CAM-SCI')->first();
        $campusLet = DB::table('campuses')->where('code', 'CAM-LET')->first();
        $campusDrt = DB::table('campuses')->where('code', 'CAM-DRT')->first();
        $campusMed = DB::table('campuses')->where('code', 'CAM-MED')->first();
        $campusTech = DB::table('campuses')->where('code', 'CAM-TECH')->first();
        $campusCom = DB::table('campuses')->where('code', 'CAM-COM')->first();

        $departments = [
            [
                'name' => 'Mathématiques',
                'code' => 'DEPT-MATH',
                'description' => 'Département de mathématiques pures et appliquées',
                'campus_id' => $campusSci->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Physique',
                'code' => 'DEPT-PHYS',
                'description' => 'Département de physique',
                'campus_id' => $campusSci->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Littérature',
                'code' => 'DEPT-LITT',
                'description' => 'Département de littérature et langues',
                'campus_id' => $campusLet->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Histoire',
                'code' => 'DEPT-HIST',
                'description' => 'Département d\'histoire et géographie',
                'campus_id' => $campusLet->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Droit Public',
                'code' => 'DEPT-DRTP',
                'description' => 'Département de droit public',
                'campus_id' => $campusDrt->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Médecine Générale',
                'code' => 'DEPT-MEDG',
                'description' => 'Département de médecine générale',
                'campus_id' => $campusMed->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Informatique',
                'code' => 'DEPT-INFO',
                'description' => 'Département d\'informatique et systèmes',
                'campus_id' => $campusTech->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Économie',
                'code' => 'DEPT-ECO',
                'description' => 'Département de sciences économiques',
                'campus_id' => $campusCom->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('departments')->insert($departments);

        $this->command->info('✅ ' . count($departments) . ' départements créés avec succès');
    }
}
