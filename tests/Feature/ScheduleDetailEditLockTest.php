<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use App\Support\TrainingScheduleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / A5 — buổi học đã kết thúc quá thời gian ân hạn thì khoá sửa lịch.
 * Khoá theo NGÀY (không theo tiết); chỉ Super Admin còn sửa được.
 *
 * Sprint 44 / A3 — nút "Cập nhật & Ngày tiếp theo" không còn đẩy Quản lý khoa
 * vào route `schedule-details.create` mà họ không có quyền.
 */
class ScheduleDetailEditLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function trainingOfficeUser(): User
    {
        $unit = Unit::query()->create([
            'code' => 'PDT-LOCK',
            'name' => 'Phòng Đào tạo Lock',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $role = Role::findByName(ManagementRole::TRAINING_OFFICE_MANAGER, 'web');
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role_id' => $role->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles', 'unit']);
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles']);
    }

    /** @return array{TrainingSchedule, Subject} */
    private function scheduleFixture(User $actor, string $startDate, string $endDate): array
    {
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Lock Test',
            'code' => 'LOCK-'.$actor->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Lock Test',
            'code' => 'LOCK-SUBJ-'.$actor->id,
            'specialization_id' => $specialization->id,
            'credits' => 2,
            'theory_hours' => 20,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'exam_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => true,
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $schedule = TrainingSchedule::query()->create([
            'name' => 'Lịch Lock Test',
            'code' => 'LOCK-SCH-'.$actor->id,
            'specialization_id' => $specialization->id,
            'academic_year' => '2026-2027',
            'semester' => 'semester_1',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return [$schedule, $subject->fresh()];
    }

    // --- A5: khoá theo ngày + ân hạn -----------------------------------

    public function test_date_within_grace_period_is_not_locked(): void
    {
        Carbon::setTestNow('2026-08-06 08:00:00');

        // Ngày học 2026-08-05 kết thúc lúc 23:59:59; ân hạn mặc định 48h
        // đưa hạn chót sang 2026-08-07 23:59:59 — còn trong hạn.
        $this->assertFalse(TrainingScheduleAccess::isScheduleDetailDateLocked('2026-08-05'));
    }

    public function test_date_past_grace_period_is_locked(): void
    {
        Carbon::setTestNow('2026-08-10 08:00:00');

        $this->assertTrue(TrainingScheduleAccess::isScheduleDetailDateLocked('2026-08-05'));
    }

    public function test_grace_period_is_configurable(): void
    {
        Carbon::setTestNow('2026-08-07 12:00:00');
        config(['training_schedule.edit_grace_hours' => 12]);

        $this->assertTrue(
            TrainingScheduleAccess::isScheduleDetailDateLocked('2026-08-05'),
            'Ân hạn 12h thì sang 2026-08-07 phải đã khoá.'
        );
    }

    public function test_training_office_cannot_update_a_locked_past_date(): void
    {
        Carbon::setTestNow('2026-08-10 08:00:00');
        $user = $this->trainingOfficeUser();
        [$schedule, $subject] = $this->scheduleFixture($user, '2026-08-01', '2026-08-09');

        $this->actingAs($user)
            ->put(route('training-schedules.schedule-details.update', [$schedule, '2026-08-05']), [
                'details' => [
                    1 => [
                        'period' => 1,
                        'date' => '2026-08-05',
                        'subject_id' => $subject->id,
                        'lesson_type' => 'theory',
                    ],
                ],
                'action' => 'save',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_still_update_a_locked_past_date(): void
    {
        Carbon::setTestNow('2026-08-10 08:00:00');
        $admin = $this->superAdmin();
        [$schedule, $subject] = $this->scheduleFixture($admin, '2026-08-01', '2026-08-09');

        $this->actingAs($admin)
            ->put(route('training-schedules.schedule-details.update', [$schedule, '2026-08-05']), [
                'details' => [
                    1 => [
                        'period' => 1,
                        'date' => '2026-08-05',
                        'subject_id' => $subject->id,
                        'lesson_type' => 'theory',
                    ],
                ],
                'action' => 'save',
            ])
            ->assertRedirect(route('training-schedules.show', $schedule));
    }

    public function test_edit_form_hides_the_save_buttons_for_a_locked_date(): void
    {
        Carbon::setTestNow('2026-08-10 08:00:00');
        $user = $this->trainingOfficeUser();
        [$schedule] = $this->scheduleFixture($user, '2026-08-01', '2026-08-09');

        $this->actingAs($user)
            ->get(route('training-schedules.schedule-details.edit', [$schedule, '2026-08-05']))
            ->assertOk()
            ->assertSee('Chỉ Super Admin sửa được lịch của ngày đã học xong')
            ->assertDontSee('name="action" value="save"', false);
    }

    public function test_edit_form_still_shows_save_buttons_for_a_recent_date(): void
    {
        Carbon::setTestNow('2026-08-06 08:00:00');
        $user = $this->trainingOfficeUser();
        [$schedule] = $this->scheduleFixture($user, '2026-08-01', '2026-08-09');

        $this->actingAs($user)
            ->get(route('training-schedules.schedule-details.edit', [$schedule, '2026-08-05']))
            ->assertOk()
            ->assertSee('name="action" value="save"', false)
            ->assertDontSee('Chỉ Super Admin sửa được lịch của ngày đã học xong');
    }

    // --- A3: không văng người thiếu quyền vào route create --------------

    public function test_save_and_next_does_not_redirect_a_faculty_scoped_user_into_the_create_route(): void
    {
        Carbon::setTestNow('2026-08-01 08:00:00');
        $user = $this->trainingOfficeUser();
        // Phòng Đào tạo có quyền create nên không phải đối tượng của A3; thay
        // bằng vai trò Quản lý khoa (không có schedule-details.create) để bài
        // test đúng kịch bản đã báo lỗi.
        $facultyUnit = Unit::query()->create([
            'code' => 'FAC-LOCK',
            'name' => 'Khoa Lock Test',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K5',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $facultyRole = Role::findByName(RoleCatalog::FACULTY_MANAGER, 'web');
        $faculty = User::factory()->create([
            'unit_id' => $facultyUnit->id,
            'role_id' => $facultyRole->id,
            'user_type' => 'internal_user',
        ]);
        $faculty->syncRoles([$facultyRole->name]);
        $this->assertFalse(
            $faculty->can('schedule-details.create'),
            'Test giả định Quản lý khoa không có quyền tạo khung — nếu ma trận đổi thì cập nhật lại test này.'
        );

        [$schedule, $subject] = $this->scheduleFixture($user, '2026-08-01', '2026-08-09');
        // subjectBelongsToFaculty() so khớp theo hậu tố mã môn = mã khoa (K5).
        $subject->update(['code' => $subject->code.'K5']);
        // PĐT xếp khung cho ngày cuối cùng của lịch để Khoa có gì mà sửa.
        ScheduleDetail::query()->create([
            'training_schedule_id' => $schedule->id,
            'date' => '2026-08-09',
            'period' => 1,
            'subject_id' => $subject->id,
            'lesson_type' => 'theory',
        ]);

        $response = $this->actingAs($faculty)
            ->put(route('training-schedules.schedule-details.update', [$schedule, '2026-08-09']), [
                'details' => [
                    1 => [
                        'period' => 1,
                        'date' => '2026-08-09',
                        'subject_id' => $subject->id,
                        'lesson_type' => 'theory',
                    ],
                ],
                'action' => 'save_and_next',
            ]);

        // Trước A3: redirect thẳng vào schedule-details.create → theo sau
        // (follow) sẽ ra 403 vì Khoa thiếu quyền create. Giờ phải dừng lại ở
        // trang danh sách lịch, có cảnh báo, không đụng route create.
        $response->assertRedirect(route('training-schedules.show', $schedule));
        $this->assertStringNotContainsString(
            'schedule-details/create',
            $response->headers->get('Location') ?? ''
        );
    }
}
