<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les tours s'appuient désormais sur les lieux d'un parcours.
     *
     * La colonne `arret_id` reste en place le temps que d'éventuelles
     * données historiques soient reprises, mais elle n'est plus alimentée.
     */
    public function up(): void
    {
        Schema::table('tournees', function (Blueprint $table) {
            $table->foreignId('arret_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tournees', function (Blueprint $table) {
            $table->foreignId('arret_id')->nullable(false)->change();
        });
    }
};
