<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_deductions', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->nullable()->after('amount');
            $table->integer('num_installments')->default(1)->after('total_amount');
            $table->integer('installment_number')->default(1)->after('num_installments');
            $table->unsignedBigInteger('group_id')->nullable()->after('installment_number');

            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('manual_deductions', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn(['total_amount', 'num_installments', 'installment_number', 'group_id']);
        });
    }
};
