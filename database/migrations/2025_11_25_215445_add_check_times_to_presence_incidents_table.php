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
        Schema::table('presence_incidents', function (Blueprint $table) {
            // Ajouter les champs pour les pointages UE (check-in/check-out)
            $table->time('check_in_time')->nullable()->after('unite_enseignement_id')
                ->comment('Heure de check-in pour l\'UE');
            $table->time('check_out_time')->nullable()->after('check_in_time')
                ->comment('Heure de check-out pour l\'UE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presence_incidents', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });
    }
};
