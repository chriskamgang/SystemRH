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
        Schema::create('user_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('Campus principal de l\'employé');
            $table->timestamps();

            $table->unique(['user_id', 'campus_id']);
            $table->index('user_id');
            $table->index('campus_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_campus');
    }
};
