<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Horaires personnalisés pour certains types d'employés (agents, gardiens, etc.)
            // Ces horaires prennent priorité sur les horaires du campus
            $table->time('custom_start_time')->nullable()->after('jours_travail')
                ->comment('Heure de début personnalisée (ex: pour gardiens de nuit)');
            $table->time('custom_end_time')->nullable()->after('custom_start_time')
                ->comment('Heure de fin personnalisée');
            $table->integer('custom_late_tolerance')->nullable()->after('custom_end_time')
                ->comment('Tolérance de retard personnalisée en minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['custom_start_time', 'custom_end_time', 'custom_late_tolerance']);
        });
    }
};
