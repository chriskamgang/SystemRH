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
            $table->decimal('heures_effectuees_validees', 8, 2)->default(0)->after('volume_horaire_total');
            $table->timestamp('derniere_mise_a_jour_heures')->nullable()->after('heures_effectuees_validees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->dropColumn(['heures_effectuees_validees', 'derniere_mise_a_jour_heures']);
        });
    }
};
