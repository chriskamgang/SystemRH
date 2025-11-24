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
        Schema::create('device_usage', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('usage_date');
            $table->string('device_model')->nullable();
            $table->string('device_os')->nullable();
            $table->timestamps();

            // Index pour rechercher rapidement
            $table->index(['device_id', 'usage_date']);
            $table->unique(['device_id', 'usage_date']); // Un device = un user par jour
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_usage');
    }
};
