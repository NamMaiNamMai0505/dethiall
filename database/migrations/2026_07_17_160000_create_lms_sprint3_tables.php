<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sprint 3 — Đánh giá: bài tập, nộp bài, ngân hàng đề, thi online, proctor cơ bản */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_assignments')) {
            Schema::create('lms_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->decimal('max_score', 8, 2)->default(10);
                $table->boolean('allow_late')->default(false);
                $table->boolean('is_published')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_assignment_submissions')) {
            Schema::create('lms_assignment_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('text_answer')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('disk', 30)->default('public');
                $table->timestamp('submitted_at')->nullable();
                $table->string('status', 20)->default('submitted'); // draft|submitted|graded
                $table->decimal('score', 8, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_assignment_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('lms_question_banks')) {
            Schema::create('lms_question_banks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->nullable()->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_questions')) {
            Schema::create('lms_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_question_bank_id')->constrained('lms_question_banks')->cascadeOnDelete();
                // mcq | true_false | short
                $table->string('type', 20)->default('mcq');
                $table->text('stem');
                $table->json('options')->nullable(); // ["A","B","C","D"]
                $table->string('correct_answer', 500)->nullable(); // index "0" or "true"/"false" or text
                $table->decimal('points', 8, 2)->default(1);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_exams')) {
            Schema::create('lms_exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->timestamp('opens_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->unsignedTinyInteger('max_attempts')->default(1);
                $table->decimal('pass_score', 8, 2)->nullable();
                $table->boolean('shuffle_questions')->default(true);
                $table->boolean('shuffle_options')->default(true);
                $table->boolean('proctor_basic')->default(true); // fullscreen + blur warn
                $table->boolean('is_published')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_exam_questions')) {
            Schema::create('lms_exam_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_exam_id')->constrained('lms_exams')->cascadeOnDelete();
                $table->foreignId('lms_question_id')->constrained('lms_questions')->cascadeOnDelete();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->decimal('points', 8, 2)->nullable(); // override
                $table->unique(['lms_exam_id', 'lms_question_id']);
            });
        }

        if (! Schema::hasTable('lms_exam_attempts')) {
            Schema::create('lms_exam_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_exam_id')->constrained('lms_exams')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('status', 20)->default('in_progress'); // in_progress|submitted|graded
                $table->json('question_order')->nullable();
                $table->json('answers')->nullable(); // {question_id: answer}
                $table->decimal('score', 8, 2)->nullable();
                $table->decimal('max_score', 8, 2)->nullable();
                $table->json('proctor_events')->nullable(); // blur, fullscreen exit
                $table->unsignedSmallInteger('blur_count')->default(0);
                $table->timestamps();
                $table->index(['lms_exam_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_exam_attempts');
        Schema::dropIfExists('lms_exam_questions');
        Schema::dropIfExists('lms_exams');
        Schema::dropIfExists('lms_questions');
        Schema::dropIfExists('lms_question_banks');
        Schema::dropIfExists('lms_assignment_submissions');
        Schema::dropIfExists('lms_assignments');
    }
};
