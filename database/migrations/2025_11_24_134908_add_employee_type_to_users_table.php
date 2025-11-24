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
        Schema::table('users', function (Blueprint $table) {
            // Vérifier si la colonne employee_type n'existe pas déjà
            if (!Schema::hasColumn('users', 'new_employee_type')) {
                // Ajouter le champ new_employee_type (nouveau champ pour le système de plages)
                // - teaching_permanent: Permanent enseignant (donne cours, peut travailler matin/soir, 7j/7)
                // - administrative_permanent: Permanent administratif (pas de cours, matin seulement, Lun-Sam)
                // - vacataire: Vacataire (déjà existant)
                $table->enum('new_employee_type', ['teaching_permanent', 'administrative_permanent', 'vacataire'])
                      ->nullable()
                      ->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'new_employee_type')) {
                $table->dropColumn('new_employee_type');
            }
        });
    }
};
