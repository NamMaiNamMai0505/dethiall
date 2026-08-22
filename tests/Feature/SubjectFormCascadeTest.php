<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / B1 — form Sửa môn học phải có ô Hệ đào tạo liên động với Ngành,
 * giống form Tạo môn học (trước đây form Sửa thiếu hẳn ô Hệ).
 */
class SubjectFormCascadeTest extends TestCase
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

    public function test_create_form_still_has_the_training_system_field(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('subjects.create'))
            ->assertOk()
            ->assertSee('name="training_system_id"', false);
    }

    public function test_edit_form_now_has_the_training_system_field_preselected(): void
    {
        $admin = $this->superAdmin();
        $system = TrainingSystem::query()->where('code', 'military')->firstOrFail();
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Cascade Test',
            'code' => 'CASCADE-'.$admin->id,
            'training_system_id' => $system->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Cascade Test',
            'code' => 'CASCADE-SUBJ-'.$admin->id,
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
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('subjects.edit', $subject));

        $response->assertOk()
            ->assertSee('name="training_system_id"', false)
            ->assertSee('<option value="'.$system->id.'" selected', false);
    }
}
