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
        Schema::create('user_campus_shifts', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');

            // Plages horaires assignées
            $table->boolean('works_morning')->default(false); // Travaille le matin (8h15-17h)
            $table->boolean('works_evening')->default(false); // Travaille le soir (17h30-21h)

            $table->timestamps();

            // Index unique pour éviter les doublons
            $table->unique(['user_id', 'campus_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_campus_shifts');
    }
};
