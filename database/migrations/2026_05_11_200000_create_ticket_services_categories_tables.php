<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default services from existing constants
        $now = now();
        DB::table('ticket_services')->insert([
            ['name' => 'Ressources Humaines', 'slug' => 'rh', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Scolarite', 'slug' => 'scolarite', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Finance', 'slug' => 'finance', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Service Technique', 'slug' => 'technique', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Direction', 'slug' => 'direction', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Services Generaux', 'slug' => 'general', 'icon' => null, 'color' => null, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Seed default categories from existing constants
        DB::table('ticket_categories')->insert([
            ['name' => 'Ressources Humaines', 'slug' => 'rh', 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Scolarite', 'slug' => 'scolarite', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Finance', 'slug' => 'finance', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Technique', 'slug' => 'technique', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Infrastructure', 'slug' => 'infrastructure', 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Autre', 'slug' => 'autre', 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('ticket_services');
    }
};
