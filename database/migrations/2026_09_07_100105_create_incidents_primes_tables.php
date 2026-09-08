<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Panne declaree par un chauffeur en difficulte (3.3).
        Schema::create('pannes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained('bus')->cascadeOnDelete();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->foreignId('tournee_id')->nullable()->constrained('tournees')->nullOnDelete();

            $table->string('type_panne', 50)->nullable();  // mecanique | pneu | carburant | accident | autre
            $table->text('description')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('passagers_immobilises')->nullable();

            // declaree | prise_en_charge | resolue | annulee
            $table->string('statut', 20)->default('declaree')->index();

            $table->timestamp('declaree_le');
            $table->timestamp('resolue_le')->nullable();
            $table->timestamps();

            $table->index(['statut', 'declaree_le']);
        });

        // Mission de secours affectee a un chauffeur disponible (3.3 / 3.5).
        Schema::create('missions_secours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panne_id')->constrained('pannes')->cascadeOnDelete();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained('bus')->cascadeOnDelete();

            // affectee | acceptee | en_route | terminee | annulee
            $table->string('statut', 20)->default('affectee')->index();

            $table->timestamp('affectee_le');
            $table->timestamp('acceptee_le')->nullable();
            $table->timestamp('terminee_le')->nullable();

            // Controle croise (3.4) : la prime exige panne confirmee ET passagers pris en charge.
            $table->unsignedSmallInteger('passagers_recuperes')->nullable();
            $table->boolean('panne_confirmee')->default(false);
            $table->boolean('prise_en_charge_confirmee')->default(false);

            // manuelle | semi_automatique
            $table->string('mode_affectation', 20)->default('manuelle');
            $table->foreignId('affectee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['chauffeur_id', 'statut']);
        });

        // Bareme parametrable des primes (3.3).
        Schema::create('baremes_primes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();   // prime_tour | prime_secours | prime_assiduite | bonus_regularite
            $table->string('libelle');
            $table->unsignedInteger('montant_fcfa');

            // par_tour | par_intervention | par_jour | par_mois
            $table->string('periodicite', 20);
            $table->text('conditions')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Ligne de cagnotte : chaque gain (ou penalite) credite au chauffeur, historique in-app (3.3).
        Schema::create('primes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();
            $table->foreignId('bareme_id')->nullable()->constrained('baremes_primes')->nullOnDelete();

            // Origine tracable de la prime.
            $table->foreignId('tournee_id')->nullable()->constrained('tournees')->nullOnDelete();
            $table->foreignId('mission_secours_id')->nullable()->constrained('missions_secours')->nullOnDelete();

            $table->string('type', 40)->index();  // reprend bareme.code, ou "penalite"
            $table->string('libelle');

            // Signe negatif pour une penalite -> le total mensuel reste une simple somme.
            $table->integer('montant_fcfa');
            $table->date('date_acquisition')->index();

            // Periode de paie AAAA-MM, sert a la cloture mensuelle (3.5).
            $table->string('periode', 7)->index();

            // en_attente | validee | payee | annulee
            $table->string('statut', 20)->default('en_attente')->index();
            $table->foreignId('validee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validee_le')->nullable();
            $table->timestamps();

            $table->index(['chauffeur_id', 'periode']);
        });

        // Cloture de paie mensuelle : fige le total de chaque chauffeur pour une periode (3.5).
        Schema::create('clotures_paie', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->foreignId('chauffeur_id')->constrained('chauffeurs')->cascadeOnDelete();

            $table->unsignedSmallInteger('tours_valides')->default(0);
            $table->unsignedSmallInteger('secours_realises')->default(0);
            $table->unsignedSmallInteger('jours_assiduite')->default(0);
            $table->unsignedInteger('effectif_transporte')->default(0);

            $table->integer('total_primes_fcfa')->default(0);
            $table->integer('total_penalites_fcfa')->default(0);
            $table->integer('net_a_payer_fcfa')->default(0);

            // brouillon | cloturee | payee
            $table->string('statut', 20)->default('brouillon')->index();
            $table->foreignId('cloturee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloturee_le')->nullable();
            $table->timestamps();

            $table->unique(['periode', 'chauffeur_id']);
        });

        // Notifications poussees aux etudiants et chauffeurs (3.1 : depart du depot, retard, changement de vehicule).
        Schema::create('notifications_app', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('titre');
            $table->text('message');
            $table->json('donnees')->nullable();
            $table->timestamp('lue_le')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lue_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_app');
        Schema::dropIfExists('clotures_paie');
        Schema::dropIfExists('primes');
        Schema::dropIfExists('baremes_primes');
        Schema::dropIfExists('missions_secours');
        Schema::dropIfExists('pannes');
    }
};
