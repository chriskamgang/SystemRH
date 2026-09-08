<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recharge d'un pass deja actif : l'etudiant ajoute des trajets a un
        // titre de meme nature. Ces trajets ne sont acquis qu'une fois payes,
        // mais le pass doit rester utilisable entre-temps pour ceux qu'il a
        // deja regles — d'ou un compteur distinct.
        Schema::table('abonnements', function (Blueprint $table) {
            $table->unsignedSmallInteger('trajets_en_attente')->default(0)->after('trajets_restants');
            $table->unsignedInteger('montant_du_fcfa')->default(0)->after('montant_paye_fcfa');
        });
    }

    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropColumn(['trajets_en_attente', 'montant_du_fcfa']);
        });
    }
};
