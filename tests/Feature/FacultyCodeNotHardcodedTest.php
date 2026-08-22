<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use App\Support\TrainingScheduleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grades\Services\GradeAccess;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 — sửa theo phản hồi 06-08-2026: mã khoa không còn giới hạn cứng
 * vào danh sách K1-K8. Khoa mới (K9, hoặc quy ước khác) phải tạo và dùng
 * được ngay mà không cần sửa code, vì Mã đơn vị (units.code) CHÍNH LÀ mã
 * khoa khi functional_type = Khoa chuyên môn — không còn field "Mã phạm vi
 * khoa" tách biệt nữa.
 */
class FacultyCodeNotHardcodedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles']);
    }

    public function test_creating_a_faculty_unit_auto_derives_faculty_code_from_the_unit_code_without_a_separate_field(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'code' => 'K9',
            'name' => 'Khoa Mới K9',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'status' => 'active',
            // Không gửi faculty_code — trước đây form bắt chọn riêng từ danh
            // sách K1-K8; giờ server tự suy từ Mã đơn vị.
        ]);

        $response->assertRedirect();
        $unit = Unit::query()->where('code', 'K9')->firstOrFail();
        $this->assertSame('K9', $unit->faculty_code, 'faculty_code phải tự suy từ Mã đơn vị, không cần nhập riêng.');
    }

    public function test_a_non_k_prefixed_faculty_code_also_works(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'code' => 'KHOA-DUOC-LIEU',
            'name' => 'Khoa Dược liệu',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $unit = Unit::query()->where('code', 'KHOA-DUOC-LIEU')->firstOrFail();
        $this->assertSame('KHOA-DUOC-LIEU', $unit->faculty_code, 'Mã khoa không bắt buộc theo mẫu K + số — trường khác có thể quy ước khác.');
    }

    public function test_a_non_faculty_unit_gets_no_faculty_code(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'code' => 'PHONG-DT',
            'name' => 'Phòng Đào tạo Test',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $unit = Unit::query()->where('code', 'PHONG-DT')->firstOrFail();
        $this->assertNull($unit->faculty_code);
    }

    public function test_subject_can_be_assigned_to_a_k9_faculty_and_scope_filtering_finds_it(): void
    {
        $admin = $this->superAdmin();
        Unit::query()->create([
            'code' => 'K9',
            'name' => 'Khoa Mới K9',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K9',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $specialization = Specialization::query()->create([
            'name' => 'Ngành K9 Test', 'code' => 'K9SPEC-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn K9 Test', 'code' => 'K9-SUBJ-'.uniqid(), 'faculty_code' => 'K9',
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        $query = Subject::query();
        SubjectCodePrefix::applyFacultyCodeScope($query, 'K9');
        $this->assertTrue($query->whereKey($subject->id)->exists(), 'Khoa K9 phải lọc được môn dù ngoài danh sách K1-K8 cũ.');
    }

    public function test_k9_faculty_appears_in_the_grades_wizard_faculty_list(): void
    {
        $admin = $this->superAdmin();
        Unit::query()->create([
            'code' => 'K9',
            'name' => 'Khoa Mới K9',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K9',
            'status' => Unit::STATUS_ACTIVE,
        ]);

        $faculties = GradeAccess::accessibleFaculties($admin);

        $this->assertTrue(
            $faculties->contains(fn ($u) => $u->code === 'K9'),
            'Wizard Quản lý điểm phải liệt kê Khoa K9 dù ngoài danh sách mã cứng K1-K8.'
        );
    }

    public function test_faculty_code_for_unit_resolves_k9_correctly(): void
    {
        $unit = Unit::query()->create([
            'code' => 'K9',
            'name' => 'Khoa Mới K9',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K9',
            'status' => Unit::STATUS_ACTIVE,
        ]);

        $this->assertSame('K9', TrainingScheduleAccess::facultyCodeForUnit($unit));
    }
}
