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
        Schema::create('geofence_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->timestamp('sent_at');
            $table->enum('action_taken', ['clicked', 'ignored', 'pending'])->default('pending');
            $table->timestamp('action_at')->nullable();
            $table->text('device_info')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes de cooldown
            $table->index(['user_id', 'campus_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofence_notifications');
    }
};
