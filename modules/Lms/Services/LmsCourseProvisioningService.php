<?php

namespace Modules\Lms\Services;

use App\Models\AcademicYear;
use App\Models\User;
use App\Support\TrainingDept;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Class\Models\ClassModel;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseInstructor;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Support\LmsSettings;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class LmsCourseProvisioningService
{
    /**
     * Ghi danh toàn bộ môn đang hoạt động của ngành vào lớp hành chính.
     * LmsCourse chính là bản ghi liên kết Lớp × Môn; người dùng không phải
     * tạo lại môn/bài trong LMS.
     *
     * @return array{created:int,updated:int,archived:int,courses:Collection<int,LmsCourse>,lessons_created:int,lessons_updated:int}
     */
    public function provisionFromClassCurriculum(
        ClassModel $class,
        ?User $actor = null,
        bool $syncContent = true,
    ): array {
        if (
            ! Schema::hasTable('lms_courses')
            || ! Schema::hasTable('lms_course_instructors')
            || ! Schema::hasColumn('lms_courses', 'provision_key')
        ) {
            return [
                'created' => 0,
                'updated' => 0,
                'archived' => 0,
                'courses' => collect(),
                'lessons_created' => 0,
                'lessons_updated' => 0,
            ];
        }

        $actor ??= Auth::user();
        $subjects = $class->is_active && $class->specialization_id
            ? Subject::query()
                ->active()
                ->where('specialization_id', $class->specialization_id)
                ->orderBy('semester')
                ->orderBy('id')
                ->get()
            : collect();
        $desiredSubjectIds = $subjects->pluck('id')->map(fn ($id) => (int) $id)->all();
        $created = 0;
        $updated = 0;
        $archived = 0;
        $lessonsCreated = 0;
        $lessonsUpdated = 0;
        $courses = collect();

        foreach ($subjects as $subject) {
            $provisionKey = "class-curriculum:{$class->id}:subject:{$subject->id}";
            $existing = LmsCourse::withTrashed()
                ->where('class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->where('is_standalone', false)
                ->first();
            $isNew = ! $existing;

            $course = DB::transaction(function () use ($class, $subject, $provisionKey, $existing, $actor): LmsCourse {
                $course = $existing ?: new LmsCourse;
                if ($course->trashed()) {
                    $course->restore();
                }

                $course->fill([
                    'code' => $course->code ?: $this->classCourseCode($class, $subject),
                    'title' => $course->title ?: trim($subject->name.' · '.$class->name),
                    'subject_id' => $subject->id,
                    'class_id' => $class->id,
                    'term' => $subject->semester,
                    'is_standalone' => false,
                    'starts_at' => $class->start_date,
                    'ends_at' => $class->end_date,
                    'status' => $course->exists
                        ? ($course->source_type === 'class_curriculum' && $course->status === LmsCourse::STATUS_ARCHIVED
                            ? LmsSettings::courseStatus()
                            : $course->status)
                        : LmsSettings::courseStatus(),
                    'created_by' => $course->created_by ?: ($actor?->id ?? $class->created_by),
                    'updated_by' => $actor?->id ?? $class->updated_by ?? $class->created_by,
                ]);

                // Không đổi nguồn của khóa đã được liên kết với lịch đào tạo.
                if (! $course->exists || ! $course->source_type) {
                    $course->source_type = 'class_curriculum';
                    $course->source_id = $class->id;
                    $course->provision_key = $provisionKey;
                }
                $course->save();

                $this->syncInstructorsFromTeachingAssignment($course);
                $this->syncRoster($course);

                return $course->fresh();
            });

            if ($syncContent) {
                $content = $this->syncCurriculumLessons($course);
                $lessonsCreated += $content['created'];
                $lessonsUpdated += $content['updated'];
            }

            $isNew ? $created++ : $updated++;
            $courses->push($course->fresh(['subject', 'classModel', 'instructors']));
        }

        // Khi đổi ngành, chỉ lưu trữ các khóa do đồng bộ ngành cũ sinh ra;
        // khóa tay hoặc khóa đã gắn lịch vẫn được giữ nguyên để bảo toàn dữ liệu.
        $obsolete = LmsCourse::query()
            ->where('class_id', $class->id)
            ->where('source_type', 'class_curriculum')
            ->where('source_id', $class->id)
            ->when(
                $desiredSubjectIds !== [],
                fn ($query) => $query->whereNotIn('subject_id', $desiredSubjectIds),
                fn ($query) => $query->whereRaw('1 = 1')
            );
        $archived = (clone $obsolete)->count();
        $obsolete->update([
            'status' => LmsCourse::STATUS_ARCHIVED,
            'updated_by' => $actor?->id ?? $class->updated_by ?? $class->created_by,
        ]);

        return compact('created', 'updated', 'archived', 'courses') + [
            'lessons_created' => $lessonsCreated,
            'lessons_updated' => $lessonsUpdated,
        ];
    }

    /** Đồng bộ lại các lớp học phần khi danh mục môn của một ngành thay đổi. */
    public function provisionForSpecialization(int $specializationId, ?User $actor = null): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'archived' => 0];
        ClassModel::query()
            ->where('specialization_id', $specializationId)
            ->orderBy('id')
            ->chunkById(50, function ($classes) use (&$summary, $actor): void {
                foreach ($classes as $class) {
                    $result = $this->provisionFromClassCurriculum($class, $actor);
                    foreach (array_keys($summary) as $key) {
                        $summary[$key] += $result[$key];
                    }
                }
            });

        return $summary;
    }

    /**
     * Tạo/cập nhật các lớp học phần LMS từ môn đã xuất hiện trong một lịch đào tạo.
     *
     * @return array{created:int,updated:int,courses:Collection<int,LmsCourse>,lessons_created:int,lessons_updated:int,attendance_created:int,attendance_updated:int}
     */
    public function provisionFromTrainingSchedule(
        TrainingSchedule $schedule,
        ?User $actor = null,
        bool $syncContent = true,
    ): array {
        $actor ??= Auth::user();
        $classId = $this->resolveClassId($schedule);
        if (! $classId) {
            throw new \RuntimeException('Lịch đào tạo chưa gắn lớp hợp lệ nên không thể tạo lớp học phần LMS.');
        }

        $subjectIds = ScheduleDetail::query()
            ->where('training_schedule_id', $schedule->id)
            ->whereNotNull('subject_id')
            ->distinct()
            ->orderBy('subject_id')
            ->pluck('subject_id');

        if ($actor && TrainingDept::isFacultyManager($actor)) {
            $allowed = TrainingDept::facultySubjectIds($actor) ?? [];
            $subjectIds = $subjectIds->filter(fn ($id) => in_array((int) $id, $allowed, true))->values();
        }

        $academicYearId = Schema::hasTable('academic_years')
            ? AcademicYear::query()->where('code', $schedule->academic_year)->value('id')
            : null;
        $class = ClassModel::query()->find($classId);
        $subjects = Subject::query()->whereIn('id', $subjectIds)->get()->keyBy('id');
        $created = 0;
        $updated = 0;
        $lessonsCreated = 0;
        $lessonsUpdated = 0;
        $attendanceCreated = 0;
        $attendanceUpdated = 0;
        $courses = collect();

        foreach ($subjectIds as $subjectId) {
            $subject = $subjects->get($subjectId);
            if (! $subject) {
                continue;
            }

            $provisionKey = "training-schedule:{$schedule->id}:subject:{$subjectId}";
            $existing = LmsCourse::withTrashed()
                ->where(function ($query) use ($provisionKey, $classId, $subjectId): void {
                    $query->where('provision_key', $provisionKey)
                        ->orWhere(function ($pair) use ($classId, $subjectId): void {
                            $pair->where('class_id', $classId)
                                ->where('subject_id', $subjectId)
                                ->where('is_standalone', false);
                        });
                })
                ->first();
            $isNew = ! $existing;

            $course = DB::transaction(function () use (
                $existing,
                $provisionKey,
                $schedule,
                $subject,
                $class,
                $classId,
                $academicYearId,
                $actor
            ): LmsCourse {
                $course = $existing ?: new LmsCourse;
                if ($course->trashed()) {
                    $course->restore();
                }

                $course->fill([
                    'code' => $course->code ?: $this->courseCode($schedule, $subject),
                    'title' => $course->title ?: trim($subject->name.' · '.($class?->name ?: $class?->code ?: 'Lớp')),
                    'subject_id' => $subject->id,
                    'class_id' => $classId,
                    'academic_year_id' => $academicYearId,
                    'term' => $schedule->semester,
                    'is_standalone' => false,
                    'starts_at' => $schedule->start_date,
                    'ends_at' => $schedule->end_date,
                    'status' => $course->exists ? $course->status : LmsSettings::courseStatus(),
                    'created_by' => $course->created_by ?: ($actor?->id ?? $schedule->created_by),
                    'updated_by' => $actor?->id ?? $schedule->updated_by ?? $schedule->created_by,
                ]);
                if (! $course->exists || ! $course->source_type) {
                    $course->source_type = 'training_schedule';
                    $course->source_id = $schedule->id;
                    $course->provision_key = $provisionKey;
                }
                $course->save();

                $this->syncInstructorsFromSchedule($course, $schedule, (int) $subject->id);
                $this->syncRoster($course);

                return $course->fresh();
            });

            if ($syncContent) {
                $content = $this->syncCurriculumLessons($course);
                $lessonsCreated += $content['created'];
                $lessonsUpdated += $content['updated'];
            }

            $attendance = $this->syncAttendanceSessionsFromSchedule($course, $schedule);
            $attendanceCreated += $attendance['created'];
            $attendanceUpdated += $attendance['updated'];

            $isNew ? $created++ : $updated++;
            $courses->push($course->fresh(['subject', 'classModel', 'instructors']));
        }

        return compact('created', 'updated', 'courses') + [
            'lessons_created' => $lessonsCreated,
            'lessons_updated' => $lessonsUpdated,
            'attendance_created' => $attendanceCreated,
            'attendance_updated' => $attendanceUpdated,
        ];
    }

    public function registerManualInstructor(LmsCourse $course, ?int $instructorId): void
    {
        if (! $instructorId) {
            return;
        }

        LmsCourseInstructor::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'instructor_id' => $instructorId],
            ['role' => LmsCourseInstructor::ROLE_LEAD, 'source' => 'manual']
        );
    }

    /** Gán các GV đã được Khoa phân công cho môn vào mọi lớp học phần tương ứng. */
    public function syncInstructorsFromTeachingAssignment(LmsCourse $course): void
    {
        $desired = DB::table('teaching_assignment')
            ->where('subject_id', $course->subject_id)
            ->orderBy('instructor_id')
            ->pluck('instructor_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->where('source', 'teaching_assignment')
            ->when($desired !== [], fn ($query) => $query->whereNotIn('instructor_id', $desired))
            ->when($desired === [], fn ($query) => $query->whereRaw('1 = 1'))
            ->delete();

        foreach ($desired as $index => $instructorId) {
            LmsCourseInstructor::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'instructor_id' => $instructorId],
                [
                    'role' => $index === 0
                        ? LmsCourseInstructor::ROLE_LEAD
                        : LmsCourseInstructor::ROLE_LECTURER,
                    'source' => 'teaching_assignment',
                ]
            );
        }

        $scheduleLead = LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->where('source', 'schedule')
            ->where('role', LmsCourseInstructor::ROLE_LEAD)
            ->value('instructor_id');
        $course->forceFill(['instructor_id' => $scheduleLead ?: ($desired[0] ?? null)])->save();
    }

    /** @param array<int|string> $subjectIds */
    public function syncTeachingAssignmentsForSubjects(array $subjectIds): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $subjectIds))));
        if ($ids === [] || ! Schema::hasTable('lms_course_instructors')) {
            return 0;
        }

        $count = 0;
        LmsCourse::query()->whereIn('subject_id', $ids)->orderBy('id')->chunkById(50, function ($courses) use (&$count): void {
            foreach ($courses as $course) {
                $this->syncInstructorsFromTeachingAssignment($course);
                $this->syncRoster($course);
                $count++;
            }
        });

        return $count;
    }

    public function syncInstructorsFromSchedule(
        LmsCourse $course,
        TrainingSchedule $schedule,
        int $subjectId,
    ): void {
        $rows = ScheduleDetail::query()
            ->where('training_schedule_id', $schedule->id)
            ->where('subject_id', $subjectId)
            ->whereNotNull('instructor_id')
            ->select('instructor_id', DB::raw('COUNT(*) as periods_count'))
            ->groupBy('instructor_id')
            ->orderByDesc('periods_count')
            ->orderBy('instructor_id')
            ->get();

        $desired = $rows->pluck('instructor_id')->map(fn ($id) => (int) $id)->all();
        $leadId = $desired[0] ?? null;

        LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->where('source', 'schedule')
            ->when($desired !== [], fn ($query) => $query->whereNotIn('instructor_id', $desired))
            ->when($desired === [], fn ($query) => $query->whereRaw('1 = 1'))
            ->delete();

        foreach ($desired as $instructorId) {
            LmsCourseInstructor::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'instructor_id' => $instructorId],
                [
                    'role' => $instructorId === $leadId
                        ? LmsCourseInstructor::ROLE_LEAD
                        : LmsCourseInstructor::ROLE_LECTURER,
                    'source' => 'schedule',
                ]
            );
        }

        $assignmentLead = LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->where('source', 'teaching_assignment')
            ->where('role', LmsCourseInstructor::ROLE_LEAD)
            ->value('instructor_id');
        $course->forceFill(['instructor_id' => $leadId ?: $assignmentLead])->save();
    }

    /** Đồng bộ chính xác roster tự động, giữ nguyên thành viên thêm thủ công. */
    public function syncRoster(LmsCourse $course): void
    {
        $now = now();
        $studentIds = $course->class_id
            ? User::query()
                ->where('class_id', $course->class_id)
                ->where(function ($query) {
                    $query->where('user_type', 'student')
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'student'));
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $instructorIds = LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->pluck('instructor_id');
        if ($course->instructor_id) {
            $instructorIds->push($course->instructor_id);
        }
        $lecturerUserIds = User::query()
            ->whereIn('instructor_id', $instructorIds->unique()->values())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($studentIds as $userId) {
            $this->upsertAutoMember($course, $userId, LmsCourseMember::ROLE_STUDENT, 'class', $now);
        }
        foreach ($lecturerUserIds as $userId) {
            $this->upsertAutoMember($course, $userId, LmsCourseMember::ROLE_LECTURER, 'schedule', $now);
        }

        $course->allMembers()
            ->where('source', 'class')
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->when($studentIds !== [], fn ($query) => $query->whereNotIn('user_id', $studentIds))
            ->when($studentIds === [], fn ($query) => $query->whereRaw('1 = 1'))
            ->update(['status' => LmsCourseMember::STATUS_INACTIVE, 'left_at' => $now, 'synced_at' => $now]);

        $course->allMembers()
            ->whereIn('source', ['schedule', 'course'])
            ->whereIn('role', [LmsCourseMember::ROLE_LECTURER, LmsCourseMember::ROLE_ASSISTANT])
            ->when($lecturerUserIds !== [], fn ($query) => $query->whereNotIn('user_id', $lecturerUserIds))
            ->when($lecturerUserIds === [], fn ($query) => $query->whereRaw('1 = 1'))
            ->update(['status' => LmsCourseMember::STATUS_INACTIVE, 'left_at' => $now, 'synced_at' => $now]);

        $course->forceFill(['roster_synced_at' => $now])->save();
    }

    /** @return array{created:int,updated:int,orphaned:int} */
    public function syncCurriculumLessons(LmsCourse $course): array
    {
        if (! $course->subject_id) {
            return ['created' => 0, 'updated' => 0, 'orphaned' => 0];
        }

        $curriculumSemester = $this->curriculumSemester($course->term);
        $query = SubjectLesson::query()
            ->where('subject_id', $course->subject_id)
            ->when($curriculumSemester !== null, function ($builder) use ($curriculumSemester) {
                $builder->where(function ($termQuery) use ($curriculumSemester) {
                    $termQuery->whereNull('semester')->orWhere('semester', $curriculumSemester);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        $all = $query->get();
        $leaf = $all->whereIn('lesson_kind', [
            SubjectLesson::KIND_LESSON,
            SubjectLesson::KIND_SUB,
            SubjectLesson::KIND_EXAM,
        ])->values();
        $sourceLessons = $leaf->isNotEmpty() ? $leaf : $all;
        $sourceIds = $sourceLessons->pluck('id')->map(fn ($id) => (int) $id)->all();
        $created = 0;
        $updated = 0;

        foreach ($sourceLessons as $index => $source) {
            $lesson = LmsLesson::withTrashed()
                ->where('lms_course_id', $course->id)
                ->where('subject_lesson_id', $source->id)
                ->first();
            $isNew = ! $lesson;
            $lesson ??= new LmsLesson;
            if ($lesson->trashed()) {
                $lesson->restore();
            }

            $previousSnapshot = (array) ($lesson->source_snapshot ?? []);
            $previousLabel = trim(implode(': ', array_filter([
                $previousSnapshot['code'] ?? null,
                $previousSnapshot['name'] ?? null,
            ])));
            $sourceLabel = $source->display_label;
            $syncTitle = $isNew || trim((string) $lesson->title) === '' || trim((string) $lesson->title) === $previousLabel;
            $syncSummary = $isNew || trim((string) $lesson->summary) === '' || trim((string) $lesson->summary) === trim((string) ($previousSnapshot['description'] ?? ''));

            $snapshot = [
                'code' => $source->code,
                'name' => $source->name,
                'lesson_kind' => $source->lesson_kind,
                'semester' => $source->semester,
                'updated_at' => $source->updated_at?->toIso8601String(),
                'description' => $source->description,
            ];

            $lesson->fill([
                'lms_course_id' => $course->id,
                'subject_lesson_id' => $source->id,
                'content_type' => 'curriculum',
                'title' => $syncTitle ? $sourceLabel : $lesson->title,
                'summary' => $syncSummary ? $source->description : $lesson->summary,
                'sort_order' => $index + 1,
                'source_snapshot' => $snapshot,
                'source_synced_at' => now(),
                'created_by' => $lesson->created_by ?: (Auth::id() ?? $course->created_by),
                'updated_by' => Auth::id() ?? $course->updated_by,
            ]);
            $lesson->save();
            $isNew ? $created++ : $updated++;
        }

        $orphanedQuery = LmsLesson::query()
            ->where('lms_course_id', $course->id)
            ->where('content_type', 'curriculum')
            ->whereNotNull('subject_lesson_id');
        if ($sourceIds !== []) {
            $orphanedQuery->whereNotIn('subject_lesson_id', $sourceIds);
        }
        $orphaned = $orphanedQuery->update(['content_type' => 'orphaned']);

        $course->forceFill(['content_synced_at' => now()])->save();

        return compact('created', 'updated', 'orphaned');
    }

    /** @return array{created:int,updated:int} */
    public function syncAttendanceSessionsFromSchedule(
        LmsCourse $course,
        ?TrainingSchedule $schedule = null,
    ): array {
        if (! $course->subject_id) {
            return ['created' => 0, 'updated' => 0];
        }

        $scheduleId = $schedule?->id
            ?: ($course->source_type === 'training_schedule' ? $course->source_id : null);
        if (! $scheduleId) {
            return ['created' => 0, 'updated' => 0];
        }

        $details = ScheduleDetail::query()
            ->with('subjectLesson:id,name')
            ->where('training_schedule_id', $scheduleId)
            ->where('subject_id', $course->subject_id)
            ->orderBy('date')
            ->orderBy('period')
            ->get();
        $created = 0;
        $updated = 0;

        foreach ($details as $detail) {
            $session = LmsAttendanceSession::query()
                ->where('schedule_detail_id', $detail->id)
                ->first();
            $isNew = ! $session;
            $session ??= new LmsAttendanceSession;
            $session->fill([
                'lms_course_id' => $course->id,
                'schedule_detail_id' => $detail->id,
                'title' => trim('Tiết '.$detail->period.' · '.($detail->subjectLesson?->name ?: $course->subject?->name ?: $course->title)),
                'session_date' => $detail->date,
                'mode' => $session->mode ?: 'manual',
                'status' => $session->status ?: 'closed',
                'created_by' => $session->created_by ?: (Auth::id() ?? $course->created_by),
            ]);
            $session->save();
            $isNew ? $created++ : $updated++;
        }

        return compact('created', 'updated');
    }

    protected function upsertAutoMember(
        LmsCourse $course,
        int $userId,
        string $role,
        string $source,
        \DateTimeInterface $now,
    ): void {
        $member = $course->allMembers()->where('user_id', $userId)->first();
        if ($member && $member->source === 'manual') {
            $member->update([
                'role' => $role,
                'status' => LmsCourseMember::STATUS_ACTIVE,
                'left_at' => null,
                'synced_at' => $now,
            ]);

            return;
        }

        LmsCourseMember::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'user_id' => $userId],
            [
                'role' => $role,
                'source' => $source,
                'status' => LmsCourseMember::STATUS_ACTIVE,
                'joined_at' => $member?->joined_at ?: $now,
                'synced_at' => $now,
                'left_at' => null,
            ]
        );
    }

    protected function resolveClassId(TrainingSchedule $schedule): ?int
    {
        if ($schedule->class_id) {
            return (int) $schedule->class_id;
        }

        if ($schedule->class_code) {
            return (int) (ClassModel::query()->where('code', $schedule->class_code)->value('id') ?: 0) ?: null;
        }

        return null;
    }

    protected function courseCode(TrainingSchedule $schedule, Subject $subject): string
    {
        $raw = implode('-', array_filter(['LMS', $schedule->code ?: $schedule->id, $subject->code ?: $subject->id]));
        $normalized = Str::upper((string) preg_replace('/[^\pL\pN._-]+/u', '-', $raw));

        return Str::limit(trim($normalized, '-'), 80, '');
    }

    protected function classCourseCode(ClassModel $class, Subject $subject): string
    {
        $raw = implode('-', array_filter(['LMS', $class->code ?: $class->id, $subject->code ?: $subject->id]));
        $normalized = Str::upper((string) preg_replace('/[^\pL\pN._-]+/u', '-', $raw));

        return Str::limit(trim($normalized, '-'), 80, '');
    }

    protected function curriculumSemester(?string $term): ?int
    {
        if (! $term || $term === 'summer') {
            return null;
        }
        if (preg_match('/(\d+)/', $term, $matches)) {
            return (int) $matches[1];
        }

        return is_numeric($term) ? (int) $term : null;
    }
}
