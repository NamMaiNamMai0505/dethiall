<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingScheduleScopeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Toàn bộ fixture trong file này dùng ngày cố định 2026-07-30..08-02.
        // Khoá thời gian "now" lại trong dải đó để không đụng ân hạn sửa lịch
        // của A5 (schedule-details khoá sau 48h kể từ hết ngày học).
        Carbon::setTestNow('2026-08-01 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_misconfigured_schedule_management_role_is_denied_instead_of_getting_global_scope(): void
    {
        // Mã đơn vị giống Khoa cũ vẫn chưa đủ: role mới bắt buộc functional_type + faculty_code.
        $unit = Unit::query()->where('code', 'K1')->first()
            ?? $this->unit('K1', Unit::FUNCTIONAL_OTHER);
        $unit->forceFill([
            'functional_type' => Unit::FUNCTIONAL_OTHER,
            'faculty_code' => null,
        ])->save();
        $user = $this->manager(ManagementRole::FACULTY_SCHEDULE_MANAGER, $unit);

        $this->actingAs($user)
            ->get(route('training-schedules.index'))
            ->assertForbidden();
    }

    public function test_faculty_cannot_create_schedule_skeleton_and_cannot_open_subject_of_another_faculty(): void
    {
        $faculty = $this->unit('FACULTY-ONE', Unit::FUNCTIONAL_FACULTY, 'K1');
        $user = $this->manager(ManagementRole::FACULTY_SCHEDULE_MANAGER, $faculty);
        [$schedule, , $otherSubject] = $this->scheduleFixture($user);

        $this->actingAs($user)
            ->get(route('training-schedules.schedule-details.create', [
                'trainingSchedule' => $schedule,
                'date' => '2026-07-31',
            ]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('subjects.show', $otherSubject))
            ->assertForbidden();
    }

    public function test_faculty_can_only_assign_its_instructor_on_an_existing_owned_skeleton(): void
    {
        $faculty = $this->unit('FACULTY-ONE', Unit::FUNCTIONAL_FACULTY, 'K1');
        $otherFaculty = $this->unit('FACULTY-TWO', Unit::FUNCTIONAL_FACULTY, 'K2');
        $user = $this->manager(ManagementRole::FACULTY_SCHEDULE_MANAGER, $faculty);
        [$schedule, $ownedSubject, $otherSubject] = $this->scheduleFixture($user);
        $ownedInstructor = $this->instructor('GV-K1', $faculty, $user);
        $otherInstructor = $this->instructor('GV-K2', $otherFaculty, $user);

        $ownedDetail = ScheduleDetail::query()->create([
            'training_schedule_id' => $schedule->id,
            'date' => '2026-07-31',
            'period' => 1,
            'subject_id' => $ownedSubject->id,
            'instructor_id' => null,
            'classroom_id' => null,
            'lesson_type' => 'theory',
        ]);
        $otherDetail = ScheduleDetail::query()->create([
            'training_schedule_id' => $schedule->id,
            'date' => '2026-07-31',
            'period' => 2,
            'subject_id' => $otherSubject->id,
            'instructor_id' => $otherInstructor->id,
            'classroom_id' => null,
            'lesson_type' => 'theory',
        ]);

        $this->actingAs($user)
            ->put(route('training-schedules.schedule-details.update', [$schedule, '2026-07-31']), [
                'details' => [
                    1 => $this->detailPayload(1, $ownedSubject, $ownedInstructor),
                    2 => $this->detailPayload(2, $otherSubject, $otherInstructor),
                ],
                'action' => 'save',
            ])
            ->assertRedirect(route('training-schedules.show', $schedule));

        $this->assertDatabaseHas('schedule_details', [
            'training_schedule_id' => $schedule->id,
            'date' => '2026-07-31',
            'period' => 1,
            'subject_id' => $ownedSubject->id,
            'instructor_id' => $ownedInstructor->id,
            'lesson_type' => 'theory',
        ]);
        $this->assertDatabaseHas('schedule_details', [
            'id' => $otherDetail->id,
            'subject_id' => $otherSubject->id,
            'instructor_id' => $otherInstructor->id,
        ]);

        $this->actingAs($user)
            ->put(route('training-schedules.schedule-details.update', [$schedule, '2026-07-31']), [
                'details' => [
                    1 => $this->detailPayload(1, $ownedSubject, $otherInstructor),
                ],
                'action' => 'save',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('schedule_details', [
            'training_schedule_id' => $schedule->id,
            'period' => 1,
            'instructor_id' => $ownedInstructor->id,
        ]);

        $this->actingAs($user)
            ->delete(route('training-schedules.schedule-details.destroy', [$schedule, '2026-07-31']))
            ->assertRedirect();

        $this->assertDatabaseHas('schedule_details', [
            'training_schedule_id' => $schedule->id,
            'period' => 1,
            'subject_id' => $ownedSubject->id,
            'instructor_id' => null,
        ]);
        $this->assertDatabaseHas('schedule_details', [
            'id' => $otherDetail->id,
            'subject_id' => $otherSubject->id,
            'instructor_id' => $otherInstructor->id,
        ]);

        $this->assertNotNull($ownedDetail->id);
    }

    public function test_training_office_stores_only_the_skeleton_even_if_assignment_fields_are_tampered(): void
    {
        $trainingOffice = $this->unit('PDT-SCOPE', Unit::FUNCTIONAL_TRAINING_OFFICE);
        $faculty = $this->unit('FACULTY-ONE', Unit::FUNCTIONAL_FACULTY, 'K1');
        $user = $this->manager(ManagementRole::TRAINING_OFFICE_MANAGER, $trainingOffice);
        [$schedule, $subject] = $this->scheduleFixture($user);
        $instructor = $this->instructor('GV-TAMPER', $faculty, $user);

        $this->actingAs($user)
            ->post(route('training-schedules.schedule-details.store', $schedule), [
                'details' => [
                    1 => $this->detailPayload(1, $subject, $instructor),
                ],
                'action' => 'save',
            ])
            ->assertRedirect(route('training-schedules.show', $schedule));

        $this->assertDatabaseHas('schedule_details', [
            'training_schedule_id' => $schedule->id,
            'date' => '2026-07-31',
            'period' => 1,
            'subject_id' => $subject->id,
            'instructor_id' => null,
            'lesson_type' => 'theory',
        ]);

        $this->actingAs($user)
            ->getJson(route('training-schedules.api.list'))
            ->assertOk()
            ->assertJsonPath('results.0.id', $schedule->id);
        $this->getJson(route('training-schedules.api.show', $schedule))
            ->assertOk()
            ->assertJsonPath('data.id', $schedule->id);
        $this->getJson(route('training-schedules.api.calendar-events'))
            ->assertOk()
            ->assertJsonPath('0.id', $schedule->id);
        $this->getJson(route('training-schedules.api.stats'))
            ->assertOk()
            ->assertJsonStructure(['total', 'active', 'ongoing', 'scheduled', 'this_month']);
    }

    private function unit(string $code, string $functionalType, ?string $facultyCode = null): Unit
    {
        return Unit::query()->create([
            'code' => $code,
            'name' => 'Đơn vị '.$code,
            'functional_type' => $functionalType,
            'faculty_code' => $facultyCode,
            'status' => Unit::STATUS_ACTIVE,
        ]);
    }

    private function manager(string $roleName, Unit $unit): User
    {
        $role = Role::findByName($roleName, 'web');
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role_id' => $role->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$roleName]);

        return $user->fresh(['roles', 'unit']);
    }

    /** @return array{TrainingSchedule, Subject, Subject} */
    private function scheduleFixture(User $actor): array
    {
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Sprint 22',
            'code' => 'SPRINT22-'.$actor->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $ownedSubject = $this->subject('M001K1-'.$actor->id, $specialization, $actor);
        $otherSubject = $this->subject('M002K2-'.$actor->id, $specialization, $actor);

        // Hậu tố K1/K2 là khóa phạm vi; giữ id ở phần giữa để mã vẫn duy nhất.
        $ownedSubject->update(['code' => 'B_S22_'.$actor->id.'_M001K1']);
        $otherSubject->update(['code' => 'B_S22_'.$actor->id.'_M002K2']);

        $schedule = TrainingSchedule::query()->create([
            'name' => 'Lịch Sprint 22',
            'code' => 'S22-'.$actor->id,
            'specialization_id' => $specialization->id,
            'academic_year' => '2026-2027',
            'semester' => 'semester_1',
            'start_date' => '2026-07-30',
            'end_date' => '2026-08-02',
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return [$schedule, $ownedSubject->fresh(), $otherSubject->fresh()];
    }

    private function subject(string $code, Specialization $specialization, User $actor): Subject
    {
        return Subject::query()->create([
            'name' => 'Môn '.$code,
            'code' => $code,
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
    }

    private function instructor(string $code, Unit $unit, User $actor): Instructor
    {
        return Instructor::query()->create([
            'name' => 'Giảng viên '.$code,
            'code' => $code.'-'.$actor->id,
            'email' => strtolower($code).'-'.$actor->id.'@example.test',
            'unit_id' => $unit->id,
            'status' => Instructor::STATUS_ACTIVE,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /** @return array<string, int|string|null> */
    private function detailPayload(int $period, Subject $subject, ?Instructor $instructor = null): array
    {
        return [
            'date' => '2026-07-31',
            'period' => $period,
            'subject_id' => $subject->id,
            'subject_lesson_id' => null,
            'instructor_id' => $instructor?->id,
            'classroom_id' => null,
            'lesson_type' => 'theory',
            'standard_hours_conversion_category_id' => null,
        ];
    }
}
