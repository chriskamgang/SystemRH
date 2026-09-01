<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modifier l'enum pour ajouter les nouveaux types
        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN type_ue ENUM('specialite', 'tronc_commun', 'tronc_commun_general', 'tronc_commun_partiel') DEFAULT 'specialite'");

        // Migrer les anciennes valeurs tronc_commun vers tronc_commun_general
        DB::table('unites_enseignement')
            ->where('type_ue', 'tronc_commun')
            ->update(['type_ue' => 'tronc_commun_general']);
    }

    public function down(): void
    {
        DB::table('unites_enseignement')
            ->whereIn('type_ue', ['tronc_commun_general', 'tronc_commun_partiel'])
            ->update(['type_ue' => 'tronc_commun']);

        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN type_ue ENUM('specialite', 'tronc_commun') DEFAULT 'specialite'");
    }
};
