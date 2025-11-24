<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key_name' => 'presence_check_interval',
                'value' => '180',
                'type' => 'integer',
                'description' => 'Intervalle de vérification de présence en minutes (180 = 3h)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'auto_checkout_time',
                'value' => '19:00:00',
                'type' => 'string',
                'description' => 'Heure de check-out automatique si oublié',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'app_version_min',
                'value' => '1.0.0',
                'type' => 'string',
                'description' => 'Version minimale de l\'application mobile requise',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'system_active',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Système actif ou en maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'max_check_in_distance',
                'value' => '500',
                'type' => 'integer',
                'description' => 'Distance maximale en mètres pour un check-in valide',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'notification_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer les notifications push',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'app_name',
                'value' => 'Système de Pointage Géolocalisé',
                'type' => 'string',
                'description' => 'Nom de l\'application',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'company_name',
                'value' => 'Université de Libreville',
                'type' => 'string',
                'description' => 'Nom de l\'organisation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('settings')->insert($settings);

        $this->command->info('✅ ' . count($settings) . ' paramètres système créés avec succès');
    }
}
