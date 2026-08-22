<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dashboard\Support\DashboardScope;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Role $instructorRole;

    protected Role $managerRole;

    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('dashboards.index', 'web');
        $this->instructorRole = Role::findOrCreate('instructor', 'web');
        $this->managerRole = Role::findOrCreate('manager', 'web');
        $this->superAdminRole = Role::findOrCreate('super-admin', 'web');

        $this->instructorRole->syncPermissions([$permission]);
        $this->managerRole->syncPermissions([$permission]);
        $this->superAdminRole->syncPermissions([$permission]);
    }

    public function test_instructor_dashboard_only_contains_their_own_statistics(): void
    {
        $instructor = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $user = User::factory()->create([
            'user_type' => 'instructor',
            'instructor_id' => $instructor->id,
            'status' => 1,
        ]);
        $user->assignRole($this->instructorRole);

        ScheduleDetail::factory()->create([
            'instructor_id' => $instructor->id,
            'date' => today()->toDateString(),
            'lesson_type' => 'theory',
        ]);
        ScheduleDetail::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'date' => today()->toDateString(),
            'lesson_type' => 'practice',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewIs('dashboard::index')
            ->assertSee('Số liệu trong phạm vi của bạn')
            ->assertSee('data-personal-instructor="1"', false)
            ->assertSee('<input type="hidden" name="instructor_id" id="instructorSelect"', false)
            ->assertDontSee('<select name="instructor_id" id="instructorSelect"', false)
            ->assertSee($instructor->code);

        $scope = $response->viewData('dashboard_scope');
        $accountStats = $response->viewData('account_stats');
        $overview = $response->viewData('overview');

        $this->assertSame(DashboardScope::TYPE_INSTRUCTOR, $scope['type']);
        $this->assertSame($instructor->id, $scope['instructor_id']);
        $this->assertSame(1, $accountStats['total_lessons']);
        $this->assertSame(1, $accountStats['theory_lessons']);
        $this->assertSame(0, $accountStats['practice_lessons']);
        $this->assertCount(1, $overview['table_rows']);

        $this->actingAs($user)
            ->getJson(route('dashboard.ajax.instructor-statistics', [
                'instructor_id' => $otherInstructor->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.overview.total_lessons', 0);
    }

    public function test_manager_dashboard_and_ajax_are_restricted_to_managed_unit(): void
    {
        $managedUnit = Unit::query()->create([
            'code' => 'UNIT-MANAGED',
            'name' => 'Khoa được quản lý',
            'level' => 1,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $otherUnit = Unit::query()->create([
            'code' => 'UNIT-OTHER',
            'name' => 'Khoa khác',
            'level' => 1,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $managedInstructor = Instructor::factory()->create(['unit_id' => $managedUnit->id]);
        $otherInstructor = Instructor::factory()->create(['unit_id' => $otherUnit->id]);
        $manager = User::factory()->create([
            'user_type' => 'internal_user',
            'unit_id' => $managedUnit->id,
            'status' => 1,
        ]);
        $manager->assignRole($this->managerRole);

        ScheduleDetail::factory()->create([
            'instructor_id' => $managedInstructor->id,
            'date' => today()->toDateString(),
            'lesson_type' => 'theory',
        ]);
        ScheduleDetail::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'date' => today()->toDateString(),
            'lesson_type' => 'practice',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $scope = $response->viewData('dashboard_scope');
        $accountStats = $response->viewData('account_stats');

        $this->assertSame(DashboardScope::TYPE_UNIT, $scope['type']);
        $this->assertSame([$managedUnit->id], $scope['unit_ids']);
        $this->assertSame(1, $accountStats['total_lessons']);
        $this->assertSame(1, $accountStats['theory_lessons']);
        $this->assertSame(0, $accountStats['practice_lessons']);

        $this->actingAs($manager)
            ->getJson(route('dashboard.ajax.instructor-statistics', [
                'unit_id' => $otherUnit->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.overview.total_lessons', 0);

        $this->actingAs($manager)
            ->getJson(route('dashboard.ajax.instructors-by-unit', ['unit_id' => $otherUnit->id]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_super_admin_dashboard_keeps_global_statistics(): void
    {
        $firstInstructor = Instructor::factory()->create();
        $secondInstructor = Instructor::factory()->create();
        $admin = User::factory()->create([
            'user_type' => 'internal_user',
            'status' => 1,
        ]);
        $admin->assignRole($this->superAdminRole);

        ScheduleDetail::factory()->create([
            'instructor_id' => $firstInstructor->id,
            'date' => today()->toDateString(),
        ]);
        ScheduleDetail::factory()->create([
            'instructor_id' => $secondInstructor->id,
            'date' => today()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $this->assertTrue($response->viewData('dashboard_scope')['is_global']);
        $this->assertSame(2, $response->viewData('account_stats')['total_lessons']);
        $this->assertCount(2, $response->viewData('overview')['table_rows']);
    }
}
