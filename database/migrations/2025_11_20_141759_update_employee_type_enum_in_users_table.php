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
        // Ajouter 'semi_permanent' aux valeurs possibles de employee_type
        \DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'semi_permanent', 'administratif', 'technique', 'direction') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'semi_permanent' des valeurs possibles
        \DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'administratif', 'technique', 'direction') NULL");
    }
};
