<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // TK-2026-0001
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // émetteur
            $table->string('category'); // rh, scolarite, finance, technique, infrastructure, autre
            $table->string('target_service'); // service choisi par l'employé
            $table->string('assigned_to_service')->nullable(); // confirmé/corrigé par réceptionniste
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete(); // réceptionniste
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('subject');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('new'); // new, assigned, in_progress, responded, resolved, closed
            $table->boolean('was_redirected')->default(false);
            $table->string('redirect_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->string('attachment_path')->nullable();
            $table->string('comment_type')->default('public'); // public (visible employé), internal (service only), response (réponse réceptionniste → employé)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
    }
};
