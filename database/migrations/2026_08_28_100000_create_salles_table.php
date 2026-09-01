<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->string('nom', 100);
            $table->string('code', 50)->unique();
            $table->integer('nombre_pc')->default(0);
            $table->integer('capacite');
            $table->string('type')->default('cours')->comment('cours, labo_info, labo_sciences, amphi, cantine, autre');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('campus_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
