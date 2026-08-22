<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RoleCatalog;
use App\Support\TrainingDept;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\Unit\Models\Unit;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / B3 — subjects.faculty_code là nguồn phân công khoa tường minh.
 * Hậu tố mã môn (K1..K8) chỉ còn là phương án dự phòng khi faculty_code
 * chưa được gán (dữ liệu cũ / môn của NVQY chưa phân công).
 */
class SubjectFacultyAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function facultyManager(string $facultyCode): User
    {
        $unit = Unit::query()->create([
            'code' => 'UNIT-'.$facultyCode.'-'.uniqid(),
            'name' => 'Khoa '.$facultyCode.' Test',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => $facultyCode,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $role = \Spatie\Permission\Models\Role::findByName(RoleCatalog::FACULTY_MANAGER, 'web');
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role_id' => $role->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles', 'unit']);
    }

    private function subject(array $overrides = []): Subject
    {
        $admin = User::factory()->create();
        $specialization = Specialization::query()->create(array_merge([
            'name' => 'Ngành Faculty Test',
            'code' => 'FAC-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED,
            'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]));

        return Subject::query()->create(array_merge([
            'name' => 'Môn Faculty Test',
            'code' => 'FAC-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id,
            'credits' => 2,
            'theory_hours' => 20,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'exam_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => true,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides));
    }

    public function test_explicit_faculty_code_wins_over_subject_code_suffix(): void
    {
        // Mã môn có hậu tố K3 nhưng đã được phân công tường minh cho K7.
        $subject = $this->subject(['code' => 'MON_LEGACY_K3', 'faculty_code' => 'K7']);

        $this->assertTrue(TrainingDept::subjectBelongsToFaculty($subject, $this->facultyManager('K7')));
        $this->assertSame('K7', $subject->faculty_code);

        // Không còn thuộc K3 dù mã môn vẫn có hậu tố đó.
        $query = Subject::query();
        SubjectCodePrefix::applyFacultyCodeScope($query, 'K3');
        $this->assertFalse($query->whereKey($subject->id)->exists());
    }

    public function test_null_faculty_code_falls_back_to_subject_code_suffix(): void
    {
        // Môn cũ chưa được phân công tường minh — vẫn lọc được bằng hậu tố mã môn.
        $subject = $this->subject(['code' => 'MON_LEGACY_K5', 'faculty_code' => null]);

        $query = Subject::query();
        SubjectCodePrefix::applyFacultyCodeScope($query, 'K5');
        $this->assertTrue($query->whereKey($subject->id)->exists());

        $otherQuery = Subject::query();
        SubjectCodePrefix::applyFacultyCodeScope($otherQuery, 'K6');
        $this->assertFalse($otherQuery->whereKey($subject->id)->exists());
    }

    public function test_faculty_code_survives_a_subject_code_that_no_longer_matches_any_suffix(): void
    {
        // Đúng kịch bản người dùng nêu: đổi mã môn theo quy ước khác (không
        // còn hậu tố K1..K8) nhưng phân công khoa vẫn phải đúng.
        $subject = $this->subject(['code' => 'B.6720101', 'faculty_code' => 'K2']);

        $this->assertTrue(TrainingDept::subjectBelongsToFaculty($subject, $this->facultyManager('K2')));

        $query = Subject::query();
        SubjectCodePrefix::applyFacultyCodeScope($query, 'K2');
        $this->assertTrue($query->whereKey($subject->id)->exists());
    }
}
