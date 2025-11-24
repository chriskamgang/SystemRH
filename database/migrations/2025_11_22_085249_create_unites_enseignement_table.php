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
        Schema::create('unites_enseignement', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('vacataire_id')->constrained('users')->onDelete('cascade')
                ->comment('Enseignant vacataire concerné');

            // Informations de l'UE
            $table->string('code_ue', 50)->nullable()->comment('Code de l\'UE (ex: MTH101)');
            $table->string('nom_matiere')->comment('Nom de la matière enseignée');
            $table->decimal('volume_horaire_total', 5, 2)->comment('Nombre d\'heures total de l\'UE');

            // Statut
            $table->enum('statut', ['non_activee', 'activee'])->default('non_activee')
                ->comment('non_activee: attribuée mais pas encore active, activee: compte pour paiement');

            // Année académique et semestre
            $table->string('annee_academique', 20)->nullable()->comment('Ex: 2024-2025');
            $table->integer('semestre')->nullable()->comment('1 ou 2');

            // Dates et traçabilité
            $table->timestamp('date_attribution')->useCurrent()->comment('Quand l\'UE a été attribuée');
            $table->timestamp('date_activation')->nullable()->comment('Quand l\'UE a été activée');

            // Admin qui a créé et activé
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Admin qui a attribué l\'UE');
            $table->foreignId('activated_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Admin qui a activé l\'UE');

            $table->timestamps();

            // Index pour optimisation
            $table->index('vacataire_id');
            $table->index('statut');
            $table->index(['vacataire_id', 'statut']);
            $table->index('annee_academique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unites_enseignement');
    }
};
