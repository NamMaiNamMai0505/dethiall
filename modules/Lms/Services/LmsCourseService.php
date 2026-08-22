<?php

namespace Modules\Lms\Services;

use App\Models\User;
use App\Support\TrainingDept;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\TeachingAssignment\Models\TeachingAssignment;

/**
 * Course CRUD + map Class/Subject/Instructor/Student từ hệ thống lõi.
 */
class LmsCourseService
{
    public function __construct(protected LmsCourseProvisioningService $provisioning) {}

    public function queryForUser(?User $user = null): Builder
    {
        $user = $user ?: Auth::user();
        $query = LmsCourse::query()
            ->with(['subject', 'classModel', 'instructor', 'academicYear'])
            ->withCount(['lessons', 'members']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $query;
        }

        // Khoa luôn bị giới hạn theo đơn vị trước khi xét quyền lms.manage của
        // role manager cũ. Nếu đảo thứ tự, manager Khoa sẽ nhìn thấy toàn LMS.
        if (TrainingDept::isFacultyManager($user)) {
            // Khoa: course có môn thuộc khoa hoặc GV thuộc unit
            $query->where(function (Builder $q) use ($user) {
                $q->whereHas('subject', function (Builder $subjectQuery) use ($user) {
                    TrainingDept::applySubjectFacultyScope($subjectQuery, $user);
                });
                if ($user->unit_id) {
                    $q->orWhereHas('instructor', fn (Builder $iq) => $iq->where('unit_id', $user->unit_id));
                }
            });

            return $query;
        }

        if (TrainingDept::unitIsTrainingOffice($user) || $user->can('lms.manage')) {
            return $query;
        }

        if (method_exists($user, 'isInstructor') && $user->isInstructor()) {
            $instructorId = $user->instructor_id ?? null;

            return $query->where(function (Builder $q) use ($user, $instructorId) {
                $q->whereHas('members', fn (Builder $m) => $m
                    ->where('user_id', $user->id)
                    ->where('status', LmsCourseMember::STATUS_ACTIVE));
                if ($instructorId) {
                    $q->orWhere('instructor_id', $instructorId);
                }
            });
        }

        if (method_exists($user, 'isStudent') && $user->isStudent()) {
            return $query->where(function (Builder $q) use ($user) {
                $q->whereHas('members', fn (Builder $m) => $m
                    ->where('user_id', $user->id)
                    ->where('role', LmsCourseMember::ROLE_STUDENT)
                    ->where('status', LmsCourseMember::STATUS_ACTIVE))
                    ->orWhere(function (Builder $q2) use ($user) {
                        if ($user->class_id) {
                            $q2->where('class_id', $user->class_id)->where('status', LmsCourse::STATUS_PUBLISHED);
                        } else {
                            $q2->whereRaw('1 = 0');
                        }
                    });
            });
        }

        // Manager khác: membership
        return $query->whereHas('members', fn (Builder $m) => $m
            ->where('user_id', $user->id)
            ->where('status', LmsCourseMember::STATUS_ACTIVE));
    }

    public function paginateForUser(int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryForUser()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Tạo course thủ công và đồng bộ roster chính xác theo lớp/GV đã chọn.
     *
     * @param  array{title:string,subject_id:int,class_id:int,instructor_id?:int|null,description?:string,status?:string,code?:string,starts_at?:string,ends_at?:string}  $data
     */
    public function createWithMembers(array $data): LmsCourse
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();
            $instructorId = $data['instructor_id'] ?? null;

            $standalone = ! empty($data['is_standalone']);

            $course = LmsCourse::create([
                'code' => $data['code'] ?? null,
                'section_code' => $data['section_code'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'subject_id' => $data['subject_id'],
                'class_id' => $standalone ? ($data['class_id'] ?? null) : ($data['class_id'] ?? null),
                'instructor_id' => $instructorId,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'term' => $data['term'] ?? null,
                'source_type' => 'manual',
                'status' => $data['status'] ?? LmsCourse::STATUS_DRAFT,
                'is_standalone' => $standalone,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->provisioning->registerManualInstructor($course, $instructorId);
            // Sprint 44 / C3 — wizard trước đây chỉ gán đúng 1 GV chọn tay.
            // Kéo thêm toàn bộ GV đã được Khoa phân công dạy môn này (không
            // ghi đè GV chọn tay: khác nguồn "manual" vs "teaching_assignment",
            // cùng instructor_id thì updateOrCreate gộp làm 1 dòng).
            $this->provisioning->syncInstructorsFromTeachingAssignment($course);
            $this->provisioning->syncRoster($course);
            $this->provisioning->syncCurriculumLessons($course);

            return $course->fresh(['subject', 'classModel', 'instructor', 'members']);
        });
    }

    /**
     * Đồng bộ roster chính xác từ lớp và danh sách GV của riêng khóa học.
     */
    public function syncMembersFromCore(LmsCourse $course): void
    {
        $this->provisioning->syncRoster($course);
    }

    public function update(LmsCourse $course, array $data): LmsCourse
    {
        $course->fill([
            'code' => $data['code'] ?? $course->code,
            'title' => $data['title'] ?? $course->title,
            'description' => $data['description'] ?? $course->description,
            'instructor_id' => array_key_exists('instructor_id', $data) ? $data['instructor_id'] : $course->instructor_id,
            'academic_year_id' => array_key_exists('academic_year_id', $data) ? $data['academic_year_id'] : $course->academic_year_id,
            'term' => array_key_exists('term', $data) ? $data['term'] : $course->term,
            'status' => $data['status'] ?? $course->status,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $course->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $course->ends_at,
            'updated_by' => Auth::id(),
        ]);
        $course->save();

        if (array_key_exists('instructor_id', $data)) {
            $course->courseInstructors()->where('role', 'lead')->delete();
            $this->provisioning->registerManualInstructor($course, $course->instructor_id);
        }

        if (! empty($data['resync_members'])) {
            $this->syncMembersFromCore($course);
        }

        return $course->fresh(['subject', 'classModel', 'instructor']);
    }

    public function suggestInstructorsForSubject(int $subjectId, ?User $user = null)
    {
        $user ??= Auth::user();
        $ids = TeachingAssignment::query()->where('subject_id', $subjectId)->pluck('instructor_id');

        return Instructor::query()
            ->whereIn('id', $ids)
            ->when(
                $user && TrainingDept::isFacultyManager($user),
                fn (Builder $query) => $query->where('unit_id', $user->unit_id ?: -1)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}
