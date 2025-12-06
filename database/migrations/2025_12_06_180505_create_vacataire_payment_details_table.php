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
        Schema::create('vacataire_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('vacataire_payments')->onDelete('cascade');
            $table->foreignId('unite_enseignement_id')->nullable()->constrained('unites_enseignement')->onDelete('set null');
            $table->string('code_ue', 50); // Copie pour historique
            $table->string('nom_matiere', 255); // Copie pour historique
            $table->decimal('heures_saisies', 8, 2); // Heures effectuées ce mois
            $table->decimal('taux_horaire', 10, 2); // Taux au moment du paiement
            $table->decimal('montant', 12, 2); // heures_saisies × taux_horaire
            $table->text('notes')->nullable(); // Remarques éventuelles
            $table->timestamps();

            // Index pour recherche rapide
            $table->index('payment_id');
            $table->index('unite_enseignement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacataire_payment_details');
    }
};
