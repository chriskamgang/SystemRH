<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lignes de ramassage (ex. "Entree de ville", "Kango", "Tougan", "Mairie").
        Schema::create('lignes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('nom');
            $table->text('description')->nullable();

            // Duree de reference d'un tour, sert au controle de coherence temporelle (regle 3.4).
            $table->unsignedSmallInteger('duree_trajet_minutes')->default(30);
            $table->unsignedSmallInteger('tours_prevus_par_jour')->default(4);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Points de ramassage le long d'une ligne + le campus (terminus).
        Schema::create('arrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_id')->constrained('lignes')->cascadeOnDelete();
            $table->string('nom');
            $table->unsignedSmallInteger('ordre')->default(1);

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Rayon de validation geographique des boutons de pointage (regle 3.4).
            $table->unsignedSmallInteger('rayon_validation_metres')->default(150);

            // Un arret de type campus marque la fin de tour.
            $table->boolean('est_campus')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['ligne_id', 'ordre']);
            $table->index(['ligne_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrets');
        Schema::dropIfExists('lignes');
    }
};
