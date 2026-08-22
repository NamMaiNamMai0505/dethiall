<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleIntegritySprint25Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_view_integrity_health_but_other_roles_cannot(): void
    {
        $admin = $this->userWithRole('super-admin');
        $instructor = $this->userWithRole('instructor');
        $legacyRole = Role::findOrCreate('manager', 'web');
        $instructor->forceFill(['role_id' => $legacyRole->id])->save();

        $this->actingAs($admin)
            ->get(route('roles.integrity'))
            ->assertOk()
            ->assertSee('SỨC KHỎE PHÂN QUYỀN')
            ->assertSee('role_link_mismatch')
            ->assertSee('Có thể đồng bộ an toàn')
            ->assertSee(route('roles.integrity.repair-links'), false);

        $this->actingAs($instructor)
            ->get(route('roles.integrity'))
            ->assertForbidden();
    }

    public function test_web_repair_only_updates_selected_primary_role_links(): void
    {
        $admin = $this->userWithRole('super-admin');
        $first = $this->userWithRole('instructor');
        $second = $this->userWithRole('student');
        $legacyRole = Role::findOrCreate('manager', 'web');
        $instructorRole = Role::findOrCreate('instructor', 'web');
        $studentRole = Role::findOrCreate('student', 'web');
        $first->forceFill(['role_id' => $legacyRole->id])->save();
        $second->forceFill(['role_id' => $legacyRole->id])->save();

        $this->actingAs($admin)
            ->post(route('roles.integrity.repair-links'), [
                'user_ids' => [$first->id],
            ])
            ->assertRedirect(route('roles.integrity'))
            ->assertSessionHas('success');

        $this->assertSame($instructorRole->id, $first->fresh()->role_id);
        $this->assertSame($legacyRole->id, $second->fresh()->role_id);
        $this->assertTrue($first->fresh()->hasRole('instructor'));
        $this->assertFalse($first->fresh()->hasRole('manager'));
        $this->assertTrue($second->fresh()->hasRole('student'));
        $this->assertSame($studentRole->id, $second->roles()->first()->id);
    }

    public function test_repair_command_is_dry_run_by_default_and_supports_user_filter(): void
    {
        $user = $this->userWithRole('instructor');
        $untouched = $this->userWithRole('student');
        $legacyRole = Role::findOrCreate('manager', 'web');
        $instructorRole = Role::findOrCreate('instructor', 'web');
        $user->forceFill(['role_id' => $legacyRole->id])->save();
        $untouched->forceFill(['role_id' => $legacyRole->id])->save();

        $exitCode = Artisan::call('roles:repair-links', [
            '--json' => true,
            '--user' => [(string) $user->id],
        ]);
        $dryRun = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($dryRun['dry_run']);
        $this->assertSame(1, $dryRun['candidates']);
        $this->assertSame(0, $dryRun['applied']);
        $this->assertSame($legacyRole->id, $user->fresh()->role_id);

        Artisan::call('roles:repair-links', [
            '--apply' => true,
            '--json' => true,
            '--user' => [(string) $user->id],
        ]);
        $applied = json_decode(Artisan::output(), true);

        $this->assertSame(1, $applied['applied']);
        $this->assertSame($instructorRole->id, $user->fresh()->role_id);
        $this->assertSame($legacyRole->id, $untouched->fresh()->role_id);
    }

    public function test_repair_does_not_guess_when_user_has_multiple_actual_roles(): void
    {
        $user = $this->userWithRole('instructor');
        $studentRole = Role::findOrCreate('student', 'web');
        $legacyRole = Role::findOrCreate('manager', 'web');
        $user->assignRole($studentRole);
        $user->forceFill(['role_id' => $legacyRole->id])->save();

        Artisan::call('roles:repair-links', [
            '--apply' => true,
            '--json' => true,
            '--user' => [(string) $user->id],
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $payload['candidates']);
        $this->assertSame(0, $payload['applied']);
        $this->assertSame($legacyRole->id, $user->fresh()->role_id);
        $this->assertCount(2, $user->fresh()->roles);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create([
            'role_id' => $role->id,
            'user_type' => $roleName === 'instructor' ? 'instructor' : 'internal_user',
        ]);
        $user->syncRoles([$roleName]);

        return $user->fresh(['roles', 'roleRelation']);
    }
}
