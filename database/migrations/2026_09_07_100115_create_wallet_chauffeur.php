<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallet du chauffeur : la recette de chaque trajet scanne y tombe
        // au fil de l'eau, et il declenche son retrait quand il veut, sans
        // attendre une cloture mensuelle.
        Schema::create('wallets_chauffeur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chauffeur_id')->unique()->constrained('chauffeurs')->cascadeOnDelete();

            // Solde disponible, en FCFA. Jamais negatif.
            $table->unsignedInteger('solde_fcfa')->default(0);

            // Somme immobilisee par un retrait en cours : elle a quitte le
            // solde sans etre encore confirmee par l'operateur.
            $table->unsignedInteger('solde_reserve_fcfa')->default(0);

            // Cumuls de reference, pour l'ecran du chauffeur.
            $table->unsignedInteger('total_percu_fcfa')->default(0);
            $table->unsignedInteger('total_retire_fcfa')->default(0);

            $table->timestamps();
        });

        // Journal de tous les mouvements : chaque ligne explique une variation
        // du solde, et leur somme doit toujours egaler ce solde.
        Schema::create('mouvements_wallet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets_chauffeur')->cascadeOnDelete();

            // trajet | prime | retrait | retrait_annule | ajustement
            $table->string('type', 30)->index();

            // Signe negatif pour ce qui sort du wallet : la somme des
            // mouvements reste une addition simple.
            $table->integer('montant_fcfa');

            $table->string('libelle');

            // Origine tracable du mouvement (trajet scanne, retrait, prime).
            $table->nullableMorphs('origine');

            // Solde apres application, fige au moment du mouvement : un
            // recalcul retroactif ne doit pas reecrire l'historique.
            $table->unsignedInteger('solde_apres_fcfa');

            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });

        // Retrait demande par le chauffeur depuis son wallet.
        Schema::create('retraits_chauffeur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets_chauffeur')->cascadeOnDelete();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();

            $table->unsignedInteger('montant_fcfa');
            $table->string('telephone', 20);
            $table->string('provider', 40)->nullable();

            // en_attente | en_cours | paye | echoue | annule
            $table->string('statut', 20)->default('en_attente')->index();

            $table->foreignId('transaction_kpay_id')->nullable()
                ->constrained('transactions_kpay')->nullOnDelete();

            $table->string('motif_echec')->nullable();
            $table->timestamp('demande_le');
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();

            $table->index(['chauffeur_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retraits_chauffeur');
        Schema::dropIfExists('mouvements_wallet');
        Schema::dropIfExists('wallets_chauffeur');
    }
};
