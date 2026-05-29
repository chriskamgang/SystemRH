<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Index composite pour la requête dashboard : user_id + type + timestamp
            $table->index(['user_id', 'type', 'timestamp'], 'att_user_type_ts');
            // Index composite pour la requête "aujourd'hui" : user_id + timestamp
            $table->index(['user_id', 'timestamp'], 'att_user_ts');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_user_type_ts');
            $table->dropIndex('att_user_ts');
        });
    }
};
