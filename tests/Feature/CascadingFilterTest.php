<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Lọc liên động: chọn điều kiện trước thì điều kiện sau chỉ còn lựa chọn hợp lệ.
 * Danh sách option được server dựng sẵn nên không phụ thuộc JavaScript.
 */
class CascadingFilterTest extends TestCase
{
    use RefreshDatabase;

    private TrainingSystem $civil;

    private TrainingSystem $military;

    private Specialization $nursing;

    private Specialization $pharmacy;

    private Specialization $militaryMedic;

    private Subject $nursingSubject;

    private Subject $pharmacySubject;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->civil = TrainingSystem::query()->firstOrCreate(
            ['code' => 'TEST-DS'],
            ['name' => 'Hệ Dân sự (test)', 'sort_order' => 91, 'is_active' => true]
        );
        $this->military = TrainingSystem::query()->firstOrCreate(
            ['code' => 'TEST-QS'],
            ['name' => 'Hệ Quân sự (test)', 'sort_order' => 92, 'is_active' => true]
        );

        $this->nursing = $this->makeSpecialization('TEST.B.6720301', '6720301', 'Điều dưỡng', $this->civil);
        $this->pharmacy = $this->makeSpecialization('TEST.B.6720201', '6720201', 'Dược', $this->civil);
        $this->militaryMedic = $this->makeSpecialization('TEST.A.6720101', '6720101', 'Y sỹ đa khoa', $this->military);

        $this->nursingSubject = $this->makeSubject('TEST-DD-K1', 'Điều dưỡng cơ bản', $this->nursing);
        $this->pharmacySubject = $this->makeSubject('TEST-DUOC-K1', 'Hóa dược', $this->pharmacy);
    }

    private function makeSpecialization(string $code, string $majorCode, string $name, TrainingSystem $system): Specialization
    {
        return Specialization::query()->create([
            'code' => $code,
            'major_code' => $majorCode,
            'name' => $name,
            'training_system_id' => $system->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'training_form' => Specialization::TRAINING_FORM_FORMAL,
            'duration_months' => 36,
            'is_active' => true,
        ]);
    }

    private function makeSubject(string $code, string $name, Specialization $specialization): Subject
    {
        return Subject::query()->create([
            'code' => $code,
            'name' => $name,
            'specialization_id' => $specialization->id,
            'credits' => 3,
            'theory_hours' => 30,
            'practice_hours' => 15,
            'is_active' => true,
        ]);
    }

    private function curriculumManager(): User
    {
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);

        foreach ([
            'subject-lessons.index', 'subject-lessons.show',
            'subjects.index', 'subjects.show',
            'specializations.index', 'specializations.show',
        ] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_lesson_specialization_options_follow_the_selected_training_system(): void
    {
        $response = $this->actingAs($this->curriculumManager())
            ->get(route('subject-lessons.index', ['training_system_id' => $this->civil->id]))
            ->assertOk();

        $specializations = $response->viewData('specializations');

        $this->assertTrue($specializations->contains('id', $this->nursing->id));
        $this->assertTrue($specializations->contains('id', $this->pharmacy->id));
        $this->assertFalse(
            $specializations->contains('id', $this->militaryMedic->id),
            'Chọn Hệ Dân sự thì ngành của Hệ Quân sự không được xuất hiện.'
        );
    }

    public function test_lesson_subject_options_follow_the_selected_specialization(): void
    {
        $response = $this->actingAs($this->curriculumManager())
            ->get(route('subject-lessons.index', [
                'training_system_id' => $this->civil->id,
                'specialization_id' => $this->nursing->id,
            ]))
            ->assertOk();

        $subjects = $response->viewData('subjects');

        $this->assertTrue($subjects->contains('id', $this->nursingSubject->id));
        $this->assertFalse(
            $subjects->contains('id', $this->pharmacySubject->id),
            'Chọn ngành Điều dưỡng thì môn của ngành Dược không được xuất hiện.'
        );
    }

    public function test_lesson_subject_options_stay_within_the_training_system_without_a_specialization(): void
    {
        $response = $this->actingAs($this->curriculumManager())
            ->get(route('subject-lessons.index', ['training_system_id' => $this->military->id]))
            ->assertOk();

        $subjects = $response->viewData('subjects');

        $this->assertFalse($subjects->contains('id', $this->nursingSubject->id));
        $this->assertFalse($subjects->contains('id', $this->pharmacySubject->id));
    }

    public function test_lessons_are_filtered_down_to_the_selected_subject(): void
    {
        $wanted = SubjectLesson::query()->create([
            'subject_id' => $this->nursingSubject->id,
            'code' => 'B1',
            'name' => 'Bài 1 điều dưỡng',
            'lesson_kind' => 'lesson',
            'sort_order' => 1,
        ]);
        $other = SubjectLesson::query()->create([
            'subject_id' => $this->pharmacySubject->id,
            'code' => 'B1',
            'name' => 'Bài 1 hóa dược',
            'lesson_kind' => 'lesson',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->curriculumManager())
            ->get(route('subject-lessons.index', ['subject_id' => $this->nursingSubject->id]))
            ->assertOk();

        $lessons = $response->viewData('lessons');

        $this->assertTrue($lessons->contains('id', $wanted->id));
        $this->assertFalse($lessons->contains('id', $other->id));
        $this->assertSame($this->nursingSubject->id, $response->viewData('selectedSubject')?->id);
    }

    public function test_subject_screen_specialization_options_follow_the_training_system(): void
    {
        $response = $this->actingAs($this->curriculumManager())
            ->get(route('subjects.index', ['training_system_id' => $this->military->id]))
            ->assertOk();

        $specializations = $response->viewData('specializations');

        $this->assertTrue($specializations->has($this->militaryMedic->id));
        $this->assertFalse(
            $specializations->has($this->nursing->id),
            'Danh sách ngành phải bám theo hệ đào tạo đã chọn.'
        );
    }
}
