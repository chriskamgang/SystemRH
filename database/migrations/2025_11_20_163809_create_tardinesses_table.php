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
        Schema::create('tardinesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('scheduled_time'); // Heure prévue de début
            $table->time('actual_time'); // Heure réelle d'arrivée
            $table->decimal('late_minutes', 8, 2)->default(0);
            $table->enum('status', ['pending', 'justified', 'unjustified'])->default('pending');
            $table->text('justification')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['user_id', 'date']);
            $table->index('campus_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tardinesses');
    }
};
