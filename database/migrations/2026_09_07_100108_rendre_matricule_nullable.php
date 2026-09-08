<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le matricule devient facultatif (US-10).
     *
     * L'etudiant qui ne le retrouve pas a l'inscription coche « j'ai
     * oublie mon matricule » et le renseigne plus tard depuis son profil.
     * L'unicite reste garantie, PostgreSQL autorisant plusieurs NULL dans
     * un index unique.
     */
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->string('matricule_insam', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->string('matricule_insam', 50)->nullable(false)->change();
        });
    }
};
