<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - subjects.color: màu nhận diện môn (xuất lịch huấn luyện)
 * - schedule_details.subject_lesson_id: bài học do Khoa phân bổ
 * - schedule_details: cho phép instructor/classroom null (khung lịch PDOT)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'color')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('color', 20)
                    ->nullable()
                    ->after('abbreviation')
                    ->comment('Màu nhận diện môn (#RRGGBB) — xuất lịch huấn luyện');
            });
        }

        if (Schema::hasTable('schedule_details')) {
            Schema::table('schedule_details', function (Blueprint $table) {
                if (! Schema::hasColumn('schedule_details', 'subject_lesson_id')) {
                    $table->unsignedBigInteger('subject_lesson_id')->nullable()->after('subject_id');
                    $table->index('subject_lesson_id');
                }
            });

            // FK optional — chỉ khi bảng subject_lessons đã có
            if (Schema::hasTable('subject_lessons') && Schema::hasColumn('schedule_details', 'subject_lesson_id')) {
                try {
                    Schema::table('schedule_details', function (Blueprint $table) {
                        $table->foreign('subject_lesson_id')
                            ->references('id')
                            ->on('subject_lessons')
                            ->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                    // FK có thể đã tồn tại
                }
            }

            // Nới null instructor/classroom cho khung lịch Phòng ĐT
            try {
                Schema::table('schedule_details', function (Blueprint $table) {
                    $table->unsignedBigInteger('instructor_id')->nullable()->change();
                    $table->unsignedBigInteger('classroom_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // change() cần doctrine/dbal — fallback skip
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schedule_details') && Schema::hasColumn('schedule_details', 'subject_lesson_id')) {
            Schema::table('schedule_details', function (Blueprint $table) {
                try {
                    $table->dropForeign(['subject_lesson_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('subject_lesson_id');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'color')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
