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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            // Informations du semestre
            $table->string('name')->comment('Ex: Semestre 1, Semestre 2');
            $table->string('code', 20)->unique()->comment('Ex: S1-2024-2025, S2-2024-2025');
            $table->string('annee_academique', 20)->comment('Ex: 2024-2025');
            $table->integer('numero_semestre')->comment('1 ou 2');

            // Dates
            $table->date('date_debut')->comment('Date de début du semestre');
            $table->date('date_fin')->comment('Date de fin du semestre');

            // Statut
            $table->boolean('is_active')->default(false)->comment('Semestre actif/en cours');

            // Description optionnelle
            $table->text('description')->nullable();

            $table->timestamps();

            // Index
            $table->index('annee_academique');
            $table->index('is_active');
            $table->index(['annee_academique', 'numero_semestre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
