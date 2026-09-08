<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presence en ligne des chauffeurs (CDC 3.1 - suivi en direct).
 *
 * Un chauffeur diffuse sa position des l'ouverture de l'application, sans
 * attendre d'avoir demarre un tour : les etudiants voient ainsi le bus
 * stationne au depot comme le bus en circulation. La table porte l'etat
 * courant, une ligne par chauffeur, mise a jour a chaque ping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences_chauffeur', function (Blueprint $table) {
            $table->id();

            // Un chauffeur n'est en ligne qu'une fois : la ligne est
            // remplacee a chaque ping plutot qu'empilee.
            $table->foreignId('chauffeur_id')->unique()->constrained('chauffeurs')->cascadeOnDelete();

            // Bus conduit au moment du ping, tire de l'affectation du jour.
            // Nul si le chauffeur ouvre l'application sans etre affecte :
            // il est alors en ligne mais rien n'est diffuse aux etudiants.
            $table->foreignId('bus_id')->nullable()->constrained('bus')->nullOnDelete();
            $table->foreignId('tournee_id')->nullable()->constrained('tournees')->nullOnDelete();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('vitesse_kmh')->nullable();
            $table->unsignedSmallInteger('cap_degres')->nullable();

            // Dernier signe de vie. Au-dela du delai de grace, le chauffeur
            // est considere hors ligne sans qu'il ait eu a se deconnecter :
            // une application tuee ne previent personne.
            $table->timestamp('vu_le')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences_chauffeur');
    }
};
