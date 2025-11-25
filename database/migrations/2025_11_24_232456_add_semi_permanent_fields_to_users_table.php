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
            // Champs spécifiques pour les semi-permanents
            $table->decimal('volume_horaire_hebdomadaire', 5, 2)->nullable()
                ->after('employee_type')
                ->comment('Volume horaire par semaine pour les semi-permanents');

            $table->json('jours_travail')->nullable()
                ->after('volume_horaire_hebdomadaire')
                ->comment('Jours de travail de la semaine (ex: ["lundi", "mercredi", "vendredi"])');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['volume_horaire_hebdomadaire', 'jours_travail']);
        });
    }
};
