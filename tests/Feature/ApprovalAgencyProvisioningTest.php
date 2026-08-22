<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApprovalAgency;
use App\Support\ManagerUnitScope;
use App\Support\PermissionCheck;
use App\Support\RoleDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Grades\Services\GradeActor;
use Modules\Grades\Services\GradeAccess;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApprovalAgencyProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_provisions_accounts_idempotently_and_routes_approval_by_unit(): void
    {
        $researchUnit = Unit::query()->create([
            'code' => ApprovalAgency::RESEARCH_UNIT_CODE,
            'name' => 'Ban Khoa học Quân sự',
            'level' => 1,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $standardHoursUnit = Unit::query()->create([
            'code' => ApprovalAgency::STANDARD_HOURS_GRADES_UNIT_CODE,
            'name' => 'Ban KT&ĐBCLGDĐT',
            'level' => 1,
            'status' => Unit::STATUS_ACTIVE,
        ]);

        $this->artisan('approval-agencies:provision', [
            '--thu-password' => 'Thu-Test-Password-2026!',
            '--hieu-password' => 'Hieu-Test-Password-2026!',
        ])->assertSuccessful();

        $role = Role::findByName(ApprovalAgency::ROLE, 'web');
        $thu = User::query()->where('email', 'thunt@cdhc2.edu.vn')->firstOrFail();
        $hieu = User::query()->where('email', 'hieupt@cdhc.edu.vn')->firstOrFail();

        $this->assertSame('Nguyễn Thị Thu', $thu->name);
        $this->assertSame($researchUnit->id, $thu->unit_id);
        $this->assertSame($role->id, (int) $thu->role_id);
        $this->assertTrue($thu->hasRole(ApprovalAgency::ROLE));
        $this->assertTrue(Hash::check('Thu-Test-Password-2026!', $thu->password));

        $this->assertSame('Phạm Thị Hiếu', $hieu->name);
        $this->assertSame($standardHoursUnit->id, $hieu->unit_id);
        $this->assertSame($role->id, (int) $hieu->role_id);
        $this->assertTrue($hieu->hasRole(ApprovalAgency::ROLE));
        $this->assertTrue(Hash::check('Hieu-Test-Password-2026!', $hieu->password));

        $this->assertTrue(ApprovalAgency::canReviewResearch($thu));
        $this->assertFalse(ApprovalAgency::canReviewProfessionalActivities($thu));
        $this->assertFalse(ApprovalAgency::canApproveGrades($thu));
        $this->assertTrue($thu->can('standard-hours.approve'));
        $this->assertFalse($thu->can('grades.manage'));
        $this->assertSame(GradeActor::INSTRUCTOR, GradeActor::resolve($thu));

        $this->assertFalse(ApprovalAgency::canReviewResearch($hieu));
        $this->assertTrue(ApprovalAgency::canReviewProfessionalActivities($hieu));
        $this->assertTrue(ApprovalAgency::canReviewAnnualStandardHours($hieu));
        $this->assertTrue(ApprovalAgency::canApproveGrades($hieu));
        $this->assertTrue($hieu->can('standard-hours.approve'));
        $this->assertTrue($hieu->can('grades.manage'));
        $this->assertTrue($hieu->can('grades.approve'));
        $this->assertSame(GradeActor::APPROVAL_AGENCY, GradeActor::resolve($hieu));
        $this->assertTrue(GradeActor::canApprove($hieu));

        $this->assertFalse(ManagerUnitScope::isScoped($thu));
        $this->assertFalse(ManagerUnitScope::isScoped($hieu));
        $this->assertSame('Cơ Quan Phê Duyệt', RoleDisplay::label(ApprovalAgency::ROLE));

        $this->actingAs($hieu)
            ->get(route('standard-hours.research-records.index'))
            ->assertForbidden();
        $this->get(route('standard-hours.research-categories.index'))
            ->assertForbidden();
        $this->get(route('standard-hours.hub'))
            ->assertOk()
            ->assertSee('Kê khai HĐ chuyên môn')
            ->assertDontSee('Kê khai NCKH');

        $this->actingAs($thu)
            ->get(route('standard-hours.hub'))
            ->assertRedirect(route('standard-hours.research-records.index'));
        $this->get(route('standard-hours.research-records.index'))
            ->assertOk()
            ->assertDontSee('Tạo kê khai')
            ->assertDontSee('Thêm kê khai');
        $this->get(route('standard-hours.conversion-records.index'))
            ->assertForbidden();
        $this->get(route('standard-hours.my-results.index'))
            ->assertForbidden();
        $this->get(route('grades.hub'))
            ->assertForbidden();

        // Mã ban vẫn là quyết định cuối nếu role cũ vô tình có quyền rộng hơn.
        $role->givePermissionTo([
            Permission::findOrCreate('standard-hours.edit', 'web'),
            Permission::findOrCreate('grades.manage', 'web'),
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse(PermissionCheck::can($thu, 'standard-hours.edit'));
        $this->assertFalse(PermissionCheck::can($hieu, 'standard-hours.edit'));
        $this->assertFalse(PermissionCheck::can($thu, 'grades.manage'));
        $this->assertFalse(GradeAccess::canEnter($thu));
        $this->assertFalse(GradeAccess::isPdot($thu));
        $this->assertTrue($hieu->can('grades.manage'));
        $this->assertTrue(GradeAccess::canEnter($hieu));

        $thuPasswordHash = $thu->password;
        $hieuPasswordHash = $hieu->password;

        $this->artisan('approval-agencies:provision')->assertSuccessful();

        $this->assertSame(2, User::query()->whereIn('email', [
            'thunt@cdhc2.edu.vn',
            'hieupt@cdhc.edu.vn',
        ])->count());
        $this->assertSame($thuPasswordHash, $thu->fresh()->password);
        $this->assertSame($hieuPasswordHash, $hieu->fresh()->password);
    }
}
