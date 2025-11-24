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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Informations personnelles
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();

            // Authentification
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();

            // Type d'employé
            $table->enum('employee_type', [
                'enseignant_titulaire',
                'enseignant_vacataire',
                'administratif',
                'technique',
                'direction'
            ]);

            // Relations
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');

            // Statut
            $table->boolean('is_active')->default(true);

            // Firebase Cloud Messaging
            $table->string('fcm_token')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('department_id');
            $table->index('role_id');
            $table->index('employee_type');
            $table->index('is_active');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
