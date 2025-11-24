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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('monthly_salary', 12, 2); // Salaire mensuel de base
            $table->decimal('working_days', 5, 2); // Jours ouvrables du mois
            $table->decimal('days_worked', 5, 2)->default(0); // Jours travaillés
            $table->decimal('days_not_worked', 5, 2)->default(0); // Jours non travaillés
            $table->decimal('days_justified', 5, 2)->default(0); // Jours justifiés
            $table->integer('total_late_minutes')->default(0); // Retard total en minutes
            $table->integer('late_minutes_justified')->default(0); // Minutes justifiées
            $table->decimal('late_penalty_amount', 12, 2)->default(0); // Montant pénalité retards
            $table->decimal('absence_deduction', 12, 2)->default(0); // Déduction absences
            $table->decimal('gross_salary', 12, 2); // Salaire brut
            $table->decimal('total_deductions', 12, 2)->default(0); // Total déductions
            $table->decimal('net_salary', 12, 2); // Salaire net
            $table->enum('status', ['draft', 'pending', 'approved', 'paid'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('payroll_records');
    }
};
