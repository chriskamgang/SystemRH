<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation', 30)->unique();
            $table->string('modele')->nullable();
            $table->unsignedSmallInteger('capacite')->default(30);

            // disponible | en_service | en_panne | maintenance
            $table->string('statut', 20)->default('disponible')->index();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Profil metier du chauffeur, adosse a un user role=chauffeur.
        Schema::create('chauffeurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('matricule', 50)->unique();
            $table->string('numero_permis', 50)->nullable();
            $table->date('permis_expire_le')->nullable();

            // Chauffeur volontaire pour les missions de secours (regle 3.3).
            $table->boolean('disponible_secours')->default(true);
            $table->timestamps();
        });

        // Profil metier de l'etudiant, adosse a un user role=etudiant.
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('matricule_insam', 50)->unique();

            // Ligne et arret de ramassage habituels (3.1).
            $table->foreignId('ligne_id')->nullable()->constrained('lignes')->nullOnDelete();
            $table->foreignId('arret_id')->nullable()->constrained('arrets')->nullOnDelete();
            $table->timestamps();

            $table->index(['ligne_id', 'arret_id']);
        });

        // Affectation quotidienne bus <-> chauffeur <-> ligne (regulation de flotte, 3.5).
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained('bus')->cascadeOnDelete();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->foreignId('ligne_id')->constrained('lignes')->cascadeOnDelete();
            $table->date('date_service');
            $table->unsignedSmallInteger('tours_prevus')->default(4);

            // planifiee | active | terminee | annulee
            $table->string('statut', 20)->default('planifiee')->index();
            $table->timestamps();

            // Un bus ne sert qu'une ligne par jour ; un chauffeur ne conduit qu'un bus par jour.
            $table->unique(['bus_id', 'date_service']);
            $table->unique(['chauffeur_id', 'date_service']);
            $table->index(['date_service', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('etudiants');
        Schema::dropIfExists('chauffeurs');
        Schema::dropIfExists('bus');
    }
};
