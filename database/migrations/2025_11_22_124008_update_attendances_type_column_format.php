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
        // Étape 1 : Convertir la colonne ENUM en VARCHAR temporairement
        DB::statement("ALTER TABLE attendances MODIFY type VARCHAR(20) NOT NULL");

        // Étape 2 : Mettre à jour les données existantes
        DB::table('attendances')
            ->where('type', 'check_in')
            ->update(['type' => 'check-in']);

        DB::table('attendances')
            ->where('type', 'check_out')
            ->update(['type' => 'check-out']);

        // Étape 3 : Reconvertir en ENUM avec les nouvelles valeurs
        DB::statement("ALTER TABLE attendances MODIFY type ENUM('check-in', 'check-out') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Revenir à l'ancien format avec underscores
            $table->enum('type', ['check_in', 'check_out'])->change();
        });
    }
};
