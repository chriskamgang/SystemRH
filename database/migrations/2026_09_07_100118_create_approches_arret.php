<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace des notifications « le bus approche » deja envoyees (CDC 3.1).
 *
 * La position remonte toutes les quinze secondes : sans cette trace, un bus
 * qui patiente a proximite d'un arret noierait les etudiants sous une alerte
 * par ping. Une ligne par tour et par lieu vaut donc verrou d'unicite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approches_arret', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournee_id')->constrained('tournees')->cascadeOnDelete();
            $table->foreignId('lieu_id')->constrained('lieux')->cascadeOnDelete();
            $table->unsignedInteger('distance_metres');
            $table->timestamp('notifie_le');
            $table->timestamps();

            // Le verrou d'idempotence : une seule alerte par arret et par tour.
            $table->unique(['tournee_id', 'lieu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approches_arret');
    }
};
