<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace l'envoi du rappel d'expiration d'un pass (3.1).
 *
 * Sans cette marque, la tache quotidienne repeterait le meme rappel chaque
 * jour du seuil, jusqu'a l'echeance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->timestamp('rappel_expiration_envoye_le')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropColumn('rappel_expiration_envoye_le');
        });
    }
};
