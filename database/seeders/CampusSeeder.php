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
                'name' => 'Campus A - Sciences',
                'code' => 'CAM-SCI',
                'address' => 'Rue de la Science, Libreville, Gabon',
                'description' => 'Campus dédié aux sciences exactes et naturelles',
                'latitude' => 0.4162,
                'longitude' => 9.4673,
                'radius' => 150,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance' => 10,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campus B - Lettres',
                'code' => 'CAM-LET',
                'address' => 'Avenue des Lettres, Libreville, Gabon',
                'description' => 'Campus des lettres et sciences humaines',
                'latitude' => 0.4250,
                'longitude' => 9.4520,
                'radius' => 200,
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'late_tolerance' => 15,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campus C - Droit',
                'code' => 'CAM-DRT',
                'address' => 'Boulevard du Droit, Libreville, Gabon',
                'description' => 'Campus de droit et sciences politiques',
                'latitude' => 0.4100,
                'longitude' => 9.4800,
                'radius' => 100,
                'start_time' => '07:30:00',
                'end_time' => '16:30:00',
                'late_tolerance' => 20,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campus D - Médecine',
                'code' => 'CAM-MED',
                'address' => 'Route de la Santé, Libreville, Gabon',
                'description' => 'Campus de médecine et sciences de la santé',
                'latitude' => 0.4300,
                'longitude' => 9.4600,
                'radius' => 250,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'late_tolerance' => 10,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campus E - Technologie',
                'code' => 'CAM-TECH',
                'address' => 'Zone Technologique, Libreville, Gabon',
                'description' => 'Campus des technologies et ingénierie',
                'latitude' => 0.4050,
                'longitude' => 9.4900,
                'radius' => 300,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance' => 15,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campus F - Commerce',
                'code' => 'CAM-COM',
                'address' => 'Quartier des Affaires, Libreville, Gabon',
                'description' => 'Campus des sciences économiques et de gestion',
                'latitude' => 0.4200,
                'longitude' => 9.4750,
                'radius' => 150,
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'late_tolerance' => 15,
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campuses')->insert($campuses);

        $this->command->info('✅ ' . count($campuses) . ' campus créés avec succès');
    }
}
