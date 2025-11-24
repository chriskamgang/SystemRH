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
        Schema::create('vacataire_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('hourly_rate', 10, 2); // Taux horaire
            $table->decimal('days_worked', 5, 2)->default(0); // Jours travaillés
            $table->decimal('hours_worked', 8, 2)->default(0); // Heures travaillées
            $table->integer('total_late_minutes')->default(0); // Retard total en minutes
            $table->decimal('gross_amount', 12, 2)->default(0); // Montant brut
            $table->decimal('late_penalty', 12, 2)->default(0); // Pénalité retards
            $table->decimal('bonus', 12, 2)->default(0); // Bonus éventuels
            $table->decimal('net_amount', 12, 2)->default(0); // Montant net
            $table->enum('status', ['pending', 'validated', 'paid'])->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('vacataire_payments');
    }
};
