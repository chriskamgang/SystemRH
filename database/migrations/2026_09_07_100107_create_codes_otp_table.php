<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Codes a usage unique envoyes par email a l'etudiant (3.1).
        // L'application mobile ne demande aucun mot de passe : le code
        // vaut a la fois preuve d'identite et verification de l'adresse.
        Schema::create('codes_otp', function (Blueprint $table) {
            $table->id();
            $table->string('email');

            // Le code n'est jamais stocke en clair.
            $table->string('code_hash');

            $table->timestamp('expire_a');
            $table->timestamp('consomme_a')->nullable();

            // Bornes les tentatives pour empecher le balayage des 10^6 codes.
            $table->unsignedTinyInteger('tentatives')->default(0);

            $table->string('ip', 45)->nullable();
            $table->timestamps();

            // Recherche du dernier code valide d'une adresse.
            $table->index(['email', 'expire_a']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_otp');
    }
};
