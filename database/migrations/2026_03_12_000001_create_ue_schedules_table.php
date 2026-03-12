<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ue_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_enseignement_id')->constrained('unites_enseignement')->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->enum('jour_semaine', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unite_enseignement_id', 'jour_semaine', 'heure_debut'], 'ue_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ue_schedules');
    }
};
