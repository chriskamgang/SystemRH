<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN type_ue ENUM('specialite', 'tronc_commun', 'tronc_commun_general', 'tronc_commun_partiel', 'matiere') DEFAULT 'specialite'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN type_ue ENUM('specialite', 'tronc_commun', 'tronc_commun_general', 'tronc_commun_partiel') DEFAULT 'specialite'");
    }
};
