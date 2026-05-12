<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the old unique on name alone
            $table->dropUnique(['name']);
            // Add composite unique: same role name allowed in different companies
            $table->unique(['name', 'company_id'], 'roles_name_company_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_company_unique');
            $table->unique('name');
        });
    }
};
