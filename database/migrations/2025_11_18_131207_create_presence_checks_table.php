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
        Schema::create('presence_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Moment de la vérification
            $table->timestamp('check_time')->useCurrent();

            // Réponse de l'employé
            $table->enum('response', ['present', 'absent', 'no_response'])->default('no_response');
            $table->timestamp('response_time')->nullable();

            // Géolocalisation au moment de la réponse
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_in_zone')->nullable();

            // Notification
            $table->boolean('notification_sent')->default(false);
            $table->unsignedBigInteger('notification_id')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('check_time');
            $table->index('response');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_checks');
    }
};
