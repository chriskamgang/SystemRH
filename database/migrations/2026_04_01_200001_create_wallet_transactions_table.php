<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit', 'transfer']);
            $table->decimal('amount', 12, 0);
            $table->decimal('balance_before', 12, 0);
            $table->decimal('balance_after', 12, 0);
            $table->text('description')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->string('elgiopay_payout_id')->nullable();
            $table->string('elgiopay_status')->nullable();
            $table->string('transfer_phone')->nullable();
            $table->string('transfer_method')->nullable();
            $table->string('source_type'); // salary, advance, penalty, transfer
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
