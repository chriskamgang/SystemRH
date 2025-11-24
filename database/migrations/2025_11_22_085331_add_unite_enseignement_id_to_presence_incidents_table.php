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
            $table->foreignId('unite_enseignement_id')->nullable()
                ->after('attendance_id')
                ->constrained('unites_enseignement')
                ->onDelete('set null')
                ->comment('UE enseignée lors de cet incident (pour vacataires enseignants)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presence_incidents', function (Blueprint $table) {
            $table->dropForeign(['unite_enseignement_id']);
            $table->dropColumn('unite_enseignement_id');
        });
    }
};
