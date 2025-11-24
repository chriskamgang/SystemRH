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
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Date de l'absence
            $table->date('date');

            // Type d'absence
            $table->enum('type', ['no_check_in', 'early_checkout', 'full_day']);

            // Justification
            $table->boolean('is_justified')->default(false);
            $table->text('justification')->nullable();
            $table->foreignId('justified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('justified_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('date');
            $table->index('type');
            $table->index('is_justified');

            $table->unique(['user_id', 'date']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
