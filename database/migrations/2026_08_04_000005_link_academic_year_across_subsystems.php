<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nối năm học giữa Lịch đào tạo → LMS → Quản lý điểm.
 *
 * Trước đây `lms_courses.academic_year_id` để trống ở mọi khóa, kéo theo
 * `grade_books.academic_year` cũng rỗng khi LMS chuyển điểm sang — mọi báo cáo
 * lọc theo năm học trả về rỗng. Migration suy ngược năm học từ lịch đào tạo của
 * lớp và bổ sung khóa ngoại cho bảng điểm.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_books') && ! Schema::hasColumn('grade_books', 'academic_year_id')) {
            Schema::table('grade_books', function (Blueprint $table): void {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('academic_year')
                    ->constrained('academic_years')
                    ->nullOnDelete();
            });
        }

        $this->backfillLmsCourses();
        $this->backfillGradeBooks();
    }

    public function down(): void
    {
        if (Schema::hasTable('grade_books') && Schema::hasColumn('grade_books', 'academic_year_id')) {
            Schema::table('grade_books', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('academic_year_id');
            });
        }
    }

    /**
     * Khóa LMS chưa có năm học → lấy từ lịch đào tạo của lớp, khớp bằng
     * `academic_years.code` (chuỗi dùng chung với training_schedules).
     */
    private function backfillLmsCourses(): void
    {
        if (! Schema::hasTable('lms_courses') || ! Schema::hasTable('academic_years')) {
            return;
        }

        $years = DB::table('academic_years')->pluck('id', 'code');
        if ($years->isEmpty()) {
            return;
        }

        $courses = DB::table('lms_courses')
            ->whereNull('academic_year_id')
            ->whereNotNull('class_id')
            ->get(['id', 'class_id']);

        foreach ($courses as $course) {
            $code = DB::table('training_schedules')
                ->where('class_id', $course->class_id)
                ->whereNotNull('academic_year')
                ->orderByDesc('id')
                ->value('academic_year');

            if (! $code || ! isset($years[$code])) {
                continue;
            }

            DB::table('lms_courses')
                ->where('id', $course->id)
                ->update(['academic_year_id' => $years[$code]]);
        }
    }

    /** Bảng điểm: đồng bộ khóa ngoại năm học từ chuỗi sẵn có hoặc từ khóa LMS. */
    private function backfillGradeBooks(): void
    {
        if (! Schema::hasTable('grade_books') || ! Schema::hasColumn('grade_books', 'academic_year_id')) {
            return;
        }

        $years = DB::table('academic_years')->pluck('id', 'code');

        foreach (DB::table('grade_books')->whereNull('academic_year_id')->get(['id', 'academic_year', 'lms_course_id']) as $book) {
            $yearId = null;

            if ($book->academic_year && isset($years[$book->academic_year])) {
                $yearId = $years[$book->academic_year];
            } elseif ($book->lms_course_id) {
                $yearId = DB::table('lms_courses')->where('id', $book->lms_course_id)->value('academic_year_id');
            }

            if ($yearId === null) {
                continue;
            }

            $code = $years->search($yearId) ?: $book->academic_year;

            DB::table('grade_books')->where('id', $book->id)->update([
                'academic_year_id' => $yearId,
                'academic_year' => $code,
            ]);
        }
    }
};
