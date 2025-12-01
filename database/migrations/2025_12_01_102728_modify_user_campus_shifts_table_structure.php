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
        // Sauvegarder les données existantes
        $existingShifts = DB::table('user_campus_shifts')->get();

        // Supprimer et recréer la table
        Schema::dropIfExists('user_campus_shifts');

        Schema::create('user_campus_shifts', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');

            // Type de shift (morning, evening, full_day)
            $table->enum('shift_type', ['morning', 'evening', 'full_day'])->default('morning');

            // Horaires de travail
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            // Index unique pour éviter les doublons user + campus + shift_type
            $table->unique(['user_id', 'campus_id', 'shift_type'], 'user_campus_shift_unique');
        });

        // Restaurer les données en convertissant l'ancien format
        foreach ($existingShifts as $shift) {
            if ($shift->works_morning) {
                DB::table('user_campus_shifts')->insert([
                    'user_id' => $shift->user_id,
                    'campus_id' => $shift->campus_id,
                    'shift_type' => 'morning',
                    'start_time' => '08:00:00',
                    'end_time' => '13:00:00',
                    'created_at' => $shift->created_at,
                    'updated_at' => $shift->updated_at,
                ]);
            }

            if ($shift->works_evening) {
                DB::table('user_campus_shifts')->insert([
                    'user_id' => $shift->user_id,
                    'campus_id' => $shift->campus_id,
                    'shift_type' => 'evening',
                    'start_time' => '14:00:00',
                    'end_time' => '19:00:00',
                    'created_at' => $shift->created_at,
                    'updated_at' => $shift->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_campus_shifts', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte unique
            $table->dropUnique('user_campus_shift_unique');

            // Supprimer les nouvelles colonnes
            $table->dropColumn(['shift_type', 'start_time', 'end_time']);

            // Restaurer les anciennes colonnes
            $table->boolean('works_morning')->default(false);
            $table->boolean('works_evening')->default(false);

            // Restaurer l'ancienne contrainte unique
            $table->unique(['user_id', 'campus_id']);
        });
    }
};
