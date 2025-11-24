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
        Schema::create('tardiness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');

            // Informations sur le retard
            $table->date('date');
            $table->time('expected_time')->comment('Heure attendue (avec tolérance)');
            $table->time('actual_time')->comment('Heure réelle d\'arrivée');
            $table->integer('late_minutes');

            // Justification
            $table->boolean('is_justified')->default(false);
            $table->text('justification')->nullable();
            $table->foreignId('justified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('justified_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('date');
            $table->index('is_justified');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tardiness');
    }
};
