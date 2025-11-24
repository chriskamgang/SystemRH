<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;

class NotificationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NotificationSetting::create([
            'permanent_semi_permanent_time' => '13:00:00',
            'temporary_time' => '14:00:00',
            'response_delay_minutes' => 45,
            'penalty_hours' => 1.00,
            'is_active' => true,
        ]);

        $this->command->info('✓ Paramètres de notifications créés avec succès');
    }
}
