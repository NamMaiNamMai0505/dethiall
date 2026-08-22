<?php

namespace Modules\Grades\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Grades\Models\GradeAuditLog;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use Modules\Grades\Models\GradeChangeRequest;
use Modules\Grades\Models\GradeColumn;
use Modules\Grades\Support\GradeSettings;

class GradeBookService
{
    public function createWithDefaultColumns(array $data): GradeBook
    {
        return DB::transaction(function () use ($data) {
            $maxScore = GradeSettings::maxScore();
            $book = GradeBook::query()->create([
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id'],
                'instructor_id' => $data['instructor_id'] ?? Auth::user()?->instructor_id,
                'lms_course_id' => $data['lms_course_id'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'title' => $data['title'] ?? 'Bảng điểm',
                'status' => GradeBook::STATUS_OPEN,
                'created_by' => Auth::id(),
            ]);

            $defaults = [
                ['code' => 'oral_15', 'name' => 'Kiểm tra 15 phút', 'sort_order' => 1, 'pdot_only' => false],
                ['code' => 'period_1', 'name' => '1 tiết', 'sort_order' => 2, 'pdot_only' => false],
                ['code' => 'midterm', 'name' => 'Giữa kỳ', 'sort_order' => 3, 'pdot_only' => false],
                ['code' => 'final', 'name' => 'Điểm thi (PDOT)', 'sort_order' => 4, 'pdot_only' => true],
            ];
            foreach ($defaults as $col) {
                GradeColumn::query()->create([
                    'grade_book_id' => $book->id,
                    'code' => $col['code'],
                    'name' => $col['name'],
                    'source' => 'manual',
                    'max_score' => $maxScore,
                    'sort_order' => $col['sort_order'],
                    'pdot_only' => $col['pdot_only'],
                ]);
            }

            GradeAuditLog::record($book->id, 'created');

            return $book->load('columns');
        });
    }

    public function saveScores(GradeBook $book, array $scores, User $user): void
    {
        DB::transaction(function () use ($book, $scores, $user): void {
            foreach ($scores as $columnId => $byStudent) {
                $column = GradeColumn::query()->where('grade_book_id', $book->id)->whereKey($columnId)->first();
                if (! $column || ! GradeAccess::canEditCell($user, $book, $column)) {
                    continue;
                }
                foreach ($byStudent as $studentId => $score) {
                    if ($score === '' || $score === null) {
                        GradeCell::query()
                            ->where('grade_column_id', $column->id)
                            ->where('user_id', $studentId)
                            ->delete();

                        continue;
                    }
                    if (! is_numeric($score) || (float) $score < 0 || (float) $score > (float) $column->max_score) {
                        throw new \RuntimeException(
                            "Điểm phải từ 0 đến {$column->max_score} cho cột {$column->name}."
                        );
                    }
                    GradeCell::query()->updateOrCreate(
                        [
                            'grade_column_id' => $column->id,
                            'user_id' => $studentId,
                        ],
                        [
                            'grade_book_id' => $book->id,
                            'score' => GradeSettings::round((float) $score),
                            'updated_by' => $user->id,
                        ]
                    );
                }
            }
            GradeAuditLog::record($book->id, 'scores_saved');
        });
    }

    public function lockByInstructor(GradeBook $book, User $user): void
    {
        if (! $book->isEditableByInstructor()) {
            throw new \RuntimeException('Bảng điểm không ở trạng thái cho phép khóa.');
        }
        $book->update([
            'status' => GradeBook::STATUS_PENDING_PDOT,
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);
        GradeAuditLog::record($book->id, 'locked_gv');
    }

    public function approveByPdot(GradeBook $book, User $user): void
    {
        $book->update([
            'status' => GradeBook::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
        GradeAuditLog::record($book->id, 'approved');
    }

    public function requestUnlock(GradeBook $book, User $user, string $reason): GradeChangeRequest
    {
        $req = GradeChangeRequest::query()->create([
            'grade_book_id' => $book->id,
            'requested_by' => $user->id,
            'status' => GradeChangeRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);
        GradeAuditLog::record($book->id, 'unlock_requested', ['request_id' => $req->id]);

        return $req;
    }

    public function pdotForwardRequest(GradeChangeRequest $req, User $user, ?string $note = null): void
    {
        $req->update([
            'status' => GradeChangeRequest::STATUS_PDOT_OK,
            'pdot_note' => $note,
            'pdot_reviewed_by' => $user->id,
            'pdot_reviewed_at' => now(),
        ]);
        GradeAuditLog::record($req->grade_book_id, 'unlock_pdot_forward', ['request_id' => $req->id]);
    }

    public function directorApproveRequest(GradeChangeRequest $req, User $user, ?string $note = null): void
    {
        $req->update([
            'status' => GradeChangeRequest::STATUS_APPROVED,
            'director_note' => $note,
            'director_reviewed_by' => $user->id,
            'director_reviewed_at' => now(),
        ]);
        $req->book->update(['status' => GradeBook::STATUS_REVISION]);
        GradeAuditLog::record($req->grade_book_id, 'unlock_approved', ['request_id' => $req->id]);
    }

    public function rejectRequest(GradeChangeRequest $req, User $user, ?string $note = null): void
    {
        $req->update([
            'status' => GradeChangeRequest::STATUS_REJECTED,
            'director_note' => $note ?? $req->director_note,
            'pdot_note' => $note ?? $req->pdot_note,
            'director_reviewed_by' => $user->id,
            'director_reviewed_at' => now(),
        ]);
        GradeAuditLog::record($req->grade_book_id, 'unlock_rejected', ['request_id' => $req->id]);
    }

    /** Students of class (users with class_id). */
    public function studentsForBook(GradeBook $book)
    {
        return User::query()
            ->where('class_id', $book->class_id)
            ->where('user_type', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'email']);
    }
}
