<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('shift');
            $table->index(['user_id', 'shift', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['shift']);
            $table->dropIndex(['user_id', 'shift', 'timestamp']);
        });
    }
};
