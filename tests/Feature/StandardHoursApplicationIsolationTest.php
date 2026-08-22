<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Chốt yêu cầu nghiệp vụ: quyền tổng của phân hệ Giờ chuẩn chỉ mở được cửa vào
 * phân hệ, KHÔNG mở được các ứng dụng bên trong. Tick ứng dụng nào thì chỉ dùng
 * được ứng dụng đó.
 */
class StandardHoursApplicationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param  list<string>  $permissions */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);

        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_subsystem_wide_permission_no_longer_opens_every_application(): void
    {
        $user = $this->userWith([
            'standard-hours.index',
            'standard-hours.show',
            'standard-hours.create',
            'standard-hours.edit',
            'standard-hours.delete',
        ]);

        // Vào được cửa phân hệ…
        $this->actingAs($user)->get('/standard-hours')->assertOk();

        // …nhưng không ứng dụng con nào mở ra.
        foreach ([
            '/standard-hours/object-types',
            '/standard-hours/positions',
            '/standard-hours/departments',
            '/standard-hours/conversion-categories',
            '/standard-hours/research-categories',
            '/standard-hours/hour-exchanges',
            '/standard-hours/calculations',
            '/standard-hours/reports',
        ] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertForbidden();
        }
    }

    public function test_ticking_one_application_grants_only_that_application(): void
    {
        $user = $this->userWith([
            'standard-hours.index',
            'standard-hours.object-types.view',
        ]);

        $this->actingAs($user)->get('/standard-hours/object-types')->assertOk();
        $this->actingAs($user)->get('/standard-hours/positions')->assertForbidden();
        $this->actingAs($user)->get('/standard-hours/departments')->assertForbidden();
    }

    public function test_view_permission_does_not_grant_write_on_the_same_application(): void
    {
        $user = $this->userWith([
            'standard-hours.index',
            'standard-hours.object-types.view',
        ]);

        $this->actingAs($user)->get('/standard-hours/object-types')->assertOk();
        $this->actingAs($user)->get('/standard-hours/object-types/create')->assertForbidden();
    }

    public function test_create_permission_grants_the_create_screen(): void
    {
        $user = $this->userWith([
            'standard-hours.index',
            'standard-hours.object-types.view',
            'standard-hours.object-types.create',
        ]);

        $this->actingAs($user)->get('/standard-hours/object-types/create')->assertOk();
    }

    public function test_settings_pages_are_gated_separately(): void
    {
        $periodOnly = $this->userWith([
            'standard-hours.index',
            'standard-hours.settings.period-mode.view',
        ]);

        $this->actingAs($periodOnly)->get('/standard-hours/settings/period-mode')->assertOk();
        $this->actingAs($periodOnly)->get('/standard-hours/settings/research-rules')->assertForbidden();

        $rulesOnly = $this->userWith([
            'standard-hours.index',
            'standard-hours.settings.research-rules.view',
        ]);

        $this->actingAs($rulesOnly)->get('/standard-hours/settings/research-rules')->assertOk();
        $this->actingAs($rulesOnly)->get('/standard-hours/settings/period-mode')->assertForbidden();
    }

    public function test_legacy_per_application_manage_permission_still_works(): void
    {
        $user = $this->userWith([
            'standard-hours.index',
            'standard-hours.object-types.view',
            'standard-hours.object-types.manage',
        ]);

        $this->actingAs($user)->get('/standard-hours/object-types/create')->assertOk();
    }
}
