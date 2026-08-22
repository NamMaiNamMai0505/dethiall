<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4 — Theo dõi & quản lý học tập:
 * điểm/bảng điểm, điểm danh đa hình thức, lớp HP độc lập,
 * tiến độ học, cảnh báo học tập.
 */
return new class extends Migration
{
    public function up(): void
    {
        // —— Lớp học phần độc lập: class_id optional + cờ standalone ——
        if (Schema::hasTable('lms_courses')) {
            Schema::table('lms_courses', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_courses', 'is_standalone')) {
                    $table->boolean('is_standalone')->default(false)->after('status');
                }
                if (! Schema::hasColumn('lms_courses', 'section_code')) {
                    $table->string('section_code', 80)->nullable()->after('code');
                }
            });

            // Drop unique(subject, class) if present — standalone may share subject without class
            try {
                Schema::table('lms_courses', function (Blueprint $table) {
                    $table->dropUnique('lms_courses_subject_class_unique');
                });
            } catch (\Throwable $e) {
                // already dropped or named differently
            }

            // Make class_id nullable without doctrine/dbal
            try {
                Schema::table('lms_courses', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            } catch (\Throwable $e) {
            }

            try {
                DB::statement('ALTER TABLE lms_courses MODIFY class_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('lms_courses', function (Blueprint $table) {
                    $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        if (! Schema::hasTable('lms_attendance_sessions')) {
            Schema::create('lms_attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->date('session_date')->nullable();
                // manual | qr | self
                $table->string('mode', 20)->default('manual');
                // open | closed
                $table->string('status', 20)->default('open');
                $table->timestamp('open_from')->nullable();
                $table->timestamp('open_until')->nullable();
                $table->string('checkin_token', 64)->nullable()->unique();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['lms_course_id', 'session_date']);
            });
        }

        if (! Schema::hasTable('lms_attendance_records')) {
            Schema::create('lms_attendance_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_attendance_session_id')->constrained('lms_attendance_sessions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                // present | absent | late | excused
                $table->string('status', 20)->default('present');
                $table->string('method', 20)->nullable(); // manual|qr|self
                $table->timestamp('checked_in_at')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['lms_attendance_session_id', 'user_id'], 'lms_att_rec_session_user_uq');
            });
        }

        if (! Schema::hasTable('lms_progress_events')) {
            Schema::create('lms_progress_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                // lesson | material | scorm | assignment | exam
                $table->string('trackable_type', 40);
                $table->unsignedBigInteger('trackable_id')->nullable();
                // view | complete
                $table->string('event', 20)->default('view');
                $table->unsignedTinyInteger('progress_pct')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['lms_course_id', 'user_id', 'trackable_type']);
            });
        }

        if (! Schema::hasTable('lms_progress_summaries')) {
            Schema::create('lms_progress_summaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedSmallInteger('lessons_done')->default(0);
                $table->unsignedSmallInteger('lessons_total')->default(0);
                $table->unsignedSmallInteger('materials_done')->default(0);
                $table->unsignedSmallInteger('materials_total')->default(0);
                $table->unsignedSmallInteger('assignments_done')->default(0);
                $table->unsignedSmallInteger('assignments_total')->default(0);
                $table->unsignedSmallInteger('exams_done')->default(0);
                $table->unsignedSmallInteger('exams_total')->default(0);
                $table->decimal('overall_pct', 5, 2)->default(0);
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_course_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('lms_gradebook_rows')) {
            Schema::create('lms_gradebook_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('assignment_avg', 8, 2)->nullable();
                $table->decimal('exam_avg', 8, 2)->nullable();
                $table->decimal('attendance_pct', 5, 2)->nullable();
                $table->decimal('progress_pct', 5, 2)->nullable();
                $table->decimal('computed_score', 8, 2)->nullable();
                $table->decimal('final_score', 8, 2)->nullable();
                $table->string('letter', 5)->nullable();
                $table->text('note')->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
                $table->unique(['lms_course_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('lms_learning_alerts')) {
            Schema::create('lms_learning_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                // low_progress | missing_assignment | low_attendance | fail_exam | custom
                $table->string('type', 40);
                // info | warning | critical
                $table->string('severity', 20)->default('warning');
                $table->string('title');
                $table->text('body')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['lms_course_id', 'user_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_learning_alerts');
        Schema::dropIfExists('lms_gradebook_rows');
        Schema::dropIfExists('lms_progress_summaries');
        Schema::dropIfExists('lms_progress_events');
        Schema::dropIfExists('lms_attendance_records');
        Schema::dropIfExists('lms_attendance_sessions');
    }
};
