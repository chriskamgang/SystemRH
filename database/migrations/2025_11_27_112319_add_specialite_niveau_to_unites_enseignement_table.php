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
            $table->string('specialite')->nullable()->after('semestre')
                ->comment('Spécialité/Filière (ex: Informatique, Gestion, Droit)');
            $table->string('niveau')->nullable()->after('specialite')
                ->comment('Niveau d\'études (ex: Licence 1, Master 2)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->dropColumn(['specialite', 'niveau']);
        });
    }
};
