<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsScormPackage;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / D2 — trang phát SCORM phải có nút toàn màn hình; thoát bằng Esc
 * là hành vi mặc định của Fullscreen API nên không cần code riêng, chỉ cần
 * requestFullscreen() được gọi đúng chuẩn (không dùng cơ chế tự chế nào khác
 * mà trình duyệt không hiểu phím Esc). Tiến độ (commit SCORM) phải độc lập
 * với trạng thái fullscreen — vẫn còn nguyên trong DOM/script sau khi bấm.
 */
class LmsScormFullscreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_scorm_player_ships_a_standard_fullscreen_button_and_keeps_the_commit_script(): void
    {
        $admin = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $admin->syncRoles([$role->name]);

        $specialization = Specialization::query()->create([
            'name' => 'Ngành Scorm Test', 'code' => 'SCM-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Scorm Test', 'code' => 'SCM-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $course = LmsCourse::query()->create([
            'title' => 'Khóa Scorm Test',
            'subject_id' => $subject->id,
            'is_standalone' => true,
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $scorm = LmsScormPackage::query()->create([
            'lms_course_id' => $course->id,
            'title' => 'Gói SCORM Test',
            'version' => '1.2',
            'launch_path' => 'index.html',
            'extract_path' => 'scorm/test-package',
            'is_published' => true,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('lms.learn.scorm.play', [$course, $scorm]));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('id="scorm-fs"', $html, 'Phải có nút bật toàn màn hình.');
        $this->assertStringContainsString('requestFullscreen', $html, 'Phải dùng Fullscreen API chuẩn — Esc thoát tự động, không cần code riêng.');
        $this->assertStringContainsString('LMSCommit', $html, 'Adapter SCORM phải còn nguyên — tiến độ vẫn ghi nhận độc lập với fullscreen.');
        $this->assertStringContainsString('commitToServer', $html);
    }
}
