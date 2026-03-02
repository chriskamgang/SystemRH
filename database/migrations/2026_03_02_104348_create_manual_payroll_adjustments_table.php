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
        Schema::create('manual_payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('applied_by')->constrained('users')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');

            // Données de calcul
            $table->decimal('salaire_mensuel', 15, 2);
            $table->decimal('jours_travailles', 5, 2);
            $table->decimal('jours_total', 5, 2);
            $table->integer('heures_retard')->default(0);
            $table->integer('minutes_retard')->default(0);
            $table->decimal('prime', 15, 2)->default(0);
            $table->decimal('deduction_manuelle', 15, 2)->default(0);

            // Résultats du calcul
            $table->decimal('salaire_journalier', 15, 2);
            $table->decimal('salaire_brut', 15, 2);
            $table->decimal('penalite_retard', 15, 2)->default(0);
            $table->decimal('salaire_net', 15, 2);
            $table->decimal('montant_perdu', 15, 2);
            $table->decimal('pourcentage_presence', 5, 2);

            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, cancelled
            $table->timestamps();

            // Index pour recherche rapide
            $table->index(['user_id', 'year', 'month']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_payroll_adjustments');
    }
};
