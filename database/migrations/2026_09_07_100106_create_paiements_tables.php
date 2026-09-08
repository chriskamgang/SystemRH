<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parametrage applicatif edite depuis le back-office (cles KPay incluses).
        // Les valeurs sensibles sont chiffrees par le cast du modele.
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->text('valeur')->nullable();
            $table->string('groupe', 50)->default('general')->index();
            $table->boolean('chiffre')->default(false);
            $table->timestamps();
        });

        // Journal de toutes les transactions KPay, encaissements comme versements.
        Schema::create('transactions_kpay', function (Blueprint $table) {
            $table->id();

            // deposit (abonnement etudiant) | payout (prime chauffeur)
            $table->string('type', 20)->index();

            // Identifiant unique cote INSAM BUS, garantit l'idempotence KPay.
            $table->string('external_id')->unique();

            // Identifiants cote KPay.
            $table->string('kpay_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->string('provider_reference')->nullable();

            // PENDING | PROCESSING | COMPLETED | FAILED | CANCELLED
            $table->string('statut', 20)->default('PENDING')->index();

            $table->unsignedInteger('montant');
            $table->unsignedInteger('montant_net')->nullable();
            $table->unsignedInteger('frais')->nullable();
            $table->string('devise', 5)->default('XAF');

            $table->string('provider', 40)->nullable();
            $table->string('pays', 5)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->boolean('est_test')->default(true);

            // Cible metier de la transaction (abonnement encaisse, cloture de paie versee).
            $table->nullableMorphs('payable');

            $table->text('description')->nullable();
            $table->string('motif_echec')->nullable();
            $table->json('metadonnees')->nullable();
            $table->json('reponse_brute')->nullable();

            $table->timestamp('completee_le')->nullable();
            $table->timestamp('echouee_le')->nullable();
            $table->timestamps();

            $table->index(['type', 'statut']);
        });

        // Trace des webhooks recus, pour l'idempotence et l'audit.
        Schema::create('webhooks_kpay', function (Blueprint $table) {
            $table->id();
            $table->string('evenement', 50)->index();
            $table->string('kpay_id')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->string('statut', 20)->nullable();
            $table->json('charge_utile');
            $table->boolean('signature_valide')->default(false);
            $table->boolean('traite')->default(false);
            $table->text('erreur_traitement')->nullable();
            $table->timestamp('recu_le');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks_kpay');
        Schema::dropIfExists('transactions_kpay');
        Schema::dropIfExists('parametres');
    }
};
