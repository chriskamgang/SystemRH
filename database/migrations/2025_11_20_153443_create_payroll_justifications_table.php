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
        Schema::create('payroll_justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Admin qui crée la justification
            $table->integer('year');
            $table->integer('month');
            $table->decimal('days_justified', 5, 2)->default(0); // Nombre de jours justifiés
            $table->integer('late_minutes_justified')->default(0); // Minutes de retard justifiées
            $table->text('reason'); // Motif de justification
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();

            // Index pour recherche rapide
            $table->index(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_justifications');
    }
};
