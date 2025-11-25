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
        Schema::table('unites_enseignement', function (Blueprint $table) {
            // Renommer la colonne pour être plus générique
            // Elle peut maintenant référencer des vacataires OU des semi-permanents
            $table->renameColumn('vacataire_id', 'enseignant_id');
        });

        // Mettre à jour le commentaire de la colonne
        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN enseignant_id BIGINT UNSIGNED NOT NULL COMMENT 'Enseignant (vacataire ou semi-permanent) concerné'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->renameColumn('enseignant_id', 'vacataire_id');
        });

        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN vacataire_id BIGINT UNSIGNED NOT NULL COMMENT 'Enseignant vacataire concerné'");
    }
};
