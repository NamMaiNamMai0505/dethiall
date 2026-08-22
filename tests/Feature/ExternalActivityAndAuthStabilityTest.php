<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RoleDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExternalActivityAndAuthStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_manager_role_has_focused_permissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->where('name', RoleDisplay::EXAM_MANAGER)->firstOrFail();
        Permission::findOrCreate('standard-hours.override-approved', 'web');
        Permission::findOrCreate('users.edit', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($role->hasPermissionTo('standard-hours.edit'));
        $this->assertTrue($role->hasPermissionTo('grades.manage'));
        $this->assertFalse($role->hasPermissionTo('standard-hours.override-approved'));
        $this->assertFalse($role->hasPermissionTo('users.edit'));
    }

    public function test_instructor_can_submit_and_exam_manager_can_approve_external_activity(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Giảng viên tự kê khai: quyền Xem/Thêm/Sửa/Xóa của riêng ứng dụng
        // "Hoạt động ngoài HĐCM", không dùng quyền tổng của cả phân hệ.
        $instructorPermissions = [
            'standard-hours.index',
            'standard-hours.external-activities.view',
            'standard-hours.external-activities.create',
            'standard-hours.external-activities.edit',
            'standard-hours.external-activities.delete',
        ];

        foreach ($instructorPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $instructorRole = Role::findOrCreate('instructor', 'web');
        $instructorRole->syncPermissions($instructorPermissions);

        $examManagerRole = Role::findByName(RoleDisplay::EXAM_MANAGER, 'web');
        $instructor = Instructor::factory()->create();
        $instructorUser = User::factory()->create([
            'user_type' => 'instructor',
            'instructor_id' => $instructor->id,
            'status' => 1,
        ]);
        $instructorUser->syncRoles([$instructorRole]);

        $reviewer = User::factory()->create([
            'user_type' => 'internal_user',
            'status' => 1,
        ]);
        $reviewer->syncRoles([$examManagerRole]);

        $this->actingAs($instructorUser)
            ->get('/standard-hours/external-activities')
            ->assertOk()
            ->assertSee('KÊ KHAI HOẠT ĐỘNG NGOÀI HĐCM');

        $this->post('/standard-hours/external-activities', [
            'activity_type' => ExternalActivityRecord::TYPE_ORGANIZATION,
            'activity_name' => 'Tổ chức hội thao cấp trường',
            'activity_details' => 'Điều phối lực lượng và tổng hợp kết quả.',
            'from_date' => '2026-09-10',
            'to_date' => '2026-09-12',
            'role_or_position' => 'Thành viên ban tổ chức',
            'organizer' => 'Nhà trường',
            'location' => 'Sân vận động',
            'result' => 'Hoàn thành nhiệm vụ',
        ])->assertSessionHasNoErrors();

        $record = ExternalActivityRecord::query()->firstOrFail();
        $this->assertSame($instructor->id, $record->instructor_id);
        $this->assertSame(2026, $record->year);
        $this->assertSame(ExternalActivityRecord::STATUS_DRAFT, $record->status);

        $this->patch(route('standard-hours.external-activities.submit', $record))
            ->assertSessionHasNoErrors();
        $this->assertSame(
            ExternalActivityRecord::STATUS_SUBMITTED,
            $record->refresh()->status
        );

        $this->actingAs($reviewer)
            ->get(route('standard-hours.external-activities.show', $record))
            ->assertOk()
            ->assertSee('Tổ chức hội thao cấp trường');

        $this->patch(route('standard-hours.external-activities.approve', $record), [
            'review_note' => 'Đã đối chiếu minh chứng.',
        ])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame(ExternalActivityRecord::STATUS_APPROVED, $record->status);
        $this->assertSame($reviewer->id, $record->approved_by);
        $this->assertSame('Đã đối chiếu minh chứng.', $record->review_note);
    }

    public function test_login_form_is_not_turbo_cached_and_local_session_cookie_is_not_forced_secure(): void
    {
        $view = file_get_contents(
            base_path('modules/Authentication/Views/login.blade.php')
        );
        $sessionConfig = file_get_contents(base_path('config/session.php'));

        $this->assertStringContainsString(
            '<meta name="turbo-cache-control" content="no-cache">',
            $view
        );
        $this->assertStringContainsString('data-turbo="false"', $view);
        $this->assertStringContainsString(
            "env('APP_ENV', 'production') === 'production'",
            $sessionConfig
        );
        $this->assertFalse((bool) config('session.secure'));
    }
}
