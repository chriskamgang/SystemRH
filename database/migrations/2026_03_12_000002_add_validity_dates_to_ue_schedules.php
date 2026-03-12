<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ue_schedules', function (Blueprint $table) {
            $table->date('date_debut_validite')->nullable()->after('salle');
            $table->date('date_fin_validite')->nullable()->after('date_debut_validite');
        });
    }

    public function down(): void
    {
        Schema::table('ue_schedules', function (Blueprint $table) {
            $table->dropColumn(['date_debut_validite', 'date_fin_validite']);
        });
    }
};
