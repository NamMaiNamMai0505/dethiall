<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('integrated_answer_sets')) return;
        Schema::create('integrated_answer_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('status', 32)->default('DRAFT');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_username')->nullable();
            $table->string('created_by_display_name')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('return_note')->nullable();
            $table->timestamps();
        });

        Schema::create('integrated_answer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('answer_set_id')->constrained('integrated_answer_sets')->cascadeOnDelete();
            $table->unsignedInteger('paper_number')->default(1);
            $table->unsignedInteger('question_number');
            $table->text('answer')->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->timestamps();
            $table->unique(['answer_set_id', 'paper_number', 'question_number']);
        });

        Schema::create('integrated_answer_workflow_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('answer_set_id')->constrained('integrated_answer_sets')->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_username')->nullable();
            $table->string('actor_display_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrated_answer_workflow_logs');
        Schema::dropIfExists('integrated_answer_items');
        Schema::dropIfExists('integrated_answer_sets');
    }
};
