<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jetons FCM des appareils, pour la notification push (3.1 et 3.3).
 *
 * Un utilisateur peut ouvrir l'application sur plusieurs appareils ; un
 * jeton, lui, n'appartient qu'a un seul compte a la fois — il est donc
 * unique et se reattribue a la reconnexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appareils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_fcm', 255)->unique();
            $table->string('plateforme', 20)->nullable();
            $table->string('modele')->nullable();
            $table->timestamp('vu_le')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appareils');
    }
};
