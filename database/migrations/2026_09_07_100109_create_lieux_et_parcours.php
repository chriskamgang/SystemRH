<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sépare les lieux physiques de leur place dans un parcours.
     *
     * Un campus est desservi par plusieurs lignes : le décrire une fois
     * évite d'avoir à corriger ses coordonnées en autant d'exemplaires.
     * Une ligne compose ensuite son itinéraire en ordonnant ces lieux,
     * dans un sens donné — le matin vers le campus, le soir vers les
     * points de dépôt.
     */
    public function up(): void
    {
        // Lieux desservis : points de ramassage urbains et campus.
        Schema::create('lieux', function (Blueprint $table) {
            $table->id();
            $table->string('nom');

            // Repère parlant pour l'étudiant : « Face au marché ».
            $table->string('adresse')->nullable();

            // ramassage | campus — un même lieu peut servir aux deux, le
            // type dit seulement à quoi il sert d'ordinaire.
            $table->string('type', 20)->default('ramassage')->index();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Zone de validation des boutons de pointage (règle 3.4).
            $table->unsignedSmallInteger('rayon_validation_metres')->default(150);

            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['type', 'actif']);
        });

        // Itinéraire d'une ligne dans un sens donné.
        Schema::create('parcours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_id')->constrained('lignes')->cascadeOnDelete();

            // aller : ramassage -> campus. retour : campus -> dépôts.
            $table->string('sens', 10)->default('aller');

            $table->string('libelle')->nullable();

            // Heure indicative de départ, pour informer l'étudiant.
            $table->time('heure_depart')->nullable();

            $table->unsignedSmallInteger('duree_reference_minutes')->default(30);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            // Un seul parcours par sens et par ligne.
            $table->unique(['ligne_id', 'sens']);
        });

        // Position d'un lieu dans un parcours.
        Schema::create('etapes_parcours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcours_id')->constrained('parcours')->cascadeOnDelete();
            $table->foreignId('lieu_id')->constrained('lieux')->cascadeOnDelete();

            $table->unsignedSmallInteger('ordre')->default(1);

            // La dernière étape clôt le tour et incrémente le compteur.
            $table->boolean('est_terminus')->default(false);

            // Décalage indicatif depuis le départ, en minutes.
            $table->unsignedSmallInteger('minutes_depuis_depart')->nullable();

            $table->timestamps();

            $table->unique(['parcours_id', 'ordre']);
            $table->index(['parcours_id', 'lieu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes_parcours');
        Schema::dropIfExists('parcours');
        Schema::dropIfExists('lieux');
    }
};
