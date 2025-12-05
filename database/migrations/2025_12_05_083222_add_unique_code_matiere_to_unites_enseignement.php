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
            // Ajouter un index unique sur la combinaison code_ue + nom_matiere
            // Car plusieurs matières peuvent partager le même code UE
            $table->unique(['code_ue', 'nom_matiere'], 'unique_code_matiere');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->dropUnique('unique_code_matiere');
        });
    }
};
