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
        // Note: SQLite doesn't support MODIFY COLUMN or ENUM constraints
        // For SQLite, the string values are already flexible
        if (\DB::getDriverName() !== 'sqlite') {
            \DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'semi_permanent', 'administratif', 'technique', 'direction') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'semi_permanent' des valeurs possibles
        if (\DB::getDriverName() !== 'sqlite') {
            \DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'administratif', 'technique', 'direction') NULL");
        }
    }
};
