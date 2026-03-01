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
        // Récupérer le campus INSAM
        $campusInsam = DB::table('campuses')->where('code', 'INSAM-BFM')->first();

        $departments = [
            [
                'name' => 'Architecture',
                'code' => 'DEPT-ARCH',
                'description' => 'Département d\'Architecture et Design',
                'campus_id' => $campusInsam->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Management',
                'code' => 'DEPT-MGMT',
                'description' => 'Département de Management et Gestion',
                'campus_id' => $campusInsam->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Informatique',
                'code' => 'DEPT-INFO',
                'description' => 'Département d\'Informatique et Systèmes',
                'campus_id' => $campusInsam->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Génie Civil',
                'code' => 'DEPT-GECI',
                'description' => 'Département de Génie Civil et BTP',
                'campus_id' => $campusInsam->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Commerce International',
                'code' => 'DEPT-COMI',
                'description' => 'Département de Commerce International',
                'campus_id' => $campusInsam->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('departments')->insert($departments);

        $this->command->info('✅ ' . count($departments) . ' départements créés avec succès');
    }
}
