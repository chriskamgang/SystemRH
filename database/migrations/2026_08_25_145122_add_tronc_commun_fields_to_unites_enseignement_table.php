<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->enum('type_ue', ['specialite', 'tronc_commun'])->default('specialite')->after('niveau');
            $table->json('groupes')->nullable()->after('type_ue')
                ->comment('Specialites concernees pour les UE tronc commun, ex: ["CGE","BQ","MCV"]');
        });
    }

    public function down(): void
    {
        Schema::table('unites_enseignement', function (Blueprint $table) {
            $table->dropColumn(['type_ue', 'groupes']);
        });
    }
};
