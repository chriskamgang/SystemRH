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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key_name', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key_name');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
