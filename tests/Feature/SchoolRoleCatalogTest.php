<?php

namespace Tests\Feature;

use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Hệ thống chỉ còn 8 vai trò chuẩn, ma trận chia theo 6 phân hệ và mỗi ứng dụng
 * của LMS / Quản lý điểm đều gác được riêng.
 */
class SchoolRoleCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_matrix_is_split_into_the_six_approved_subsystems(): void
    {
        $this->assertSame(
            ['training', 'standard-hours', 'lms', 'grades', 'system', 'users'],
            array_column(ApplicationRegistry::subsystems(), 'key'),
            'Thứ tự phân hệ phải đúng: Lịch đào tạo → Giờ chuẩn → LMS → Điểm → Hệ thống → Người dùng.'
        );
    }

    public function test_lms_and_grades_are_listed_application_by_application(): void
    {
        $count = fn (string $subsystem) => count(array_filter(
            ApplicationRegistry::applications(),
            fn (array $a) => $a['subsystem'] === $subsystem
        ));

        // Trước đây cả LMS chỉ là 1 dòng, Quản lý điểm cũng 1 dòng.
        $this->assertGreaterThanOrEqual(15, $count('lms'));
        $this->assertGreaterThanOrEqual(8, $count('grades'));

        foreach (['lms.exams', 'lms.attendance', 'lms.gradebook', 'grades.books', 'grades.approval'] as $key) {
            $this->assertArrayHasKey($key, ApplicationRegistry::applications(), "Thiếu ứng dụng {$key}.");
        }
    }

    public function test_only_the_eight_standard_roles_are_declared(): void
    {
        $this->assertSame([
            ManagementRole::SUPER_ADMIN,
            ManagementRole::SYSTEM_MANAGER,
            ManagementRole::TRAINING_OFFICE_MANAGER,
            RoleCatalog::FACULTY_MANAGER,
            RoleCatalog::EXAM_MANAGER,
            RoleCatalog::RESEARCH_AGENCY_MANAGER,
            RoleCatalog::INSTRUCTOR,
            RoleCatalog::STUDENT,
        ], RoleCatalog::names());

        foreach (RoleCatalog::groups() as $group) {
            $this->assertNotSame('', trim($group['label']));
            $this->assertNotSame('', trim($group['description']));
            $this->assertNotSame('', trim($group['scope']), "Vai trò {$group['name']} phải nêu rõ phạm vi.");
        }
    }

    public function test_retired_roles_are_removed_and_holders_moved(): void
    {
        foreach (ManagementRole::RETIRED_ROLES as $name) {
            $this->assertNull(
                Role::query()->where('name', $name)->where('guard_name', 'web')->first(),
                "Vai trò cũ {$name} phải bị gỡ khỏi hệ thống."
            );
        }

        foreach ([RoleCatalog::FACULTY_MANAGER, RoleCatalog::RESEARCH_AGENCY_MANAGER] as $name) {
            $this->assertNotNull(Role::query()->where('name', $name)->where('guard_name', 'web')->first());
        }
    }

    public function test_faculty_manager_is_scoped_and_never_touches_user_administration(): void
    {
        $granted = RoleCatalog::permissionNames(RoleCatalog::FACULTY_MANAGER);

        foreach (['users.create', 'roles.edit', 'permissions.index'] as $forbidden) {
            $this->assertNotContains($forbidden, $granted);
        }

        // Khoa theo dõi được LMS và điểm của khoa mình.
        $this->assertContains('lms.gradebook.view', $granted);
        $this->assertContains('grades.conduct.edit', $granted);
    }

    public function test_school_manager_covers_everything_except_user_administration(): void
    {
        $granted = RoleCatalog::permissionNames(ManagementRole::SYSTEM_MANAGER);

        $this->assertContains('lms.exams.delete', $granted);
        $this->assertContains('grades.graduation.approve', $granted);
        $this->assertNotContains('roles.edit', $granted);
        $this->assertNotContains('users.delete', $granted);
    }

    public function test_application_gate_accepts_granular_and_legacy_permissions(): void
    {
        $abilities = ApplicationGate::abilities('lms.exams', ApplicationRegistry::ACTION_EDIT);

        $this->assertContains('lms.exams.edit', $abilities, 'Phải chấp nhận quyền chi tiết.');
        $this->assertContains('lms.edit', $abilities, 'Vẫn chấp nhận quyền tổng cũ trong giai đoạn chuyển đổi.');
    }

    public function test_grade_books_are_linked_to_the_academic_year_catalogue(): void
    {
        $this->assertTrue(
            Schema::hasColumn('grade_books', 'academic_year_id'),
            'Bảng điểm phải neo vào danh mục năm học, không chỉ giữ chuỗi tự do.'
        );
    }
}
