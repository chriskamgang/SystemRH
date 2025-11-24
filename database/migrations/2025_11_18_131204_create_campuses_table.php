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
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();

            // Informations générales
            $table->string('name', 150);
            $table->string('code', 20)->unique()->nullable();
            $table->text('address');
            $table->text('description')->nullable();

            // Géolocalisation
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius')->default(100)->comment('Rayon en mètres');

            // Configuration horaires
            $table->time('start_time')->default('08:00:00');
            $table->time('end_time')->default('17:00:00');
            $table->integer('late_tolerance')->default(15)->comment('Tolérance retard en minutes');

            // Jours de travail (format JSON)
            $table->json('working_days')->default('["monday", "tuesday", "wednesday", "thursday", "friday"]');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
            $table->index(['latitude', 'longitude']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campuses');
    }
};
