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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();

            // Configuration des heures d'envoi des notifications de présence
            $table->time('permanent_semi_permanent_time')->default('13:00:00')
                ->comment('Heure d\'envoi pour permanents et semi-permanents');
            $table->time('temporary_time')->default('14:00:00')
                ->comment('Heure d\'envoi pour les temporaires/vacataires');

            // Délai de réponse en minutes (par défaut 45 minutes)
            $table->integer('response_delay_minutes')->default(45)
                ->comment('Délai pour répondre à la notification (en minutes)');

            // Pénalité appliquée en heures
            $table->decimal('penalty_hours', 5, 2)->default(1.00)
                ->comment('Nombre d\'heures de pénalité pour non-réponse');

            // Activer/désactiver le système
            $table->boolean('is_active')->default(true)
                ->comment('Système de notifications actif');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
