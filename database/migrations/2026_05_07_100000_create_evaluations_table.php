<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('evaluation_campaigns')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_score')->default(5);
            $table->integer('weight')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('evaluation_campaigns')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'self_evaluated', 'evaluated', 'validated'])->default('pending');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('evaluator_comments')->nullable();
            $table->text('objectives_next_year')->nullable();
            $table->text('training_needs')->nullable();
            $table->timestamp('self_evaluated_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'employee_id']);
        });

        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->onDelete('cascade');
            $table->foreignId('criteria_id')->constrained('evaluation_criteria')->onDelete('cascade');
            $table->integer('employee_score')->nullable();
            $table->integer('evaluator_score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_campaigns');
    }
};
