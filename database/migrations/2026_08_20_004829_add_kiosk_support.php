<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Token QR unique par employe
        Schema::table('users', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('fcm_token');
        });

        // Bornes (tablettes) associees a un campus
        Schema::create('kiosk_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // ex: "Borne Entree Hopital"
            $table->string('device_token', 64)->unique(); // token d'auth de la borne
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // Photo de pointage + source (app ou borne)
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('device_info');
            $table->string('source', 20)->default('app')->after('photo_path'); // 'app' ou 'kiosk'
            $table->foreignId('kiosk_device_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['kiosk_device_id']);
            $table->dropColumn(['photo_path', 'source', 'kiosk_device_id']);
        });

        Schema::dropIfExists('kiosk_devices');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
