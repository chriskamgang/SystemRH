<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accueille les comptes INSAM BUS dans la table `users` d'Estuaire RH.
 *
 * Les deux applications avaient chacune leur table `users`, aux exigences
 * inconciliables : Estuaire RH impose un `role_id` et un mot de passe, INSAM
 * BUS un téléphone unique et un rôle en chaîne — et son étudiant n'a pas de
 * mot de passe du tout, il se connecte par code à usage unique puis par PIN.
 *
 * La table RH est donc étendue plutôt que dupliquée : les colonnes du bus
 * arrivent toutes nullables, et les deux contraintes qui bloquaient un
 * compte étudiant (`role_id`, `password`) deviennent facultatives.
 *
 * Rien n'est retiré : les colonnes et index d'Estuaire RH restent intacts,
 * et un employé créé par le back-office RH continue de porter son `role_id`
 * et son `employee_type` comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // --- Identité côté bus ---------------------------------------
            //
            // Le bus nomme ses champs en français et sépare nom et prénom
            // comme RH le fait déjà (first_name / last_name) : on ne
            // redouble pas ces deux-là, `espace` dira lequel fait foi.

            // Le téléphone est l'identifiant des applications mobiles. RH a
            // déjà un `phone`, mais sans contrainte d'unicité et parfois
            // vide : le bus a besoin de la sienne, distincte.
            $table->string('telephone_bus', 20)->nullable()->unique()->after('phone');
            $table->timestamp('telephone_verified_at')->nullable()->after('telephone_bus');

            // etudiant | chauffeur | gestionnaire | admin — voir
            // App\Enums\RoleUtilisateur. Sans rapport avec `role_id`, qui
            // reste la hiérarchie de droits d'Estuaire RH.
            $table->string('role_bus', 20)->nullable()->index()->after('role_id');

            // Un compte peut être actif d'un côté et pas de l'autre.
            $table->boolean('actif_bus')->default(true)->after('is_active');

            $table->string('photo_path')->nullable()->after('photo');

            // --- Connexion par PIN ---------------------------------------
            //
            // L'étudiant pose un code à quatre chiffres après avoir vérifié
            // son adresse ; il remplace le mot de passe pour l'application
            // mobile. Les essais sont comptés pour bloquer le brute force.
            $table->string('pin')->nullable()->after('password');
            $table->unsignedSmallInteger('pin_essais')->default(0)->after('pin');
            $table->timestamp('pin_bloque_jusqu_a')->nullable()->after('pin_essais');

            // --- Cloisonnement --------------------------------------------
            //
            // Dit de quel espace vient le compte. Les requêtes d'Estuaire RH
            // n'ont jamais à voir les étudiants du bus, et inversement :
            // c'est cette colonne que lisent les scopes des deux modèles.
            //
            // `rh` par défaut : tous les comptes déjà en base sont des
            // employés, et le resteront.
            $table->enum('espace', ['rh', 'bus', 'mixte'])
                ->default('rh')
                ->index();
        });

        // --- Contraintes assouplies -------------------------------------
        //
        // Un étudiant du bus n'a ni rôle RH ni mot de passe. Les deux
        // colonnes deviennent facultatives ; les comptes RH existants les
        // renseignent toujours, et le back-office continue de l'exiger.
        DB::statement('ALTER TABLE users MODIFY COLUMN role_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Les comptes du bus doivent partir avant que les contraintes ne
        // reviennent : ils n'ont ni `role_id` ni mot de passe à présenter.
        DB::table('users')->where('espace', 'bus')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telephone_bus',
                'telephone_verified_at',
                'role_bus',
                'actif_bus',
                'photo_path',
                'pin',
                'pin_essais',
                'pin_bloque_jusqu_a',
                'espace',
            ]);
        });

        DB::statement('ALTER TABLE users MODIFY COLUMN role_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL');
    }
};
