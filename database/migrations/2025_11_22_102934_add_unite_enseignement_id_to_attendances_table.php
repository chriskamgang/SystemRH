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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('unite_enseignement_id')->nullable()
                ->after('campus_id')
                ->constrained('unites_enseignement')
                ->onDelete('set null')
                ->comment('UE enseignée lors de ce pointage (pour vacataires enseignants)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['unite_enseignement_id']);
            $table->dropColumn('unite_enseignement_id');
        });
    }
};
