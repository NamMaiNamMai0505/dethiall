<?php

namespace Modules\Grades\Services;

use App\Models\User;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use App\Support\ApprovalAgency;
use App\Support\TrainingDept;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GradeBook;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\Unit\Models\Unit;

/**
 * Phân quyền + wizard chọn phạm vi điểm:
 * - GV: Môn → Lớp (theo lịch / LMS)
 * - PDOT / super-admin: Khoa → Môn → Lớp
 */
class GradeAccess
{
    public static function canEnter(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (ApprovalAgency::isResearchAgency($user)) {
            return false;
        }
        // Quyền chi tiết của bất kỳ ứng dụng Quản lý điểm nào cũng mở được cửa
        // phân hệ; quyền tổng cũ vẫn được chấp nhận qua ApplicationGate.
        if (ApplicationGate::allows($user, 'grades', ApplicationRegistry::ACTION_VIEW)
            || ApplicationGate::allows($user, 'grades.books', ApplicationRegistry::ACTION_VIEW)) {
            return true;
        }

        return $user->isInstructor() || $user->isManager();
    }

    public static function isDirector(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (ApprovalAgency::isResearchAgency($user)) {
            return false;
        }
        if (ApplicationGate::allows($user, 'grades', ApplicationRegistry::ACTION_APPROVE)) {
            return true;
        }

        return $user->isManager() && TrainingDept::unitIsTrainingOffice($user);
    }

    public static function isPdot(User $user): bool
    {
        if (ApprovalAgency::isResearchAgency($user)) {
            return false;
        }

        if (self::isDirector($user)) {
            return true;
        }

        return $user->isManager()
            || ApplicationGate::allows($user, 'grades.books', ApplicationRegistry::ACTION_EDIT)
            || ApplicationGate::allows($user, 'grades.approval', ApplicationRegistry::ACTION_APPROVE);
    }

    /** PDOT / super-admin: wizard Khoa → Môn → Lớp */
    public static function usesFacultyWizard(User $user): bool
    {
        if (ApprovalAgency::isResearchAgency($user)) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->can('grades.manage')) {
            return true;
        }

        return $user->isManager() && TrainingDept::unitIsTrainingOffice($user);
    }

    /** GV (không phải PDOT full): wizard Môn → Lớp */
    public static function usesInstructorWizard(User $user): bool
    {
        return $user->isInstructor() && ! self::usesFacultyWizard($user);
    }

    public static function isFullScope(User $user): bool
    {
        return self::usesFacultyWizard($user);
    }

    /**
     * Cặp lớp–môn GV đang dạy.
     *
     * @param  list<int>|null  $instructorIds
     * @return Collection<int, array{class_id:int, subject_id:int, instructor_id?:int}>
     */
    public static function teachingPairs(User $user, ?array $instructorIds = null): Collection
    {
        $pairs = collect();

        if ($instructorIds === null) {
            if ($user->isInstructor() && $user->instructor_id) {
                $instructorIds = [(int) $user->instructor_id];
            } elseif ($user->isManager() && $user->unit_id && ! self::usesFacultyWizard($user)) {
                $instructorIds = Instructor::query()
                    ->where('unit_id', $user->unit_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } else {
                return $pairs;
            }
        }

        if ($instructorIds === []) {
            return $pairs;
        }

        if (Schema::hasTable('schedule_details') && Schema::hasTable('training_schedules')) {
            $fromSchedule = DB::table('schedule_details as sd')
                ->join('training_schedules as ts', 'ts.id', '=', 'sd.training_schedule_id')
                ->whereIn('sd.instructor_id', $instructorIds)
                ->whereNotNull('ts.class_id')
                ->whereNotNull('sd.subject_id')
                ->when(
                    Schema::hasColumn('training_schedules', 'deleted_at'),
                    fn ($q) => $q->whereNull('ts.deleted_at')
                )
                ->select('ts.class_id', 'sd.subject_id', 'sd.instructor_id')
                ->distinct()
                ->get();

            foreach ($fromSchedule as $row) {
                $pairs->push([
                    'class_id' => (int) $row->class_id,
                    'subject_id' => (int) $row->subject_id,
                    'instructor_id' => (int) $row->instructor_id,
                ]);
            }
        }

        if (class_exists(LmsCourse::class) && Schema::hasTable('lms_courses')) {
            $fromLms = LmsCourse::query()
                ->whereIn('instructor_id', $instructorIds)
                ->whereNotNull('class_id')
                ->whereNotNull('subject_id')
                ->get(['class_id', 'subject_id', 'instructor_id']);

            foreach ($fromLms as $row) {
                $pairs->push([
                    'class_id' => (int) $row->class_id,
                    'subject_id' => (int) $row->subject_id,
                    'instructor_id' => (int) $row->instructor_id,
                ]);
            }
        }

        if (Schema::hasTable('grade_books')) {
            $fromBooks = GradeBook::query()
                ->whereIn('instructor_id', $instructorIds)
                ->whereNotNull('class_id')
                ->whereNotNull('subject_id')
                ->get(['class_id', 'subject_id', 'instructor_id']);

            foreach ($fromBooks as $row) {
                $pairs->push([
                    'class_id' => (int) $row->class_id,
                    'subject_id' => (int) $row->subject_id,
                    'instructor_id' => (int) ($row->instructor_id ?? 0),
                ]);
            }
        }

        return $pairs
            ->filter(fn ($p) => $p['class_id'] > 0 && $p['subject_id'] > 0)
            ->unique(fn ($p) => $p['class_id'].':'.$p['subject_id'].':'.($p['instructor_id'] ?? 0))
            ->values();
    }

    /** Môn GV đang dạy (từ teaching pairs). */
    public static function accessibleSubjects(User $user): Collection
    {
        if (self::usesFacultyWizard($user)) {
            return Subject::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        $ids = self::teachingPairs($user)->pluck('subject_id')->unique()->filter()->values()->all();
        if ($ids === []) {
            return collect();
        }

        return Subject::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code']);
    }

    /** Lớp GV dạy một môn cụ thể. */
    public static function classesForSubject(User $user, int $subjectId): Collection
    {
        if (self::usesFacultyWizard($user)) {
            // PDOT: lớp có lịch/LMS/gradebook môn này, hoặc mọi lớp nếu full
            $classIds = collect();
            if (Schema::hasTable('schedule_details') && Schema::hasTable('training_schedules')) {
                $classIds = $classIds->merge(
                    DB::table('schedule_details as sd')
                        ->join('training_schedules as ts', 'ts.id', '=', 'sd.training_schedule_id')
                        ->where('sd.subject_id', $subjectId)
                        ->whereNotNull('ts.class_id')
                        ->pluck('ts.class_id')
                );
            }
            if (Schema::hasTable('lms_courses')) {
                $classIds = $classIds->merge(
                    LmsCourse::query()->where('subject_id', $subjectId)->whereNotNull('class_id')->pluck('class_id')
                );
            }
            if (Schema::hasTable('grade_books')) {
                $classIds = $classIds->merge(
                    GradeBook::query()->where('subject_id', $subjectId)->pluck('class_id')
                );
            }
            $ids = $classIds->filter()->unique()->values()->all();
            if ($ids === []) {
                return ClassModel::query()->orderBy('name')->get(['id', 'name', 'code', 'is_active']);
            }

            return ClassModel::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code', 'is_active']);
        }

        $ids = self::teachingPairs($user)
            ->where('subject_id', $subjectId)
            ->pluck('class_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return ClassModel::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code', 'is_active']);
    }

    /**
     * Danh sách khoa (unit) cho wizard PDOT.
     * Ưu tiên unit code K1–K8; fallback unit có GV.
     */
    public static function accessibleFaculties(User $user): Collection
    {
        if (! self::usesFacultyWizard($user)) {
            return collect();
        }

        $q = Unit::query()->orderBy('code')->orderBy('name');
        if (Schema::hasColumn('units', 'status')) {
            $q->where(function ($w) {
                $w->where('status', Unit::STATUS_ACTIVE)->orWhereNull('status');
            });
        }

        // Lọc theo functional_type = Khoa thay vì danh sách mã cứng K1-K8 —
        // Khoa mới (K9, hoặc quy ước khác) phải hiện ra ngay trong wizard mà
        // không cần sửa code. Sprint 44 §2.2.
        $units = $q->get(['id', 'name', 'code', 'functional_type']);
        $faculty = $units->filter(fn ($u) => $u->functional_type === Unit::FUNCTIONAL_FACULTY);

        if ($faculty->isNotEmpty()) {
            return $faculty->values();
        }

        // Fallback: units có giảng viên
        $withGv = Instructor::query()->whereNotNull('unit_id')->distinct()->pluck('unit_id');

        return $units->whereIn('id', $withGv)->values();
    }

    /** Môn trong khoa (GV thuộc unit + hậu tố mã môn nếu có). */
    public static function subjectsForFaculty(User $user, int $unitId): Collection
    {
        if (! self::usesFacultyWizard($user)) {
            return collect();
        }

        $instructorIds = Instructor::query()->where('unit_id', $unitId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $pairs = self::teachingPairs($user, $instructorIds);
        $ids = $pairs->pluck('subject_id')->unique()->filter()->values()->all();

        // Bổ sung môn theo hậu tố khoa (nếu có SubjectCodePrefix)
        $unit = Unit::query()->find($unitId);
        $code = strtoupper((string) ($unit?->code ?? ''));
        if ($code !== '' && class_exists(SubjectCodePrefix::class)) {
            try {
                $q = Subject::query();
                SubjectCodePrefix::applyFacultyCodeScope($q, $code);
                $ids = collect($ids)->merge($q->pluck('id'))->unique()->values()->all();
            } catch (\Throwable) {
                // ignore
            }
        }

        if ($ids === []) {
            return Subject::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        return Subject::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code']);
    }

    /** Lớp trong khoa + môn. */
    public static function classesForFacultySubject(User $user, int $unitId, int $subjectId): Collection
    {
        if (! self::usesFacultyWizard($user)) {
            return collect();
        }

        $instructorIds = Instructor::query()->where('unit_id', $unitId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $pairs = self::teachingPairs($user, $instructorIds)->where('subject_id', $subjectId);
        $ids = $pairs->pluck('class_id')->unique()->filter()->values()->all();

        // Thêm từ lịch/LMS toàn môn (PDOT duyệt rộng)
        if (Schema::hasTable('schedule_details') && Schema::hasTable('training_schedules')) {
            $q = DB::table('schedule_details as sd')
                ->join('training_schedules as ts', 'ts.id', '=', 'sd.training_schedule_id')
                ->where('sd.subject_id', $subjectId)
                ->whereNotNull('ts.class_id');
            if ($instructorIds !== []) {
                $q->where(function ($w) use ($instructorIds) {
                    $w->whereIn('sd.instructor_id', $instructorIds)->orWhereNotNull('sd.instructor_id');
                });
            }
            $ids = collect($ids)->merge($q->pluck('ts.class_id'))->unique()->filter()->values()->all();
        }
        if (Schema::hasTable('lms_courses')) {
            $ids = collect($ids)->merge(
                LmsCourse::query()
                    ->where('subject_id', $subjectId)
                    ->when($instructorIds !== [], fn ($q) => $q->whereIn('instructor_id', $instructorIds))
                    ->whereNotNull('class_id')
                    ->pluck('class_id')
            )->unique()->filter()->values()->all();
        }

        if ($ids === []) {
            return ClassModel::query()->orderBy('name')->get(['id', 'name', 'code', 'is_active']);
        }

        return ClassModel::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'code', 'is_active']);
    }

    public static function booksQuery(User $user): Builder
    {
        $q = GradeBook::query()->with(['classModel', 'subject', 'instructor']);

        if (self::usesFacultyWizard($user)) {
            return $q;
        }

        if ($user->isInstructor() && $user->instructor_id) {
            $pairs = self::teachingPairs($user);
            if ($pairs->isEmpty()) {
                return $q->where('instructor_id', $user->instructor_id);
            }

            return $q->where(function (Builder $inner) use ($user, $pairs) {
                $inner->where('instructor_id', $user->instructor_id);
                $inner->orWhere(function (Builder $or) use ($pairs) {
                    foreach ($pairs as $p) {
                        $or->orWhere(function (Builder $w) use ($p) {
                            $w->where('class_id', $p['class_id'])
                                ->where('subject_id', $p['subject_id']);
                        });
                    }
                });
            });
        }

        if ($user->isManager() && $user->unit_id) {
            $instructorIds = Instructor::query()->where('unit_id', $user->unit_id)->pluck('id');

            return $q->whereIn('instructor_id', $instructorIds);
        }

        return $q->whereRaw('1 = 0');
    }

    public static function canAccessClass(User $user, int $classId): bool
    {
        if (self::usesFacultyWizard($user)) {
            return true;
        }

        return self::teachingPairs($user)->contains(fn ($p) => (int) $p['class_id'] === $classId);
    }

    public static function canAccessClassSubject(User $user, int $classId, int $subjectId): bool
    {
        if (self::usesFacultyWizard($user)) {
            return true;
        }

        return self::teachingPairs($user)->contains(
            fn ($p) => (int) $p['class_id'] === $classId && (int) $p['subject_id'] === $subjectId
        );
    }

    public static function canAccessSubject(User $user, int $subjectId): bool
    {
        if (self::usesFacultyWizard($user)) {
            return true;
        }

        return self::teachingPairs($user)->contains(fn ($p) => (int) $p['subject_id'] === $subjectId);
    }

    public static function canEditBook(User $user, GradeBook $book): bool
    {
        if ($user->isSuperAdmin() || self::isDirector($user)) {
            return true;
        }
        if (self::isPdot($user) && in_array($book->status, [
            GradeBook::STATUS_LOCKED_GV,
            GradeBook::STATUS_PENDING_PDOT,
            GradeBook::STATUS_REVISION,
            GradeBook::STATUS_OPEN,
            GradeBook::STATUS_DRAFT,
        ], true)) {
            return true;
        }
        if ($user->isInstructor() && (int) $user->instructor_id === (int) $book->instructor_id) {
            return $book->isEditableByInstructor();
        }

        return false;
    }

    public static function canEditCell(User $user, GradeBook $book, $column): bool
    {
        if ($user->isSuperAdmin() || self::isDirector($user)) {
            return true;
        }
        if ($column->pdot_only) {
            return self::isPdot($user);
        }
        if ($user->isInstructor() && (int) $user->instructor_id === (int) $book->instructor_id) {
            return $book->isEditableByInstructor() && ! $column->is_locked;
        }
        if (self::isPdot($user)) {
            return in_array($book->status, [
                GradeBook::STATUS_REVISION,
                GradeBook::STATUS_OPEN,
                GradeBook::STATUS_DRAFT,
                GradeBook::STATUS_LOCKED_GV,
                GradeBook::STATUS_PENDING_PDOT,
            ], true);
        }

        return false;
    }
}
