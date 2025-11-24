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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 20)->unique()->nullable();
            $table->text('description')->nullable();

            // Campus principal (optionnel) - Note: head_user_id sera ajouté plus tard
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('head_user_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('campus_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
