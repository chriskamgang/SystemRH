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
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les colonnes spécifiques aux étudiants si elles n'existent pas
            if (!Schema::hasColumn('users', 'specialite')) {
                $table->string('specialite')->nullable()->after('employee_type');
            }
            if (!Schema::hasColumn('users', 'niveau')) {
                $table->string('niveau')->nullable()->after('specialite');
            }
        });

        // Modifier l'ENUM pour inclure 'etudiant'
        // Note: SQLite ne supporte pas MODIFY COLUMN, donc on ne fait rien pour SQLite (qui traite l'ENUM comme du texte)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'semi_permanent', 'administratif', 'technique', 'direction', 'etudiant') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'specialite')) {
                $table->dropColumn('specialite');
            }
            if (Schema::hasColumn('users', 'niveau')) {
                $table->dropColumn('niveau');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN employee_type ENUM('enseignant_titulaire', 'enseignant_vacataire', 'semi_permanent', 'administratif', 'technique', 'direction') NULL");
        }
    }
};
