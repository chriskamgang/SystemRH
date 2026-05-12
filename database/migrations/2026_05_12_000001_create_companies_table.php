<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Cameroun');
            $table->string('sector')->nullable(); // Education, Sante, etc.
            $table->string('subscription_plan')->default('basic'); // basic, pro, enterprise
            $table->integer('max_employees')->default(50);
            $table->boolean('is_active')->default(true);
            $table->date('subscription_expires_at')->nullable();
            $table->json('settings')->nullable(); // Config specifique par entreprise
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
