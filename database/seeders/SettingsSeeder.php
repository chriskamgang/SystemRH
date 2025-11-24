<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key_name' => 'map_provider',
                'value' => 'openstreetmap',
                'type' => 'string',
                'description' => 'Fournisseur de cartes (openstreetmap ou google)',
            ],
            [
                'key_name' => 'google_maps_api_key',
                'value' => '',
                'type' => 'string',
                'description' => 'Clé API Google Maps (optionnel si OpenStreetMap est utilisé)',
            ],
            [
                'key_name' => 'app_name',
                'value' => 'Attendance System',
                'type' => 'string',
                'description' => 'Nom de l\'application',
            ],
            [
                'key_name' => 'timezone',
                'value' => 'Africa/Douala',
                'type' => 'string',
                'description' => 'Fuseau horaire',
            ],
            [
                'key_name' => 'default_late_tolerance',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Tolérance de retard par défaut (minutes)',
            ],
            [
                'key_name' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Mode maintenance',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }
    }
}
