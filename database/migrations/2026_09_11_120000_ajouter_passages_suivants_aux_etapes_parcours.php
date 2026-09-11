<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mémorise les passages suivants du bus à une même étape.
     *
     * Les feuilles de service notent deux à trois passages par arrêt : le
     * bus fait plusieurs tours dans la matinée. `minutes_depuis_depart`
     * ne décrivait que le premier, ce qui laissait l'étudiant du second
     * tour sans horaire.
     *
     * Les passages sont stockés en heures absolues plutôt qu'en décalage,
     * parce qu'un tour ne repart pas à intervalle fixe : le second tour
     * saute parfois les premiers arrêts, et son écart au départ varie
     * d'un arrêt à l'autre. Un tableau JSON évite par ailleurs d'ajouter
     * une colonne à chaque tour supplémentaire.
     */
    public function up(): void
    {
        Schema::table('etapes_parcours', function (Blueprint $table) {
            // ["07:15", "08:00"] — les tours au-delà du premier, dans
            // l'ordre. Vide quand la feuille ne note qu'un passage.
            $table->json('passages_suivants')->nullable()->after('minutes_depuis_depart');
        });
    }

    public function down(): void
    {
        Schema::table('etapes_parcours', function (Blueprint $table) {
            $table->dropColumn('passages_suivants');
        });
    }
};
