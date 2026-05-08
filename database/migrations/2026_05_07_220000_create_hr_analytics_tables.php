<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshots mensuels pour analytics historiques
        Schema::create('hr_analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_employees')->default(0);
            $table->integer('new_hires')->default(0);
            $table->integer('departures')->default(0);
            $table->decimal('turnover_rate', 5, 2)->default(0);
            $table->decimal('avg_attendance_rate', 5, 2)->default(0);
            $table->decimal('avg_late_rate', 5, 2)->default(0);
            $table->integer('total_leave_days')->default(0);
            $table->decimal('total_payroll', 15, 2)->default(0);
            $table->decimal('avg_evaluation_score', 5, 2)->nullable();
            $table->integer('training_completions')->default(0);
            $table->integer('open_positions')->default(0);
            $table->json('department_breakdown')->nullable();
            $table->json('employee_type_breakdown')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_analytics_snapshots');
    }
};
