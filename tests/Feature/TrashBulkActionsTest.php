<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Thùng rác: công tắc "Chọn nhiều" cho khôi phục và xóa vĩnh viễn hàng loạt.
 */
class TrashBulkActionsTest extends TestCase
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

    private function trashedSubjects(int $count): array
    {
        $spec = Specialization::query()->create([
            'name' => 'Ngành Trash Test',
            'code' => 'TRASH-'.uniqid(),
            'level' => Specialization::LEVEL_BEGINNER,
            'duration_months' => 12,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
        ]);

        $subjects = [];
        for ($i = 0; $i < $count; $i++) {
            $subject = Subject::query()->create([
                'name' => 'Môn Trash Test '.$i,
                'code' => 'TRASH-SUBJ-'.uniqid(),
                'specialization_id' => $spec->id,
                'credits' => 1,
                'theory_hours' => 1,
                'practice_hours' => 0,
                'self_study_hours' => 0,
                'level' => 'basic',
                'assessment_method' => 'exam',
                'is_required' => true,
                'is_active' => true,
            ]);
            $subject->delete();
            $subjects[] = $subject;
        }

        return $subjects;
    }

    public function test_bulk_restore_restores_every_selected_item(): void
    {
        $admin = $this->superAdmin();
        $subjects = $this->trashedSubjects(3);

        $items = array_map(fn (Subject $s) => 'subjects:'.$s->id, $subjects);

        $this->actingAs($admin)
            ->post(route('trash.bulk-restore'), ['items' => $items])
            ->assertRedirect();

        foreach ($subjects as $subject) {
            $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'deleted_at' => null]);
        }
    }

    public function test_bulk_force_delete_permanently_removes_every_selected_item(): void
    {
        $admin = $this->superAdmin();
        $subjects = $this->trashedSubjects(3);

        $items = array_map(fn (Subject $s) => 'subjects:'.$s->id, $subjects);

        $this->actingAs($admin)
            ->delete(route('trash.bulk-force-delete'), ['items' => $items])
            ->assertRedirect();

        foreach ($subjects as $subject) {
            $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
        }
    }

    public function test_non_super_admin_cannot_bulk_force_delete(): void
    {
        $role = Role::findOrCreate('manager-test', 'web');
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->syncRoles([$role->name]);

        $subjects = $this->trashedSubjects(1);
        $items = array_map(fn (Subject $s) => 'subjects:'.$s->id, $subjects);

        $this->actingAs($user)
            ->delete(route('trash.bulk-force-delete'), ['items' => $items])
            ->assertForbidden();

        $this->assertDatabaseHas('subjects', ['id' => $subjects[0]->id]);
    }
}
