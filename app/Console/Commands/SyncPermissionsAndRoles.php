<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsAndRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync {--reset : Reset all permissions and roles before syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions and roles for the application modules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting permissions and roles synchronization...');

        if ($this->option('reset')) {
            $this->resetPermissionsAndRoles();
        }

        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
        $this->createOrUpdateAdminUser();

        $this->info('✅ Permissions and roles synchronization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Reset all permissions and roles
     */
    private function resetPermissionsAndRoles()
    {
        $this->warn('Resetting all permissions and roles...');

        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Delete all permissions and roles
        Permission::query()->delete();
        Role::query()->delete();

        $this->info('✓ Reset completed');
    }

    /**
     * Create all permissions for modules
     * FORMAT: {module}.{action} (e.g., users.index, classes.create)
     */
    private function createPermissions()
    {
        $this->info('Creating permissions...');

        // Define modules and their actions
        // Format: 'module-name' => [actions]
        $modules = [
            // Core admin modules (super-admin only)
            'users' => ['index', 'show', 'create', 'edit', 'delete'],
            'roles' => ['index', 'show', 'create', 'edit', 'delete'],
            'permissions' => ['index', 'show', 'create', 'edit', 'delete'],

            // Management modules (super-admin only)
            'units' => ['index', 'show', 'create', 'edit', 'delete'],
            'buildings' => ['index', 'show', 'create', 'edit', 'delete'],
            'classrooms' => ['index', 'show', 'create', 'edit', 'delete'],
            'specializations' => ['index', 'show', 'create', 'edit', 'delete'],
            'subjects' => ['index', 'show', 'create', 'edit', 'delete'],
            // Bài học / khung CTĐT (Khoa + PDOT)
            'subject-lessons' => ['index', 'show', 'create', 'edit', 'delete'],
            'instructors' => ['index', 'show', 'create', 'edit', 'delete'],
            'classes' => ['index', 'show', 'create', 'edit', 'delete'],
            'training-schedules' => ['index', 'show', 'create', 'edit', 'delete'],
            'schedule-details' => ['index', 'show', 'create', 'edit', 'delete'],
            'teaching-assignments' => ['index', 'show', 'create', 'edit', 'delete'],

            // Schedule viewing modules (read-only for students/instructors)
            'student-schedule' => ['index', 'show'],
            'instructor-schedule' => ['index', 'show'],

            // Dashboard
            'dashboards' => ['index'],

            // LMS học tập (Sprint 1+) — không thay thế lịch/CTĐT
            'lms' => ['index', 'show', 'create', 'edit', 'delete', 'manage'],

            // Wi‑Fi trường (điểm danh QR LMS) — admin only, không gán instructor
            'campus-network' => ['index', 'show', 'create', 'edit', 'delete'],

            // Giờ chuẩn giảng viên (module StandardHours dùng prefix standard-hours.*)
            // ModuleBaseController yêu cầu index/show/create/edit/delete
            // override-approved: cấu hình luật/kỳ tính và sửa hồ sơ đã duyệt
            // (chỉ super-admin vì không nằm trong danh sách quyền manager).
            'standard-hours' => ['index', 'show', 'create', 'edit', 'delete', 'approve', 'override-approved'],
            'standard-hours.object-types' => ['view', 'manage'],
            'standard-hours.positions' => ['view', 'manage'],
            'standard-hours.departments' => ['view', 'manage'],
            'standard-hours.department-overtime' => ['view', 'manage'],
            'standard-hours.norm-reductions' => ['view', 'manage'],
            'standard-hours.conversion-categories' => ['view', 'manage'],
            'standard-hours.research-categories' => ['view', 'manage'],
            'standard-hours.conversion-records' => ['view', 'manage', 'approve'],
            'standard-hours.research-records' => ['view', 'manage', 'approve'],
            'standard-hours.external-activities' => ['view', 'manage', 'approve'],
            'standard-hours.calculations' => ['view', 'run', 'approve'],
            'standard-hours.reports' => ['view', 'export'],
            'standard-hours.hour-exchanges' => ['view', 'manage'],
            'standard-hours.settings' => ['view', 'manage'],

            // Trash bin (soft-deleted records) — super-admin / manager
            'trash' => ['index', 'show', 'restore', 'delete'],

            // Quản lý điểm (GV / Manager / Super-admin) — không student
            // manage = CN PDOT; approve = Ban giám hiệu (phê duyệt TN / hồ sơ)
            'grades' => ['index', 'show', 'create', 'edit', 'delete', 'manage', 'approve'],

            // Mẫu xuất chung Dashboard / LMS / Điểm
            'export-templates' => ['index', 'show', 'create', 'edit', 'delete'],

            // Tổ chức thi: tách quyền theo từng bước nghiệp vụ
            'exam-organization' => ['view', 'plan', 'assignment', 'execution', 'grading'],
            'inventory' => ['index', 'show', 'create', 'edit', 'delete', 'approve', 'export'],
            'leave-management' => ['index', 'show', 'create', 'edit', 'delete', 'approve', 'export'],
        ];

        // Ứng dụng khai báo trong ApplicationRegistry là nguồn của ma trận phân
        // quyền; gộp vào đây để mọi ô trong ma trận đều có permission thật.
        // Giữ nguyên danh sách legacy ở trên để không role nào bị mất quyền.
        foreach (ApplicationRegistry::permissionDefinitions() as $module => $actions) {
            $modules[$module] = array_values(array_unique(
                array_merge($modules[$module] ?? [], $actions)
            ));
        }

        $createdCount = 0;
        $existingCount = 0;

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                // CORRECT FORMAT: module.action (not action.module!)
                $permissionName = "{$module}.{$action}";

                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName],
                    ['guard_name' => 'web']
                );

                if ($permission->wasRecentlyCreated) {
                    $createdCount++;
                    $this->line("  ✓ Created: {$permissionName}");
                } else {
                    $existingCount++;
                }
            }
        }

        $this->info("✓ Permissions: {$createdCount} created, {$existingCount} already existed");
    }

    /**
     * Create roles
     * Roles: super-admin, manager, student, instructor
     */
    private function createRoles()
    {
        $this->info('Creating roles...');

        $createdCount = 0;
        $existingCount = 0;

        // Chỉ 8 vai trò chuẩn; vai trò phát sinh do quản trị viên tự tạo
        // trên màn hình Vai trò và không bị lệnh này đụng tới.
        foreach (RoleCatalog::groups() as $group) {
            $role = Role::firstOrCreate(
                ['name' => $group['name'], 'guard_name' => 'web']
            );

            if ($role->wasRecentlyCreated) {
                $createdCount++;
                $this->line("  ✓ Created: {$group['label']} ({$group['name']})");
            } else {
                $existingCount++;
            }
        }

        $this->info("✓ Roles: {$createdCount} created, {$existingCount} already existed");
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        $this->info('Assigning permissions to roles...');

        $allPermissions = Permission::query()->where('guard_name', 'web')->get();

        // Super Admin luôn giữ toàn bộ quyền.
        $superAdmin = Role::findByName(ManagementRole::SUPER_ADMIN);
        $superAdmin->syncPermissions($allPermissions);
        $this->line("  ✓ Super Admin assigned all {$allPermissions->count()} permissions");

        foreach (RoleCatalog::groups() as $group) {
            if ($group['name'] === ManagementRole::SUPER_ADMIN) {
                continue;
            }

            $role = Role::findByName($group['name']);
            $permissions = $allPermissions->whereIn('name', RoleCatalog::permissionNames($group['name']));
            $role->syncPermissions($permissions);
            $this->line("  ✓ {$group['label']} assigned {$permissions->count()} permissions");
        }

        $this->info('✓ Permission assignment completed');
    }

    /**
     * Create or update admin user
     */
    private function createOrUpdateAdminUser()
    {
        $this->info('Setting up admin user...');

        $adminEmail = 'admin@example.com';
        // Chỉ dùng khi TẠO MỚI — không reset pass mỗi lần permissions:sync / migrate
        $defaultPassword = 'password';

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($defaultPassword),
                'status' => 1,
            ]
        );

        // Assign super-admin role
        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
            $this->line("  ✓ Assigned super-admin role to {$adminEmail}");
        }

        // Giữ mật khẩu hiện tại; chỉ đảm bảo tài khoản active
        if (! $admin->wasRecentlyCreated) {
            if ((int) $admin->status !== 1) {
                $admin->update(['status' => 1]);
                $this->line('  ✓ Re-activated admin user (password unchanged)');
            } else {
                $this->line('  ✓ Admin user exists (password not modified)');
            }
        } else {
            $this->line("  ✓ Created admin user: {$adminEmail}");
            $this->warn("Default credentials (first create only): {$adminEmail} / {$defaultPassword}");
        }

        $this->info('✓ Admin user setup completed');
    }
}
