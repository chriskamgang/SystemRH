<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insérer les paramètres de configuration des plages horaires
        $settings = [
            // Horaires MATIN
            ['key_name' => 'morning_start_time', 'value' => '08:15', 'type' => 'string', 'description' => 'Heure de début du matin', 'created_at' => now(), 'updated_at' => now()],
            ['key_name' => 'morning_end_time', 'value' => '17:00', 'type' => 'string', 'description' => 'Heure de fin du matin', 'created_at' => now(), 'updated_at' => now()],

            // Horaires SOIR
            ['key_name' => 'evening_start_time', 'value' => '17:30', 'type' => 'string', 'description' => 'Heure de début du soir', 'created_at' => now(), 'updated_at' => now()],
            ['key_name' => 'evening_end_time', 'value' => '21:00', 'type' => 'string', 'description' => 'Heure de fin du soir', 'created_at' => now(), 'updated_at' => now()],

            // Heure de séparation entre matin et soir (pour détection automatique)
            ['key_name' => 'shift_separator_time', 'value' => '17:00', 'type' => 'string', 'description' => 'Heure de séparation entre matin et soir', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($settings as $setting) {
            // Insérer seulement si la clé n'existe pas déjà
            $exists = DB::table('settings')->where('key_name', $setting['key_name'])->exists();
            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les paramètres ajoutés
        DB::table('settings')->whereIn('key_name', [
            'morning_start_time',
            'morning_end_time',
            'evening_start_time',
            'evening_end_time',
            'shift_separator_time',
        ])->delete();
    }
};
