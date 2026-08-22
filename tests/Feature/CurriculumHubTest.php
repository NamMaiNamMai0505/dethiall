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
 * Hub Ngành đào tạo gom Hệ → Ngành → Môn → Bài, và danh mục ngành đặt Mã số
 * (khóa chính) ở cột ngoài cùng bên trái theo BUSINESS_RULES.
 */
class CurriculumHubTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $specialization;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $system = TrainingSystem::query()->firstOrCreate(
            ['code' => 'TEST-HUB'],
            ['name' => 'Hệ kiểm thử hub', 'sort_order' => 95, 'is_active' => true]
        );

        $this->specialization = Specialization::query()->create([
            'code' => 'TEST.HUB.6720301',
            'major_code' => '6720301',
            'name' => 'Điều dưỡng hub',
            'training_system_id' => $system->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'training_form' => Specialization::TRAINING_FORM_FORMAL,
            'duration_months' => 36,
            'is_active' => true,
        ]);

        $this->subject = Subject::query()->create([
            'code' => 'TEST-HUB-DD',
            'name' => 'Điều dưỡng cơ bản hub',
            'specialization_id' => $this->specialization->id,
            'credits' => 3,
            'theory_hours' => 30,
            'practice_hours' => 15,
            'is_active' => true,
        ]);
    }

    /** @param  list<string>  $extra */
    private function curriculumUser(array $extra = []): User
    {
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);

        foreach (array_merge([
            'specializations.index', 'specializations.show',
            'subjects.index', 'subjects.show',
            'subject-lessons.index', 'subject-lessons.show',
        ], $extra) as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_hub_shows_all_four_curriculum_levels(): void
    {
        $this->actingAs($this->curriculumUser())
            ->get(route('specializations.hub'))
            ->assertOk()
            ->assertSeeText('Hệ đào tạo')
            ->assertSeeText('Ngành đào tạo')
            ->assertSeeText('Môn học')
            ->assertSeeText('Bài học');
    }

    public function test_hub_requires_the_specialization_permission(): void
    {
        $outsider = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);

        $this->actingAs($outsider)
            ->get(route('specializations.hub'))
            ->assertForbidden();
    }

    public function test_hub_only_lists_menu_entries_the_account_may_open(): void
    {
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->givePermissionTo(Permission::findOrCreate('specializations.index', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $menu = $this->actingAs($user)
            ->get(route('specializations.hub'))
            ->assertOk()
            ->viewData('menuItems');

        $routes = array_column($menu, 'route');

        $this->assertContains('specializations.index', $routes);
        $this->assertNotContains('subjects.index', $routes);
        $this->assertNotContains('subject-lessons.index', $routes);
    }

    public function test_specialization_list_puts_the_primary_code_before_the_major_code(): void
    {
        $html = $this->actingAs($this->curriculumUser())
            ->get(route('specializations.index', ['search' => 'Điều dưỡng hub']))
            ->assertOk()
            ->assertDontSee('Mã số nội bộ')
            ->getContent();

        $codeHeader = strpos($html, 'Mã số');
        $majorHeader = strpos($html, 'Mã ngành');

        $this->assertNotFalse($codeHeader);
        $this->assertNotFalse($majorHeader);
        $this->assertLessThan(
            $majorHeader,
            $codeHeader,
            'Mã số là khóa chính nên phải đứng trước Mã ngành.'
        );
    }

    public function test_lesson_screen_hides_the_subject_column_once_a_subject_is_chosen(): void
    {
        SubjectLesson::query()->create([
            'subject_id' => $this->subject->id,
            'code' => 'HUB-B1',
            'name' => 'Bài hub 1',
            'lesson_kind' => 'lesson',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->curriculumUser())
            ->get(route('subject-lessons.index', ['subject_id' => $this->subject->id]))
            ->assertOk()
            ->assertSeeText('Môn đang chọn')
            ->assertSeeText($this->subject->name)
            ->assertSeeText('Bài hub 1');
    }

    public function test_quick_add_row_creates_a_lesson_for_the_selected_subject(): void
    {
        $user = $this->curriculumUser(['subject-lessons.create']);

        $this->actingAs($user)
            ->post(route('subject-lessons.store'), [
                'specialization_id' => $this->specialization->id,
                'subject_id' => $this->subject->id,
                'code' => 'HUB-NEW',
                'name' => 'Bài thêm nhanh',
                'lesson_kind' => 'lesson',
                'theory_hours' => 2,
                'practice_hours' => 1,
                'exam_hours' => 0,
                'sort_order' => 5,
            ])
            ->assertRedirect();

        $lesson = SubjectLesson::query()
            ->where('subject_id', $this->subject->id)
            ->where('code', 'HUB-NEW')
            ->first();

        $this->assertNotNull($lesson);
        $this->assertSame('Bài thêm nhanh', $lesson->name);
        $this->assertSame(3, (int) $lesson->total_hours, 'Tổng giờ phải tự cộng từ LT + TH + Thi.');
    }

    public function test_quick_add_keeps_the_whole_filter_chain_after_saving(): void
    {
        // Thêm nhiều bài liên tiếp: sau mỗi lần lưu phải quay lại đúng
        // Hệ → Ngành → Môn đang chọn, không văng về "tất cả".
        $this->actingAs($this->curriculumUser(['subject-lessons.create']))
            ->post(route('subject-lessons.store'), [
                'training_system_id' => $this->specialization->training_system_id,
                'specialization_id' => $this->specialization->id,
                'subject_id' => $this->subject->id,
                'code' => 'HUB-CTX',
                'name' => 'Bài giữ ngữ cảnh lọc',
                'lesson_kind' => 'lesson',
            ])
            ->assertRedirect(route('subject-lessons.index', [
                'training_system_id' => $this->specialization->training_system_id,
                'specialization_id' => $this->specialization->id,
                'subject_id' => $this->subject->id,
            ]));
    }

    public function test_quick_add_infers_the_filter_chain_from_the_subject_when_missing(): void
    {
        $this->actingAs($this->curriculumUser(['subject-lessons.create']))
            ->post(route('subject-lessons.store'), [
                'specialization_id' => $this->specialization->id,
                'subject_id' => $this->subject->id,
                'code' => 'HUB-INFER',
                'name' => 'Bài suy ra ngữ cảnh',
                'lesson_kind' => 'lesson',
            ])
            ->assertRedirect(route('subject-lessons.index', [
                'training_system_id' => $this->specialization->training_system_id,
                'specialization_id' => $this->specialization->id,
                'subject_id' => $this->subject->id,
            ]));
    }

    public function test_quick_add_rejects_a_subject_outside_the_chosen_specialization(): void
    {
        $otherSystem = TrainingSystem::query()->firstOrCreate(
            ['code' => 'TEST-HUB2'],
            ['name' => 'Hệ kiểm thử hub 2', 'sort_order' => 96, 'is_active' => true]
        );
        $otherSpecialization = Specialization::query()->create([
            'code' => 'TEST.HUB.6720201',
            'major_code' => '6720201',
            'name' => 'Dược hub',
            'training_system_id' => $otherSystem->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'training_form' => Specialization::TRAINING_FORM_FORMAL,
            'duration_months' => 36,
            'is_active' => true,
        ]);

        $this->actingAs($this->curriculumUser(['subject-lessons.create']))
            ->post(route('subject-lessons.store'), [
                'specialization_id' => $otherSpecialization->id,
                'subject_id' => $this->subject->id,
                'code' => 'HUB-BAD',
                'name' => 'Bài sai ngành',
                'lesson_kind' => 'lesson',
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('subject_lessons', ['code' => 'HUB-BAD']);
    }
}
