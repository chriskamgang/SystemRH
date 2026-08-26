<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Séances de TP rattachées aux UEs existantes
        Schema::create('ue_seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_enseignement_id')->constrained('unites_enseignement')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero'); // 1, 2, 3, 4
            $table->string('titre'); // "Appareil locomoteur"
            $table->text('objectif'); // "Localiser les organes..."
            $table->timestamps();
        });

        // Validations par les étudiants
        Schema::create('ue_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ue_seance_id')->constrained('ue_seances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('tp_effectue')->default(false);
            $table->boolean('objectif_atteint')->default(false);
            $table->text('observation')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['ue_seance_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ue_validations');
        Schema::dropIfExists('ue_seances');
    }
};
