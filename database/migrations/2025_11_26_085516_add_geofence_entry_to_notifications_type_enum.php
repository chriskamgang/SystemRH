<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'ENUM pour ajouter 'geofence_entry' (SQLite doesn't support ENUM)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('presence_check', 'check_in_reminder', 'check_out_reminder', 'zone_exit', 'geofence_entry', 'system') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'geofence_entry' de l'ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('presence_check', 'check_in_reminder', 'check_out_reminder', 'zone_exit', 'system') NOT NULL");
        }
    }
};
