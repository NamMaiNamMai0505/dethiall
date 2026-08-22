<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManagementRoleAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_clean_management_role_configuration_passes_strict_audit(): void
    {
        $exitCode = Artisan::call('management-roles:audit', [
            '--json' => true,
            '--strict' => true,
        ]);
        $payload = $this->auditPayload();

        $this->assertSame(0, $exitCode);
        $this->assertTrue($payload['ok']);
        $this->assertSame(3, $payload['summary']['roles_checked']);
        $this->assertSame(0, $payload['summary']['errors']);
        $this->assertSame([], $payload['issues']);
    }

    public function test_strict_audit_detects_role_unit_mismatch(): void
    {
        $trainingUnit = Unit::query()->create([
            'code' => 'PDT-AUDIT',
            'name' => 'Phòng Đào tạo Audit',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $role = Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER, 'web');
        $user = User::factory()->create([
            'unit_id' => $trainingUnit->id,
            'role_id' => $role->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$role->name]);

        $exitCode = Artisan::call('management-roles:audit', [
            '--json' => true,
            '--strict' => true,
        ]);
        $payload = $this->auditPayload();

        $this->assertSame(1, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertContains('invalid_role_scope', array_column($payload['issues'], 'code'));
        $this->assertStringContainsString(
            'chỉ được gán cho đơn vị có chức năng “Khoa”',
            collect($payload['issues'])->firstWhere('code', 'invalid_role_scope')['message']
        );
    }

    public function test_audit_reports_legacy_manager_without_writing_data(): void
    {
        $faculty = Unit::query()->create([
            'code' => 'K3-AUDIT',
            'name' => 'Khoa Audit',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K3',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $legacyRole = Role::findOrCreate(ManagementRole::LEGACY_MANAGER, 'web');
        $user = User::factory()->create([
            'unit_id' => $faculty->id,
            'role_id' => $legacyRole->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$legacyRole->name]);

        $exitCode = Artisan::call('management-roles:audit', ['--json' => true]);
        $payload = $this->auditPayload();

        $this->assertSame(0, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertSame(1, $payload['summary']['warnings']);
        $this->assertContains('legacy_manager', array_column($payload['issues'], 'code'));
        $this->assertTrue($user->fresh()->hasRole(ManagementRole::LEGACY_MANAGER));
    }

    public function test_audit_detects_permission_matrix_drift(): void
    {
        $role = Role::findByName(ManagementRole::TRAINING_OFFICE_MANAGER, 'web');
        $role->revokePermissionTo('training-schedules.edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $exitCode = Artisan::call('management-roles:audit', [
            '--json' => true,
            '--strict' => true,
        ]);
        $payload = $this->auditPayload();

        $this->assertSame(1, $exitCode);
        $this->assertContains('missing_permissions', array_column($payload['issues'], 'code'));
        $this->assertStringContainsString(
            'training-schedules.edit',
            collect($payload['issues'])->firstWhere('code', 'missing_permissions')['message']
        );
    }

    public function test_audit_detects_duplicate_faculty_scope_codes(): void
    {
        foreach (['FAC-AUDIT-1', 'FAC-AUDIT-2'] as $code) {
            Unit::query()->create([
                'code' => $code,
                'name' => 'Khoa '.$code,
                'functional_type' => Unit::FUNCTIONAL_FACULTY,
                'faculty_code' => 'K6',
                'status' => Unit::STATUS_ACTIVE,
            ]);
        }

        $exitCode = Artisan::call('management-roles:audit', [
            '--json' => true,
            '--strict' => true,
        ]);
        $payload = $this->auditPayload();

        $this->assertSame(1, $exitCode);
        $this->assertContains('duplicate_faculty_code', array_column($payload['issues'], 'code'));
        $this->assertStringContainsString(
            'K6',
            collect($payload['issues'])->firstWhere('code', 'duplicate_faculty_code')['subject']
        );
    }

    /** @return array<string, mixed> */
    private function auditPayload(): array
    {
        $payload = json_decode(Artisan::output(), true);
        $this->assertIsArray($payload, Artisan::output());

        return $payload;
    }
}
