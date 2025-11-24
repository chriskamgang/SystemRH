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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 12, 2); // Montant total du prêt
            $table->decimal('monthly_amount', 12, 2); // Montant à déduire chaque mois
            $table->decimal('amount_paid', 12, 2)->default(0); // Montant déjà remboursé
            $table->date('start_date'); // Date de début des déductions
            $table->text('reason')->nullable(); // Motif du prêt
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Admin qui a créé
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin qui a marqué terminé
            $table->timestamp('completed_at')->nullable(); // Date de fin
            $table->timestamps();

            // Index pour performances
            $table->index(['user_id', 'status']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
