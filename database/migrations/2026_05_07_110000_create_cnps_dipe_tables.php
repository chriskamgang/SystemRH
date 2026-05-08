<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parametres CNPS de l'employe
        Schema::create('cnps_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('cnps_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
        });

        // Cotisations mensuelles
        Schema::create('cnps_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('employee_contribution', 12, 2)->default(0);
            $table->decimal('employer_contribution', 12, 2)->default(0);
            $table->decimal('total_contribution', 12, 2)->default(0);
            $table->enum('status', ['calculated', 'declared', 'paid'])->default('calculated');
            $table->timestamps();
            $table->unique(['user_id', 'month', 'year']);
        });

        // Declarations DIPE
        Schema::create('dipe_declarations', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('trimester');
            $table->enum('status', ['draft', 'submitted', 'validated'])->default('draft');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_employee', 12, 2)->default(0);
            $table->decimal('total_employer', 12, 2)->default(0);
            $table->integer('employee_count')->default(0);
            $table->string('reference')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'trimester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dipe_declarations');
        Schema::dropIfExists('cnps_contributions');
        Schema::dropIfExists('cnps_records');
    }
};
