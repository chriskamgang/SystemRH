<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bascule les rattachements de `arrets` vers `lieux`.
     *
     * L'étudiant déclare son point de ramassage, stable d'un jour à
     * l'autre ; son campus, lui, change au gré de l'emploi du temps et
     * n'est donc pas enregistré dans son profil.
     */
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->foreignId('lieu_ramassage_id')
                ->nullable()
                ->after('ligne_id')
                ->constrained('lieux')
                ->nullOnDelete();
        });

        Schema::table('tournees', function (Blueprint $table) {
            // Sens réellement effectué, choisi au démarrage du service.
            $table->foreignId('parcours_id')
                ->nullable()
                ->after('affectation_id')
                ->constrained('parcours')
                ->nullOnDelete();

            // Lieu desservi par ce tour, en remplacement de `arret_id`.
            $table->foreignId('lieu_id')
                ->nullable()
                ->after('parcours_id')
                ->constrained('lieux')
                ->nullOnDelete();
        });

        Schema::table('pointages', function (Blueprint $table) {
            $table->foreignId('lieu_id')
                ->nullable()
                ->after('tournee_id')
                ->constrained('lieux')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pointages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lieu_id');
        });

        Schema::table('tournees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lieu_id');
            $table->dropConstrainedForeignId('parcours_id');
        });

        Schema::table('etudiants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lieu_ramassage_id');
        });
    }
};
