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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Type de notification
            $table->enum('type', [
                'presence_check',
                'check_in_reminder',
                'check_out_reminder',
                'zone_exit',
                'system'
            ]);

            // Contenu
            $table->string('title');
            $table->text('body');

            // Statut
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Envoi
            $table->timestamp('sent_at')->useCurrent();
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');

            // Données supplémentaires
            $table->json('data')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('sent_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
