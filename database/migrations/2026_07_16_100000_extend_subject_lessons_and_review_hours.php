<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - subjects.review_hours: thời gian ôn (ngoài thời gian học tập)
 * - subject_lessons: phân cấp Unit → bài con, giờ LT/TH/thi, parent_id
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'review_hours')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedInteger('review_hours')->default(0)->after('exam_hours')
                    ->comment('Thời gian ôn (ngoài thời gian học tập)');
            });
        }

        if (! Schema::hasTable('subject_lessons')) {
            return;
        }

        Schema::table('subject_lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('subject_lessons', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('subject_id');
                $table->index('parent_id');
            }
            if (! Schema::hasColumn('subject_lessons', 'lesson_kind')) {
                // unit | chapter | lesson | sub | exam
                $table->string('lesson_kind', 20)->default('lesson')->after('name');
            }
            if (! Schema::hasColumn('subject_lessons', 'theory_hours')) {
                $table->unsignedInteger('theory_hours')->default(0)->after('sort_order');
            }
            if (! Schema::hasColumn('subject_lessons', 'practice_hours')) {
                $table->unsignedInteger('practice_hours')->default(0)->after('theory_hours');
            }
            if (! Schema::hasColumn('subject_lessons', 'exam_hours')) {
                $table->unsignedInteger('exam_hours')->default(0)->after('practice_hours');
            }
            if (! Schema::hasColumn('subject_lessons', 'total_hours')) {
                $table->unsignedInteger('total_hours')->default(0)->after('exam_hours');
            }
            if (! Schema::hasColumn('subject_lessons', 'semester')) {
                $table->string('semester', 30)->nullable()->after('total_hours');
            }
        });

        // FK parent (self) — optional
        try {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('subject_lessons')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // already exists
        }

        // Nới unique code: cho phép code dài hơn (sub-lesson)
        try {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->string('code', 100)->change();
            });
        } catch (\Throwable $e) {
            // doctrine/dbal optional
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'review_hours')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('review_hours');
            });
        }

        if (Schema::hasTable('subject_lessons')) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                try {
                    $table->dropForeign(['parent_id']);
                } catch (\Throwable $e) {
                }
                foreach (['parent_id', 'lesson_kind', 'theory_hours', 'practice_hours', 'exam_hours', 'total_hours', 'semester'] as $col) {
                    if (Schema::hasColumn('subject_lessons', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
