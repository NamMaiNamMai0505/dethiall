<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('essay_exams')) {
            Schema::create('essay_exams', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('title');
                $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
                $table->string('status', 32)->default('DRAFT')->index();
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->text('note')->nullable();
                $table->text('return_note')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('created_by_username')->nullable();
                $table->string('created_by_display_name')->nullable();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('locked')->default(false);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('essay_exam_questions')) {
            Schema::create('essay_exam_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('essay_exam_id')->constrained('essay_exams')->cascadeOnDelete();
                $table->unsignedInteger('question_number')->default(1);
                $table->text('content');
                $table->text('answer')->nullable();
                $table->decimal('points', 6, 2)->default(1);
                $table->timestamps();
                $table->index(['essay_exam_id', 'question_number']);
            });
        }
        if (! Schema::hasTable('essay_exam_workflow_logs')) {
            Schema::create('essay_exam_workflow_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('essay_exam_id')->constrained('essay_exams')->cascadeOnDelete();
                $table->string('action', 32);
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();
                $table->text('note')->nullable();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_username')->nullable();
                $table->string('actor_display_name')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('essay_exam_draws')) {
            Schema::create('essay_exam_draws', function (Blueprint $table) {
                $table->id();
                $table->foreignId('essay_exam_id')->constrained('essay_exams')->cascadeOnDelete();
                $table->string('draw_code')->unique();
                $table->string('draw_type', 8);
                $table->string('class_name')->nullable();
                $table->date('exam_date')->nullable();
                $table->time('exam_time')->nullable();
                $table->string('location')->nullable();
                $table->foreignId('drawn_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('drawn_at');
                $table->timestamps();
                $table->index(['essay_exam_id','draw_type']);
            });
        }
    }
    public function down(): void { Schema::dropIfExists('essay_exam_draws'); Schema::dropIfExists('essay_exam_workflow_logs'); Schema::dropIfExists('essay_exam_questions'); Schema::dropIfExists('essay_exams'); }
};
