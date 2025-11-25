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
            [
                'key_name' => 'presence_check_hours',
                'value' => '["10:00", "15:00", "18:30", "20:45", "21:00"]',
                'type' => 'json',
                'description' => 'Heures d\'envoi des notifications de vérification de présence',
            ],
            [
                'key_name' => 'geofence_notification_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer les notifications d\'entrée en zone de campus',
            ],
            [
                'key_name' => 'geofence_notification_cooldown_minutes',
                'value' => '360',
                'type' => 'integer',
                'description' => 'Durée minimum entre deux notifications de géofencing pour le même campus (en minutes)',
            ],
            [
                'key_name' => 'shift_separator_time',
                'value' => '17:00',
                'type' => 'string',
                'description' => 'Heure de séparation entre plage matin et soir',
            ],
            [
                'key_name' => 'morning_start_time',
                'value' => '08:15',
                'type' => 'string',
                'description' => 'Heure de début de la plage matin',
            ],
            [
                'key_name' => 'morning_end_time',
                'value' => '17:00',
                'type' => 'string',
                'description' => 'Heure de fin de la plage matin',
            ],
            [
                'key_name' => 'evening_start_time',
                'value' => '17:30',
                'type' => 'string',
                'description' => 'Heure de début de la plage soir',
            ],
            [
                'key_name' => 'evening_end_time',
                'value' => '21:00',
                'type' => 'string',
                'description' => 'Heure de fin de la plage soir',
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
