<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key_name' => 'notification_time_1',
                'value' => '10:00',
                'type' => 'string',
                'description' => 'Heure de la 1ère notification (matin)',
            ],
            [
                'key_name' => 'notification_time_2',
                'value' => '15:00',
                'type' => 'string',
                'description' => 'Heure de la 2ème notification (après-midi)',
            ],
            [
                'key_name' => 'notification_time_3',
                'value' => '18:30',
                'type' => 'string',
                'description' => 'Heure de la 3ème notification (soir 1)',
            ],
            [
                'key_name' => 'notification_time_4',
                'value' => '20:45',
                'type' => 'string',
                'description' => 'Heure de la 4ème notification (soir 2)',
            ],
            [
                'key_name' => 'notification_time_5',
                'value' => '21:00',
                'type' => 'string',
                'description' => 'Heure de la 5ème notification (soir 3)',
            ],
            [
                'key_name' => 'notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Activer/Désactiver les notifications de présence',
            ],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }

        $this->command->info('✅ Paramètres de notification créés avec succès !');
    }
}
