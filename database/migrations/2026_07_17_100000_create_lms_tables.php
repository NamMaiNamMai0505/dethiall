<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 1 — Nền tảng LMS học tập.
 * Tận dụng subjects, classes, instructors, users — không nhân bản CTĐT/lịch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_courses')) {
            Schema::create('lms_courses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 80)->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();
                $table->string('status', 20)->default('draft')->index(); // draft|published|archived
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                // Một course LMS active cho mỗi cặp môn + lớp (soft-delete xử lý ở app)
                $table->unique(['subject_id', 'class_id'], 'lms_courses_subject_class_unique');
            });
        }

        if (! Schema::hasTable('lms_course_members')) {
            Schema::create('lms_course_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 20)->default('student'); // student|lecturer|assistant
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['lms_course_id', 'user_id']);
                $table->index(['user_id', 'role']);
            });
        }

        if (! Schema::hasTable('lms_lessons')) {
            Schema::create('lms_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                // Tuỳ chọn: map bài khung CTĐT (subject_lessons) — không bắt buộc
                $table->unsignedBigInteger('subject_lesson_id')->nullable()->index();
                $table->string('title');
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedSmallInteger('week_number')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['lms_course_id', 'sort_order']);
            });

            if (Schema::hasTable('subject_lessons')) {
                try {
                    Schema::table('lms_lessons', function (Blueprint $table) {
                        $table->foreign('subject_lesson_id')
                            ->references('id')
                            ->on('subject_lessons')
                            ->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                    // FK optional
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_course_members');
        Schema::dropIfExists('lms_courses');
    }
};
