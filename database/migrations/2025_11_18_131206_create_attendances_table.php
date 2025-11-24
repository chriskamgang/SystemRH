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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Type de pointage
            $table->enum('type', ['check-in', 'check-out']);

            // Date et heure du pointage
            $table->timestamp('timestamp')->useCurrent();

            // Géolocalisation au moment du pointage
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable()->comment('Précision GPS en mètres');

            // Détection de retard
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);

            // Informations supplémentaires
            $table->json('device_info')->nullable();
            $table->text('notes')->nullable();

            // Statut
            $table->enum('status', ['valid', 'invalid', 'disputed'])->default('valid');

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('type');
            $table->index('timestamp');
            $table->index('is_late');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
