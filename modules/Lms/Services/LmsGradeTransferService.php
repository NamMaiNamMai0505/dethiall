<?php

namespace Modules\Lms\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Grades\Models\GradeAuditLog;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use Modules\Grades\Models\GradeColumn;
use Modules\Grades\Support\GradeSettings;
use Modules\Lms\Models\LmsCourse;

class LmsGradeTransferService
{
    public function __construct(protected LmsGradebookService $gradebook) {}

    /** Chuyển snapshot LMS sang một cột nháp của Quản lý điểm. */
    public function transfer(LmsCourse $course, User $actor): GradeBook
    {
        if (! $course->class_id || ! $course->subject_id) {
            throw new \RuntimeException('Khóa LMS phải gắn lớp và môn trước khi chuyển điểm.');
        }

        $this->gradebook->refreshStored($course);

        return DB::transaction(function () use ($course, $actor): GradeBook {
            $book = GradeBook::query()->firstOrCreate(
                ['lms_course_id' => $course->id],
                [
                    'class_id' => $course->class_id,
                    'subject_id' => $course->subject_id,
                    'instructor_id' => $course->instructor_id,
                    // Ghi cả chuỗi lẫn khóa ngoại: báo cáo cũ đọc chuỗi, thống kê
                    // mới join bằng academic_year_id.
                    'academic_year' => $course->academicYear?->code,
                    'academic_year_id' => $course->academic_year_id,
                    'title' => 'Bảng điểm '.$course->title,
                    'status' => GradeBook::STATUS_DRAFT,
                    'created_by' => $actor->id,
                ]
            );

            if (! in_array($book->status, [GradeBook::STATUS_DRAFT, GradeBook::STATUS_OPEN, GradeBook::STATUS_REVISION], true)) {
                throw new \RuntimeException('Bảng điểm đã khóa hoặc đang/đã phê duyệt nên LMS không được ghi đè.');
            }

            $column = GradeColumn::query()->updateOrCreate(
                ['grade_book_id' => $book->id, 'code' => 'lms_total'],
                [
                    'name' => 'Điểm tổng hợp LMS',
                    'source' => 'lms',
                    'max_score' => GradeSettings::maxScore(),
                    'weight' => null,
                    'sort_order' => 90,
                    'is_locked' => true,
                    'pdot_only' => false,
                ]
            );

            $rows = $course->gradebookRows()->get();
            foreach ($rows as $row) {
                if ($row->displayScore() === null) {
                    continue;
                }
                GradeCell::query()->updateOrCreate(
                    ['grade_column_id' => $column->id, 'user_id' => $row->user_id],
                    [
                        'grade_book_id' => $book->id,
                        'score' => GradeSettings::round((float) $row->displayScore()),
                        'note' => 'Snapshot LMS lúc '.now()->format('d/m/Y H:i'),
                        'updated_by' => $actor->id,
                    ]
                );
            }

            GradeAuditLog::record($book->id, 'lms_scores_transferred', [
                'lms_course_id' => $course->id,
                'rows' => $rows->count(),
            ]);

            return $book->fresh(['columns']);
        });
    }
}
