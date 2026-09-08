<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rend le nom et le prenom facultatifs.
 *
 * L'etudiant d'INSAM BUS ouvre son compte avec une adresse et un code a
 * quatre chiffres, rien de plus : son identite n'arrive qu'a l'etape
 * suivante, la completion de profil. Ces deux colonnes venant d'Estuaire
 * RH etaient restees NOT NULL, et l'inscription echouait donc en base
 * avant meme d'avoir pu demander le nom.
 *
 * Cote Estuaire RH rien ne change : la creation d'un employe passe par le
 * back-office, dont la validation continue d'exiger les deux champs.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN first_name VARCHAR(100) NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN last_name VARCHAR(100) NULL');
    }

    public function down(): void
    {
        // Un compte sans identite empecherait le retour a NOT NULL. Les
        // etudiants du transport en cours d'inscription sont dans ce cas :
        // on leur pose une valeur vide plutot que de supprimer le compte,
        // qui porte deja un abonnement et un historique de trajets.
        DB::table('users')->whereNull('first_name')->update(['first_name' => '']);
        DB::table('users')->whereNull('last_name')->update(['last_name' => '']);

        DB::statement('ALTER TABLE users MODIFY COLUMN first_name VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY COLUMN last_name VARCHAR(100) NOT NULL');
    }
};
