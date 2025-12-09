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
        Schema::table('vacataire_payments', function (Blueprint $table) {
            $table->decimal('impot_retenu', 10, 2)->default(0)->after('late_penalty')->comment('Impôt retenu à la source (5% du montant brut)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacataire_payments', function (Blueprint $table) {
            $table->dropColumn('impot_retenu');
        });
    }
};
