<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Liên kết Ngành (cha) -> Môn (con) -> Bài (con) và Khoa -> Môn:
 * - Xóa Ngành phải xóa theo toàn bộ Môn + Bài thuộc ngành (cascade soft-delete).
 * - Xóa Môn phải xóa theo toàn bộ Bài thuộc môn.
 * - Xóa Khoa KHÔNG được xóa Môn - chỉ gỡ gán (faculty_code = null), Môn vẫn còn.
 * - Bulk-delete (chọn nhiều) áp dụng cùng quy tắc cascade như xóa từng cái.
 */
class CurriculumCascadeDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles']);
    }

    private function specialization(): Specialization
    {
        return Specialization::query()->create([
            'name' => 'Ngành Cascade Test',
            'code' => 'CASCADE-'.uniqid(),
            'level' => Specialization::LEVEL_BEGINNER,
            'duration_months' => 12,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
        ]);
    }

    private function subject(int $specializationId, array $overrides = []): Subject
    {
        return Subject::query()->create(array_merge([
            'name' => 'Môn Cascade Test',
            'code' => 'CASCADE-SUBJ-'.uniqid(),
            'specialization_id' => $specializationId,
            'credits' => 1,
            'theory_hours' => 1,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => true,
            'is_active' => true,
        ], $overrides));
    }

    private function lesson(int $subjectId, array $overrides = []): SubjectLesson
    {
        return SubjectLesson::query()->create(array_merge([
            'subject_id' => $subjectId,
            'code' => 'L-'.uniqid(),
            'name' => 'Bài Cascade Test',
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_deleting_a_subject_cascades_to_its_lessons(): void
    {
        $admin = $this->superAdmin();
        $spec = $this->specialization();
        $subject = $this->subject($spec->id);
        $lesson = $this->lesson($subject->id);

        $this->actingAs($admin)
            ->delete(route('subjects.destroy', $subject))
            ->assertRedirect();

        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
        $this->assertSoftDeleted('subject_lessons', ['id' => $lesson->id]);
    }

    public function test_deleting_a_specialization_cascades_to_subjects_and_lessons(): void
    {
        $admin = $this->superAdmin();
        $spec = $this->specialization();
        $subject = $this->subject($spec->id);
        $lesson = $this->lesson($subject->id);

        $this->actingAs($admin)
            ->delete(route('specializations.destroy', $spec))
            ->assertRedirect();

        $this->assertSoftDeleted('specializations', ['id' => $spec->id]);
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
        $this->assertSoftDeleted('subject_lessons', ['id' => $lesson->id]);
    }

    public function test_deleting_a_unit_only_unassigns_subjects_without_deleting_them(): void
    {
        $admin = $this->superAdmin();
        $unit = Unit::query()->create([
            'code' => 'UNIT-CASCADE-'.uniqid(),
            'name' => 'Khoa Cascade Test',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'CASCADEK1',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $spec = $this->specialization();
        $subject = $this->subject($spec->id, ['faculty_code' => 'CASCADEK1']);

        $this->actingAs($admin)
            ->delete(route('units.destroy', $unit))
            ->assertRedirect();

        $this->assertSoftDeleted('units', ['id' => $unit->id]);
        // Môn không bị xóa - chỉ mất gán khoa.
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'deleted_at' => null, 'faculty_code' => null]);
    }

    public function test_bulk_delete_subjects_cascades_lessons_for_every_selected_subject(): void
    {
        $admin = $this->superAdmin();
        $spec = $this->specialization();
        $subjectA = $this->subject($spec->id);
        $lessonA = $this->lesson($subjectA->id);
        $subjectB = $this->subject($spec->id);
        $lessonB = $this->lesson($subjectB->id);
        $subjectKept = $this->subject($spec->id);

        $this->actingAs($admin)
            ->post(route('subjects.bulk-delete'), ['ids' => [$subjectA->id, $subjectB->id]])
            ->assertRedirect();

        $this->assertSoftDeleted('subjects', ['id' => $subjectA->id]);
        $this->assertSoftDeleted('subjects', ['id' => $subjectB->id]);
        $this->assertSoftDeleted('subject_lessons', ['id' => $lessonA->id]);
        $this->assertSoftDeleted('subject_lessons', ['id' => $lessonB->id]);
        $this->assertDatabaseHas('subjects', ['id' => $subjectKept->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_lessons_cascades_to_child_lessons(): void
    {
        $admin = $this->superAdmin();
        $spec = $this->specialization();
        $subject = $this->subject($spec->id);
        $parent = $this->lesson($subject->id, ['lesson_kind' => 'unit']);
        $child = $this->lesson($subject->id, ['parent_id' => $parent->id]);
        $kept = $this->lesson($subject->id);

        $this->actingAs($admin)
            ->post(route('subject-lessons.bulk-delete'), ['ids' => [$parent->id]])
            ->assertRedirect();

        $this->assertSoftDeleted('subject_lessons', ['id' => $parent->id]);
        $this->assertSoftDeleted('subject_lessons', ['id' => $child->id]);
        $this->assertDatabaseHas('subject_lessons', ['id' => $kept->id, 'deleted_at' => null]);
    }
}
