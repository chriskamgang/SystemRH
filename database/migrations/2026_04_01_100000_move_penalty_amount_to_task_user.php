<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter penalty_amount au pivot task_user
        Schema::table('task_user', function (Blueprint $table) {
            $table->decimal('penalty_amount', 10, 0)->default(0)->after('note');
        });

        // Copier les montants existants de tasks vers task_user
        DB::statement('UPDATE task_user SET penalty_amount = (SELECT penalty_amount FROM tasks WHERE tasks.id = task_user.task_id)');

        // Supprimer penalty_amount de la table tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('penalty_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('penalty_amount', 10, 0)->default(0)->after('due_date');
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropColumn('penalty_amount');
        });
    }
};
