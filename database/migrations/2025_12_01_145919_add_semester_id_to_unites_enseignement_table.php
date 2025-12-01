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
        Schema::table('unites_enseignement', function (Blueprint $table) {
            // Ajouter la colonne semester_id après semestre
            $table->foreignId('semester_id')->nullable()->after('semestre')->constrained('semesters')->onDelete('set null');

            // Ajouter un index pour améliorer les performances
            $table->index('semester_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            // Supprimer la clé étrangère et la colonne
            $table->dropForeign(['semester_id']);
            $table->dropIndex(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
};
