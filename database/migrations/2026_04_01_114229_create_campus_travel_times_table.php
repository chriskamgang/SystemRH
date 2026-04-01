<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_travel_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_from_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('campus_to_id')->constrained('campuses')->onDelete('cascade');
            $table->integer('travel_minutes')->default(30);
            $table->timestamps();

            $table->unique(['campus_from_id', 'campus_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_travel_times');
    }
};
