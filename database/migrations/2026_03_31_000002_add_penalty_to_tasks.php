<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('penalty_amount', 10, 0)->default(0)->after('due_date');
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->boolean('penalty_approved')->default(false)->after('completed_at');
            $table->timestamp('penalty_approved_at')->nullable()->after('penalty_approved');
            $table->foreignId('penalty_approved_by')->nullable()->after('penalty_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_user', function (Blueprint $table) {
            $table->dropForeign(['penalty_approved_by']);
            $table->dropColumn(['penalty_approved', 'penalty_approved_at', 'penalty_approved_by']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('penalty_amount');
        });
    }
};
