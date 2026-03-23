<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->decimal('taux_horaire', 10, 2)->nullable()->after('niveau');
        });
    }

    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->dropColumn('taux_horaire');
        });
    }
};
