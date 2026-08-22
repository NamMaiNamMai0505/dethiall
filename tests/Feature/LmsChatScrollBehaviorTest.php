<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lms\Models\LmsCourse;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / D1 — chat không được ép cuộn xuống cuối khi người dùng đang
 * đọc tin cũ (đã kéo lên) và poll() nhận thêm tin mới từ người khác.
 */
class LmsChatScrollBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_chat_room_only_scrolls_on_poll_when_the_reader_is_already_near_the_bottom(): void
    {
        $admin = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $admin->syncRoles([$role->name]);

        $specialization = Specialization::query()->create([
            'name' => 'Ngành Chat Test', 'code' => 'CHT-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Chat Test', 'code' => 'CHT-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $course = LmsCourse::query()->create([
            'title' => 'Khóa Chat Test',
            'subject_id' => $subject->id,
            'is_standalone' => true,
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('lms.courses.chat.index', $course));

        $response->assertOk();
        $html = $response->getContent();
        // Trước fix: poll() luôn gọi scrollBottom() vô điều kiện sau khi thêm
        // tin — người đang đọc tin cũ bị ép nhảy về cuối. Giờ phải chốt
        // "đang gần cuối?" TRƯỚC khi thêm tin.
        $this->assertStringContainsString('isNearBottom', $html);
        $this->assertStringContainsString('stickToBottom', $html);
        $this->assertStringContainsString('scroll-behavior: smooth', $html);
    }
}
