<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsLearningAlert;
use Modules\Lms\Services\LmsAlertService;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Tests\TestCase;

/**
 * Sprint 44 / C5 — ngưỡng vắng riêng từng môn, vắng có phép không tính vào
 * ngưỡng, vượt ngưỡng chỉ cảnh báo (không tự đánh trượt).
 */
class LmsAbsenceThresholdAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(?int $absenceLimitPercent): array
    {
        $admin = User::factory()->create();
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Absence Test', 'code' => 'ABS-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Absence Test', 'code' => 'ABS-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'absence_limit_percent' => $absenceLimitPercent,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        $class = ClassModel::query()->create([
            'name' => 'Lớp Absence Test', 'code' => 'ABS-CLS-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $instructor->id,
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $course = LmsCourse::query()->create([
            'title' => 'Khóa Absence Test',
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $student = User::factory()->create(['name' => 'Học viên Absence Test']);
        LmsCourseMember::query()->create([
            'lms_course_id' => $course->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT,
        ]);

        return [$course, $student];
    }

    /** @param list<string> $statuses present|absent|late|excused theo từng buổi, theo thứ tự */
    private function markSessions(LmsCourse $course, User $student, array $statuses): void
    {
        foreach ($statuses as $i => $status) {
            $session = LmsAttendanceSession::query()->create([
                'lms_course_id' => $course->id,
                'title' => 'Buổi '.($i + 1),
                'session_date' => now()->subDays(count($statuses) - $i),
                'mode' => 'manual',
                'status' => 'closed',
                'open_from' => now(),
                'created_by' => $student->id,
            ]);
            LmsAttendanceRecord::query()->create([
                'lms_attendance_session_id' => $session->id,
                'user_id' => $student->id,
                'status' => $status,
                'method' => 'manual',
            ]);
        }
    }

    public function test_exceeding_the_subjects_own_threshold_creates_a_critical_alert_with_the_right_numbers(): void
    {
        [$course, $student] = $this->makeCourse(absenceLimitPercent: 20);
        // 10 buổi, vắng không phép 3 buổi = 30% > ngưỡng 20% của môn.
        $this->markSessions($course, $student, [
            'present', 'present', 'present', 'present', 'present',
            'present', 'absent', 'absent', 'absent', 'present',
        ]);

        app(LmsAlertService::class)->evaluateCourse($course->fresh());

        $alert = LmsLearningAlert::query()
            ->where('lms_course_id', $course->id)->where('user_id', $student->id)
            ->where('type', 'low_attendance')->first();

        $this->assertNotNull($alert, 'Phải sinh cảnh báo khi vượt ngưỡng 20% của môn.');
        $this->assertSame('critical', $alert->severity);
        $this->assertSame('Đã vắng 3/10 buổi (30%) — vượt ngưỡng 20% của môn.', $alert->body);
        $this->assertSame(3, $alert->meta['absent_count']);
        $this->assertSame(10, $alert->meta['total_sessions']);
        $this->assertEquals(30.0, $alert->meta['absence_pct']);
        $this->assertSame(20, $alert->meta['absence_limit_percent']);
    }

    public function test_excused_absences_do_not_count_toward_the_threshold(): void
    {
        [$course, $student] = $this->makeCourse(absenceLimitPercent: 20);
        // 10 buổi: 3 "excused" (vắng có phép) + 7 present → 0% vắng không phép.
        // Nếu tính cả excused vào vắng thì sẽ là 30% > 20% và sai sinh cảnh báo.
        $this->markSessions($course, $student, [
            'present', 'present', 'present', 'present', 'present',
            'present', 'present', 'excused', 'excused', 'excused',
        ]);

        app(LmsAlertService::class)->evaluateCourse($course->fresh());

        $this->assertDatabaseMissing('lms_learning_alerts', [
            'lms_course_id' => $course->id,
            'user_id' => $student->id,
            'type' => 'low_attendance',
        ]);
    }

    public function test_subject_without_its_own_threshold_falls_back_to_the_system_default(): void
    {
        [$course, $student] = $this->makeCourse(absenceLimitPercent: null);
        \App\Support\SystemSettings::put('lms', 'default_absence_limit_percent', '10');

        // 10 buổi, vắng 2 buổi = 20% > mặc định hệ thống 10%.
        $this->markSessions($course, $student, [
            'present', 'present', 'present', 'present', 'present',
            'present', 'present', 'present', 'absent', 'absent',
        ]);

        app(LmsAlertService::class)->evaluateCourse($course->fresh());

        $alert = LmsLearningAlert::query()
            ->where('lms_course_id', $course->id)->where('user_id', $student->id)
            ->where('type', 'low_attendance')->first();

        $this->assertNotNull($alert);
        $this->assertSame(10, $alert->meta['absence_limit_percent']);
    }

    public function test_staying_within_the_threshold_does_not_create_an_alert(): void
    {
        [$course, $student] = $this->makeCourse(absenceLimitPercent: 20);
        // 10 buổi, vắng 1 buổi = 10% < ngưỡng 20%.
        $this->markSessions($course, $student, [
            'present', 'present', 'present', 'present', 'present',
            'present', 'present', 'present', 'present', 'absent',
        ]);

        app(LmsAlertService::class)->evaluateCourse($course->fresh());

        $this->assertDatabaseMissing('lms_learning_alerts', [
            'lms_course_id' => $course->id,
            'user_id' => $student->id,
            'type' => 'low_attendance',
        ]);
    }
}
