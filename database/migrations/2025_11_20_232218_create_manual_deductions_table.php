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
        Schema::create('manual_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->integer('month'); // 1-12
            $table->integer('year');
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->foreignId('applied_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['user_id', 'year', 'month']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_deductions');
    }
};
