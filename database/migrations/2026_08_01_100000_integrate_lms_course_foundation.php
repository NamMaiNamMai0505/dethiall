<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_courses', 'academic_year_id')) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('academic_years')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('lms_courses', 'term')) {
                $table->string('term', 40)->nullable()->after('academic_year_id')->index();
            }
            if (! Schema::hasColumn('lms_courses', 'source_type')) {
                $table->string('source_type', 40)->nullable()->after('term')->index();
            }
            if (! Schema::hasColumn('lms_courses', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            }
            if (! Schema::hasColumn('lms_courses', 'provision_key')) {
                $table->string('provision_key', 191)->nullable()->after('source_id')->unique();
            }
            if (! Schema::hasColumn('lms_courses', 'content_synced_at')) {
                $table->timestamp('content_synced_at')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('lms_courses', 'roster_synced_at')) {
                $table->timestamp('roster_synced_at')->nullable()->after('content_synced_at');
            }
        });

        Schema::table('lms_course_members', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_course_members', 'source')) {
                $table->string('source', 40)->default('manual')->after('role')->index();
            }
            if (! Schema::hasColumn('lms_course_members', 'status')) {
                $table->string('status', 20)->default('active')->after('source')->index();
            }
            if (! Schema::hasColumn('lms_course_members', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('joined_at');
            }
            if (! Schema::hasColumn('lms_course_members', 'left_at')) {
                $table->timestamp('left_at')->nullable()->after('synced_at');
            }
        });

        if (Schema::hasTable('lms_course_members')) {
            DB::table('lms_course_members')->where('role', 'student')->update([
                'source' => 'class',
                'status' => 'active',
            ]);
            DB::table('lms_course_members')->whereIn('role', ['lecturer', 'assistant'])->update([
                'source' => 'legacy',
                'status' => 'active',
            ]);
        }

        if (! Schema::hasTable('lms_course_instructors')) {
            Schema::create('lms_course_instructors', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lms_course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('instructor_id')->constrained('instructors')->cascadeOnDelete();
                $table->string('role', 20)->default('lecturer');
                $table->string('source', 40)->default('manual');
                $table->timestamps();

                $table->unique(['lms_course_id', 'instructor_id'], 'lms_course_instructor_unique');
                $table->index(['instructor_id', 'role'], 'lms_course_instructor_lookup');
            });
        }

        if (Schema::hasTable('lms_course_instructors')) {
            DB::table('lms_courses')
                ->whereNotNull('instructor_id')
                ->orderBy('id')
                ->get(['id', 'instructor_id', 'created_at', 'updated_at'])
                ->each(function ($course): void {
                    DB::table('lms_course_instructors')->insertOrIgnore([
                        'lms_course_id' => $course->id,
                        'instructor_id' => $course->instructor_id,
                        'role' => 'lead',
                        'source' => 'legacy',
                        'created_at' => $course->created_at ?? now(),
                        'updated_at' => $course->updated_at ?? now(),
                    ]);
                });
        }

        Schema::table('lms_lessons', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_lessons', 'content_type')) {
                $table->string('content_type', 30)->default('supplemental')->after('subject_lesson_id')->index();
            }
            if (! Schema::hasColumn('lms_lessons', 'source_snapshot')) {
                $table->json('source_snapshot')->nullable()->after('content_type');
            }
            if (! Schema::hasColumn('lms_lessons', 'source_synced_at')) {
                $table->timestamp('source_synced_at')->nullable()->after('source_snapshot');
            }
        });

        if (Schema::hasTable('lms_lessons')) {
            DB::table('lms_lessons')->whereNotNull('subject_lesson_id')->update(['content_type' => 'curriculum']);
        }

        Schema::table('lms_attendance_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_attendance_sessions', 'schedule_detail_id')) {
                $table->foreignId('schedule_detail_id')
                    ->nullable()
                    ->after('lms_course_id')
                    ->constrained('schedule_details')
                    ->nullOnDelete();
                $table->unique('schedule_detail_id', 'lms_attendance_schedule_detail_unique');
            }
        });

        if (Schema::hasTable('system_settings')) {
            $now = now();
            foreach ([
                'grade_weight_assignments' => 40,
                'grade_weight_exams' => 40,
                'grade_weight_attendance' => 10,
                'grade_weight_progress' => 10,
            ] as $key => $value) {
                DB::table('system_settings')->insertOrIgnore([
                    'portal' => 'lms',
                    'key' => $key,
                    'value' => json_encode($value),
                    'type' => 'number',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('portal', 'lms')
                ->whereIn('key', [
                    'grade_weight_assignments',
                    'grade_weight_exams',
                    'grade_weight_attendance',
                    'grade_weight_progress',
                ])
                ->delete();
        }

        Schema::table('lms_attendance_sessions', function (Blueprint $table): void {
            if (Schema::hasColumn('lms_attendance_sessions', 'schedule_detail_id')) {
                $table->dropUnique('lms_attendance_schedule_detail_unique');
                $table->dropConstrainedForeignId('schedule_detail_id');
            }
        });

        Schema::table('lms_lessons', function (Blueprint $table): void {
            if (Schema::hasColumn('lms_lessons', 'content_type')) {
                $table->dropIndex(['content_type']);
            }
            foreach (['source_synced_at', 'source_snapshot', 'content_type'] as $column) {
                if (Schema::hasColumn('lms_lessons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('lms_course_instructors');

        Schema::table('lms_course_members', function (Blueprint $table): void {
            if (Schema::hasColumn('lms_course_members', 'status')) {
                $table->dropIndex(['status']);
            }
            if (Schema::hasColumn('lms_course_members', 'source')) {
                $table->dropIndex(['source']);
            }
            foreach (['left_at', 'synced_at', 'status', 'source'] as $column) {
                if (Schema::hasColumn('lms_course_members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('lms_courses', function (Blueprint $table): void {
            if (Schema::hasColumn('lms_courses', 'provision_key')) {
                $table->dropUnique(['provision_key']);
            }
            foreach (['source_id', 'source_type', 'term'] as $indexedColumn) {
                if (Schema::hasColumn('lms_courses', $indexedColumn)) {
                    $table->dropIndex([$indexedColumn]);
                }
            }
            foreach (['roster_synced_at', 'content_synced_at', 'provision_key', 'source_id', 'source_type', 'term'] as $column) {
                if (Schema::hasColumn('lms_courses', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('lms_courses', 'academic_year_id')) {
                $table->dropConstrainedForeignId('academic_year_id');
            }
        });
    }
};
