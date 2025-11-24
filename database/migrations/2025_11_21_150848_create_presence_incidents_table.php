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
        Schema::create('presence_incidents', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade')
                ->comment('Employé concerné');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade')
                ->comment('Campus où l\'incident a eu lieu');
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('set null')
                ->comment('Référence au check-in du jour');

            // Informations de l'incident
            $table->date('incident_date')->comment('Date de l\'incident');
            $table->time('notification_sent_at')->comment('Heure d\'envoi de la notification');
            $table->time('response_deadline')->comment('Heure limite de réponse');

            // Réponse de l'employé
            $table->boolean('has_responded')->default(false)
                ->comment('L\'employé a-t-il répondu?');
            $table->timestamp('responded_at')->nullable()
                ->comment('Quand l\'employé a répondu');

            // Géolocalisation au moment de la réponse
            $table->decimal('response_latitude', 10, 8)->nullable();
            $table->decimal('response_longitude', 11, 8)->nullable();
            $table->boolean('was_in_zone')->nullable()
                ->comment('Était dans la zone au moment de la réponse');

            // Statut et validation admin
            $table->enum('status', ['pending', 'validated', 'ignored', 'cancelled'])->default('pending')
                ->comment('pending: en attente, validated: pénalité validée, ignored: incident ignoré, cancelled: annulé');

            // Validation admin
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Admin qui a validé/ignoré');
            $table->timestamp('validated_at')->nullable();
            $table->text('admin_notes')->nullable()
                ->comment('Notes de l\'admin');

            // Pénalité appliquée
            $table->decimal('penalty_hours', 5, 2)->nullable()
                ->comment('Heures de salaire coupées si validé');
            $table->boolean('penalty_applied')->default(false)
                ->comment('La pénalité a-t-elle été appliquée au salaire');

            $table->timestamps();

            // Index
            $table->index('incident_date');
            $table->index('status');
            $table->index(['user_id', 'incident_date']);
            $table->index(['campus_id', 'incident_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_incidents');
    }
};
