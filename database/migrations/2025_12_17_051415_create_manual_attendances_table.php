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
        Schema::create('manual_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('unite_enseignement_id')->nullable()->constrained('unites_enseignement')->onDelete('set null');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->enum('session_type', ['jour', 'soir'])->default('jour');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Index pour les recherches fréquentes
            $table->index(['user_id', 'date']);
            $table->index(['campus_id', 'date']);
            $table->index(['unite_enseignement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_attendances');
    }
};
