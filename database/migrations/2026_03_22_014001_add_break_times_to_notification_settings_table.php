<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->time('break_start_time')->default('12:00:00')->after('temporary_time');
            $table->time('break_end_time')->default('13:00:00')->after('break_start_time');
            $table->boolean('break_enabled')->default(true)->after('break_end_time');
        });

        // Mettre à jour l'heure de fin de travail à 18h
        DB::table('settings')->where('key_name', 'morning_end_time')->update(['value' => '18:00']);
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['break_start_time', 'break_end_time', 'break_enabled']);
        });

        DB::table('settings')->where('key_name', 'morning_end_time')->update(['value' => '17:00']);
    }
};
