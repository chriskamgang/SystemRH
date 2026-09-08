<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grille tarifaire parametrable : ticket unitaire 500 FCFA, pass semaine 400 FCFA/jour (3.1).
        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();       // ticket_unitaire | pass_semaine
            $table->string('libelle');
            $table->unsignedInteger('montant_fcfa');

            // Nombre de jours couverts (1 pour un ticket, 5 pour le pass lundi-vendredi).
            $table->unsignedSmallInteger('jours_couverts')->default(1);
            $table->unsignedSmallInteger('trajets_par_jour')->default(2);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('tarif_id')->constrained('tarifs')->restrictOnDelete();

            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedInteger('montant_paye_fcfa');
            $table->unsignedSmallInteger('trajets_restants')->nullable();

            // en_attente | actif | expire | annule
            $table->string('statut', 20)->default('en_attente')->index();

            // especes | mobile_money | carte
            $table->string('moyen_paiement', 30)->nullable();
            $table->string('reference_paiement')->nullable();
            $table->timestamp('paye_le')->nullable();
            $table->timestamps();

            $table->index(['etudiant_id', 'statut']);
            $table->index(['date_debut', 'date_fin']);
        });

        // Un embarquement effectif consomme un trajet et alimente le comptage anti-fraude.
        Schema::create('trajets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('tournee_id')->constrained('tournees')->cascadeOnDelete();
            $table->foreignId('abonnement_id')->nullable()->constrained('abonnements')->nullOnDelete();
            $table->timestamp('embarque_le');
            $table->timestamps();

            $table->unique(['etudiant_id', 'tournee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trajets');
        Schema::dropIfExists('abonnements');
        Schema::dropIfExists('tarifs');
    }
};
