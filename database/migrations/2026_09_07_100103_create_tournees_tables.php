<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une "tournee" = un tour complet (arret de ramassage -> campus). Cycle 3.2.
        Schema::create('tournees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affectation_id')->constrained('affectations')->cascadeOnDelete();
            $table->foreignId('arret_id')->constrained('arrets')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_tour')->default(1);

            // en_attente | embarquement | en_transit | termine | annule
            $table->string('statut', 20)->default('en_attente')->index();

            // Horodatage des 4 evenements du cycle de pointage.
            $table->timestamp('demarre_le')->nullable();       // 1. demarrage service
            $table->timestamp('arrive_point_le')->nullable();  // 2. arrive au point
            $table->timestamp('depart_le')->nullable();        // 4. depart vers campus
            $table->timestamp('termine_le')->nullable();       // 5. arrivee campus

            // 3. Effectif reel embarque (comptage anti-fraude).
            $table->unsignedSmallInteger('effectif_embarque')->nullable();

            // Duree reelle vs duree de reference -> anomalie de coherence temporelle (3.4).
            $table->unsignedSmallInteger('duree_reelle_minutes')->nullable();
            $table->boolean('anomalie_duree')->default(false);
            $table->text('note_anomalie')->nullable();

            $table->timestamps();

            $table->unique(['affectation_id', 'numero_tour']);
            $table->index(['statut', 'created_at']);
        });

        // Journal d'audit de chaque appui sur un bouton d'etape, avec la position GPS constatee.
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournee_id')->constrained('tournees')->cascadeOnDelete();

            // demarrage | arrive_point | effectif | depart | termine
            $table->string('etape', 20)->index();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Distance constatee a l'arret de reference au moment de l'appui.
            $table->unsignedInteger('distance_metres')->nullable();
            $table->boolean('dans_zone')->default(true);

            $table->unsignedSmallInteger('effectif')->nullable();
            $table->timestamp('pointe_le');
            $table->timestamps();

            $table->index(['tournee_id', 'etape']);
        });

        // Trace GPS du bus, alimentee par l'app chauffeur, diffusee aux etudiants (3.1).
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained('bus')->cascadeOnDelete();
            $table->foreignId('tournee_id')->nullable()->constrained('tournees')->nullOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('vitesse_kmh')->nullable();
            $table->unsignedSmallInteger('cap_degres')->nullable();
            $table->timestamp('releve_le')->index();
            $table->timestamps();

            $table->index(['bus_id', 'releve_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('pointages');
        Schema::dropIfExists('tournees');
    }
};
