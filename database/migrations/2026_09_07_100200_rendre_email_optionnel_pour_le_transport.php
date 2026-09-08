<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rend l'adresse email facultative.
 *
 * Le chauffeur d'INSAM BUS se connecte par telephone et mot de passe : il
 * n'a pas d'adresse, et rien dans son parcours ne lui en demande une.
 *
 * L'unicite est conservee — MySQL n'applique pas une contrainte unique aux
 * valeurs nulles, plusieurs comptes sans adresse coexistent donc sans se
 * gener. Cote Estuaire RH rien ne change : l'inscription d'un employe
 * continue d'exiger une adresse, la validation s'en charge en amont.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Les comptes sans adresse empecheraient le retour a NOT NULL.
        DB::table('users')->whereNull('email')->delete();

        DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NOT NULL');
    }
};
