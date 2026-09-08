<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis des etudiants sur les cours qu'ils suivent.
 *
 * L'evaluation porte sur l'unite d'enseignement et non sur la seance : un
 * etudiant juge un cours sur l'ensemble du semestre, pas une heure isolee.
 * D'ou la contrainte d'unicite — un avis par etudiant et par UE, revisable
 * tant que le semestre dure, plutot qu'un flot de notes repetees dont la
 * moyenne ne voudrait plus rien dire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations_cours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unite_enseignement_id')
                ->constrained('unites_enseignement')
                ->cascadeOnDelete();

            // De 1 a 5. Le `unsignedTinyInteger` suffit et refuse d'emblee
            // les valeurs negatives.
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();

            $table->timestamps();

            // Un seul avis par etudiant et par cours : le second passage
            // met a jour le premier.
            $table->unique(['user_id', 'unite_enseignement_id'], 'evaluation_unique_par_etudiant');

            // Les moyennes se lisent par cours.
            $table->index('unite_enseignement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations_cours');
    }
};
