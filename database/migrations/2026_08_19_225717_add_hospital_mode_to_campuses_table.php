<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->string('attendance_mode')->default('standard')->after('is_active'); // 'standard' ou 'hospital'
            $table->time('night_start_time')->nullable()->after('attendance_mode'); // Heure debut garde (ex: 19:00)
            $table->integer('night_late_tolerance')->nullable()->after('night_start_time'); // Tolerance retard garde (minutes)
        });
    }

    public function down(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->dropColumn(['attendance_mode', 'night_start_time', 'night_late_tolerance']);
        });
    }
};
