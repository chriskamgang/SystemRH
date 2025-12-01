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

            // Type de shift (morning, evening, full_day)
            $table->enum('shift_type', ['morning', 'evening', 'full_day'])->default('morning');

            // Horaires de travail
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            // Index unique pour éviter les doublons user + campus + shift_type
            $table->unique(['user_id', 'campus_id', 'shift_type']);
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
