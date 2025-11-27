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
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable(); // Précision GPS en mètres
            $table->string('device_info')->nullable(); // Info appareil (Android/iOS)
            $table->boolean('is_active')->default(true); // Utilisateur actif (app ouverte)
            $table->timestamp('last_updated_at'); // Dernière mise à jour position
            $table->timestamps();

            // Index pour requêtes rapides
            $table->index('user_id');
            $table->index('is_active');
            $table->index('last_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};
