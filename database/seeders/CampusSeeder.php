<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = [
            [
                'name' => 'INSAM Bafoussam',
                'code' => 'INSAM-BFM',
                'address' => 'Bafoussam, Région de l\'Ouest, Cameroun',
                'description' => 'Institut Supérieur d\'Architecture et de Management - Campus de Bafoussam',
                'latitude' => 5.4781,
                'longitude' => 10.4178,
                'radius' => 200,
                'start_time' => '07:30:00',
                'end_time' => '17:30:00',
                'late_tolerance' => 15,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campuses')->insert($campuses);

        $this->command->info('✅ ' . count($campuses) . ' campus créés avec succès');
    }
}
