<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Champs utilisateur pour le calcul des droits
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_embauche')->nullable()->after('company_id');
            $table->string('sexe', 1)->nullable()->after('date_embauche'); // M ou F
            $table->unsignedTinyInteger('nombre_enfants_charge')->default(0)->after('sexe'); // enfants < 6 ans
        });

        // Champs leave_requests pour workflow manager + intérimaire
        Schema::table('leave_requests', function (Blueprint $table) {
            // Sous-type événement familial
            $table->string('family_event_type')->nullable()->after('type'); // marriage, birth, death

            // Intérimaire
            $table->string('interim_name')->nullable()->after('review_comment');
            $table->string('interim_function')->nullable()->after('interim_name');
            $table->text('interim_tasks')->nullable()->after('interim_function');

            // Workflow manager (étape 1)
            $table->string('manager_status')->nullable()->after('interim_tasks'); // pending, approved, rejected
            $table->foreignId('manager_reviewed_by')->nullable()->after('manager_status')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_reviewed_at')->nullable()->after('manager_reviewed_by');
            $table->text('manager_comment')->nullable()->after('manager_reviewed_at');
        });

        // Table jours fériés
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_recurring')->default(false); // se répète chaque année (même jour/mois)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['manager_reviewed_by']);
            $table->dropColumn([
                'family_event_type', 'interim_name', 'interim_function', 'interim_tasks',
                'manager_status', 'manager_reviewed_by', 'manager_reviewed_at', 'manager_comment',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_embauche', 'sexe', 'nombre_enfants_charge']);
        });
    }
};
