<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ecart entre l'effectif compte par le chauffeur et les embarquements
        // valides par QR : ce sont les passagers montes sans ticket (3.2).
        Schema::table('tournees', function (Blueprint $table) {
            $table->unsignedSmallInteger('embarquements_valides')->default(0)->after('effectif_embarque');
            $table->unsignedSmallInteger('passagers_sans_ticket')->default(0)->after('embarquements_valides');

            // especes_bord | invite | erreur_comptage | autre
            $table->string('motif_ecart', 30)->nullable()->after('passagers_sans_ticket');
            $table->text('commentaire_ecart')->nullable()->after('motif_ecart');
        });

        // Origine de la validation, pour distinguer un scan d'une saisie manuelle.
        Schema::table('trajets', function (Blueprint $table) {
            $table->string('mode_validation', 20)->default('qr')->after('embarque_le');
            $table->foreignId('valide_par')->nullable()->after('mode_validation')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('valide_par');
            $table->dropColumn('mode_validation');
        });

        Schema::table('tournees', function (Blueprint $table) {
            $table->dropColumn([
                'embarquements_valides', 'passagers_sans_ticket',
                'motif_ecart', 'commentaire_ecart',
            ]);
        });
    }
};
