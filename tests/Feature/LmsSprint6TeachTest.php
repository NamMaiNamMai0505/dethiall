<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsQuestion;
use Modules\Lms\Models\LmsQuestionBank;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sprint 6 T6 — scope GV + NHCH/gradebook teach.
 */
class LmsSprint6TeachTest extends TestCase
{
    use RefreshDatabase;

    protected User $gvA;

    protected User $gvB;

    protected Instructor $instA;

    protected Instructor $instB;

    protected LmsCourse $courseA;

    protected LmsCourse $courseB;

    protected LmsQuestionBank $bankA;

    protected LmsQuestion $questionA;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $role = Role::findOrCreate('instructor', 'web');
        $role->syncPermissions(['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index']);

        // Tạo user trước (created_by cho instructors)
        $this->gvA = User::factory()->create([
            'email' => 'gva-user@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->gvA->assignRole($role);

        $this->gvB = User::factory()->create([
            'email' => 'gvb-user@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->gvB->assignRole($role);

        $this->instA = Instructor::query()->create([
            'name' => 'GV A',
            'code' => 'GV-TEST-A',
            'email' => 'gva@test.local',
            'status' => 'active',
            'created_by' => $this->gvA->id,
            'updated_by' => $this->gvA->id,
        ]);
        $this->instB = Instructor::query()->create([
            'name' => 'GV B',
            'code' => 'GV-TEST-B',
            'email' => 'gvb@test.local',
            'status' => 'active',
            'created_by' => $this->gvB->id,
            'updated_by' => $this->gvB->id,
        ]);

        $this->gvA->forceFill(['instructor_id' => $this->instA->id])->save();
        $this->gvB->forceFill(['instructor_id' => $this->instB->id])->save();

        // Minimal subject + class for FK (MariaDB NOT NULL)
        [$subjectIdA, $classIdA] = $this->seedSubjectAndClass('A', $this->instA->id, $this->gvA->id);
        [$subjectIdB, $classIdB] = $this->seedSubjectAndClass('B', $this->instB->id, $this->gvB->id);

        $this->courseA = LmsCourse::query()->create([
            'code' => 'LMS-A-001',
            'title' => 'Khóa A',
            'status' => 'published',
            'subject_id' => $subjectIdA,
            'class_id' => $classIdA,
            'instructor_id' => $this->instA->id,
            'created_by' => $this->gvA->id,
            'updated_by' => $this->gvA->id,
        ]);
        $this->courseB = LmsCourse::query()->create([
            'code' => 'LMS-B-001',
            'title' => 'Khóa B',
            'status' => 'published',
            'subject_id' => $subjectIdB,
            'class_id' => $classIdB,
            'instructor_id' => $this->instB->id,
            'created_by' => $this->gvB->id,
            'updated_by' => $this->gvB->id,
        ]);

        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $this->gvA->id,
            'role' => LmsCourseMember::ROLE_LECTURER ?? 'lecturer',
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseB->id,
            'user_id' => $this->gvB->id,
            'role' => LmsCourseMember::ROLE_LECTURER ?? 'lecturer',
        ]);

        $this->bankA = LmsQuestionBank::query()->create([
            'lms_course_id' => $this->courseA->id,
            'title' => 'Bank A',
            'created_by' => $this->gvA->id,
        ]);
        $this->questionA = LmsQuestion::query()->create([
            'lms_question_bank_id' => $this->bankA->id,
            'type' => 'true_false',
            'stem' => 'Câu A1',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
            'points' => 1,
            'sort_order' => 1,
        ]);
    }

    public function test_gv_cannot_update_question_on_other_course(): void
    {
        $response = $this->actingAs($this->gvB)->put(
            route('lms.teach.exam-questions.update', [$this->courseA, $this->bankA, $this->questionA]),
            [
                'type' => 'true_false',
                'stem' => 'Hacked',
                'correct_answer' => 'false',
                'points' => 1,
            ]
        );

        $response->assertForbidden();
        $this->assertSame('Câu A1', $this->questionA->fresh()->stem);
    }

    public function test_gv_can_update_own_question(): void
    {
        $response = $this->actingAs($this->gvA)->put(
            route('lms.teach.exam-questions.update', [$this->courseA, $this->bankA, $this->questionA]),
            [
                'type' => 'true_false',
                'stem' => 'Câu A1 updated',
                'correct_answer' => 'false',
                'points' => 2,
            ]
        );

        $response->assertRedirect();
        $this->assertSame('Câu A1 updated', $this->questionA->fresh()->stem);
        $this->assertEquals(2.0, (float) $this->questionA->fresh()->points);
    }

    public function test_gv_can_create_exam_with_selected_questions(): void
    {
        $q2 = LmsQuestion::query()->create([
            'lms_question_bank_id' => $this->bankA->id,
            'type' => 'short',
            'stem' => 'Câu A2',
            'correct_answer' => 'ok',
            'points' => 1,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->gvA)->post(
            route('lms.teach.exams.store', $this->courseA),
            [
                'title' => 'Đề chọn lẻ',
                'duration_minutes' => 30,
                'max_attempts' => 1,
                'question_ids' => [$this->questionA->id, $q2->id],
                'is_published' => 1,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('lms_exams', [
            'lms_course_id' => $this->courseA->id,
            'title' => 'Đề chọn lẻ',
        ]);
        $exam = \Modules\Lms\Models\LmsExam::query()->where('title', 'Đề chọn lẻ')->first();
        $this->assertNotNull($exam);
        $this->assertCount(2, $exam->questions);
    }

    public function test_gv_cannot_override_grade_on_other_course(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);

        $response = $this->actingAs($this->gvB)->post(
            route('lms.teach.gradebook.override', [$this->courseA, $student]),
            [
                'final_score' => 9.5,
                'note' => 'hack',
                'teach' => 1,
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('lms_gradebook_rows', [
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'final_score' => 9.5,
        ]);
    }

    public function test_gv_can_override_grade_on_own_course(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);

        $response = $this->actingAs($this->gvA)->post(
            route('lms.teach.gradebook.override', [$this->courseA, $student]),
            [
                'final_score' => 8.5,
                'note' => 'GV chốt',
                'teach' => 1,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('lms_gradebook_rows', [
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'final_score' => 8.5,
        ]);
    }

    public function test_exam_take_page_renders_for_enrolled_student(): void
    {
        $q2 = LmsQuestion::query()->create([
            'lms_question_bank_id' => $this->bankA->id,
            'type' => 'true_false',
            'stem' => 'Câu A2',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
            'points' => 1,
            'sort_order' => 2,
        ]);

        $exam = \Modules\Lms\Models\LmsExam::query()->create([
            'lms_course_id' => $this->courseA->id,
            'title' => 'Bài thi test render',
            'duration_minutes' => 45,
            'max_attempts' => 1,
            'pass_score' => 50,
            'is_published' => true,
            'created_by' => $this->gvA->id,
        ]);
        $exam->questions()->sync([
            $this->questionA->id => ['sort_order' => 1, 'points' => 1],
            $q2->id => ['sort_order' => 2, 'points' => 1],
        ]);

        $studentRole = Role::findOrCreate('student', 'web');
        $studentRole->syncPermissions(['lms.index', 'lms.show']);
        $student = User::factory()->create(['user_type' => 'student']);
        $student->assignRole($studentRole);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);

        // Regression: @json(route('name', [$a, $b, $c])) bi Blade compileJson() cat cut
        // (explode(',', ...) ngay tho) khien trang nay tra ve 500 ParseError truoc day.
        $response = $this->actingAs($student)->get(
            route('lms.learn.exams.take', [$this->courseA, $exam])
        );

        $response->assertOk();
        $response->assertSee('Bài thi test render');
        // Bảng theo dõi câu đã làm / chưa làm — mỗi câu 1 nút nav gắn data-question-id
        // trỏ tới thẻ câu hỏi tương ứng (data-nav-target -> #exam-q-N) để JS cuộn tới
        // và tô màu khi đã trả lời.
        $response->assertSee('exam-nav-grid', false);
        $response->assertSee('data-question-id="'.$this->questionA->id.'"', false);
        $response->assertSee('data-nav-target="exam-q-1"', false);
        // Xác nhận trước khi nộp bài - modal tự viết riêng cho trang này (không
        // qua confirm-modal dùng chung, tránh phụ thuộc trạng thái nơi khác).
        $response->assertSee('id="exam-submit-confirm"', false);
        $response->assertSee('id="btn-confirm-submit-exam"', false);
        $response->assertSee('Nộp bài thi?');
    }

    public function test_exam_score_is_weighted_by_per_question_points_not_raw_count(): void
    {
        // Câu 1 nặng 5 điểm, câu 2 chỉ 1 điểm — điểm cuối phải là tổng điểm của câu
        // trả lời đúng, không phải tỉ lệ "số câu đúng / tổng số câu".
        $q2 = LmsQuestion::query()->create([
            'lms_question_bank_id' => $this->bankA->id,
            'type' => 'true_false',
            'stem' => 'Câu A2',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
            'points' => 1,
            'sort_order' => 2,
        ]);

        $exam = \Modules\Lms\Models\LmsExam::query()->create([
            'lms_course_id' => $this->courseA->id,
            'title' => 'Bài thi trọng số điểm',
            'duration_minutes' => 45,
            'max_attempts' => 1,
            'pass_score' => 50,
            'is_published' => true,
            'created_by' => $this->gvA->id,
        ]);
        $exam->questions()->sync([
            $this->questionA->id => ['sort_order' => 1, 'points' => 5],
            $q2->id => ['sort_order' => 2, 'points' => 1],
        ]);

        $studentRole = Role::findOrCreate('student', 'web');
        $studentRole->syncPermissions(['lms.index', 'lms.show']);
        $student = User::factory()->create(['user_type' => 'student']);
        $student->assignRole($studentRole);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);

        $service = app(\Modules\Lms\Services\LmsExamService::class);
        $this->actingAs($student);
        $attempt = $service->startAttempt($exam);
        $attempt = $service->submitAttempt($attempt, [
            (string) $this->questionA->id => 'true', // đúng, 5đ
            (string) $q2->id => 'false', // sai, 0đ
        ]);

        $this->assertEquals(5.0, (float) $attempt->score);
        $this->assertEquals(6.0, (float) $attempt->max_score);
    }

    public function test_exam_result_page_shows_scale_10_and_per_question_detail(): void
    {
        // Cau 1 dung (5d), cau 2 sai (0d) -> 5/6 diem tho -> quy doi thang 10 = 8.33.
        $q2 = LmsQuestion::query()->create([
            'lms_question_bank_id' => $this->bankA->id,
            'type' => 'true_false',
            'stem' => 'Câu A2',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
            'points' => 1,
            'sort_order' => 2,
        ]);

        $exam = \Modules\Lms\Models\LmsExam::query()->create([
            'lms_course_id' => $this->courseA->id,
            'title' => 'Bài thi xem chi tiết',
            'duration_minutes' => 45,
            'max_attempts' => 1,
            'pass_score' => 5,
            'is_published' => true,
            'created_by' => $this->gvA->id,
        ]);
        $exam->questions()->sync([
            $this->questionA->id => ['sort_order' => 1, 'points' => 5],
            $q2->id => ['sort_order' => 2, 'points' => 1],
        ]);

        $studentRole = Role::findOrCreate('student', 'web');
        $studentRole->syncPermissions(['lms.index', 'lms.show']);
        $student = User::factory()->create(['user_type' => 'student']);
        $student->assignRole($studentRole);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->courseA->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);

        $service = app(\Modules\Lms\Services\LmsExamService::class);
        $this->actingAs($student);
        $attempt = $service->startAttempt($exam);
        $attempt = $service->submitAttempt($attempt, [
            (string) $this->questionA->id => 'true', // đúng
            (string) $q2->id => 'false', // sai
        ]);

        $response = $this->get(route('lms.learn.exams.result', [$this->courseA, $exam, $attempt]));

        $response->assertOk();
        // Thang 10: 5/6 * 10 = 8.33
        $response->assertSee('8.33');
        $response->assertSee('/ 10 điểm', false);
        $response->assertSee('Đạt');
        $response->assertSee('Xem chi tiết bài thi');
        $response->assertSee('Đúng 1/2 câu', false);
        $response->assertSee('Đáp án đúng', false);
    }

    /** @return array{0:int,1:int} [subject_id, class_id] */
    protected function seedSubjectAndClass(string $tag, int $instructorId, int $userId): array
    {
        $specId = DB::table('specializations')->insertGetId([
            'name' => 'Spec '.$tag,
            'code' => 'SPEC-'.$tag.'-'.uniqid(),
            'description' => null,
            'level' => 'beginner',
            'duration_months' => 12,
            'certification_type' => 'certificate',
            'is_active' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectCols = [
            'name' => 'Mon '.$tag,
            'code' => 'SUB-'.$tag.'-'.uniqid(),
            'description' => null,
            'specialization_id' => $specId,
            'credits' => 1,
            'theory_hours' => 1,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => 1,
            'is_active' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('subjects', 'semester')) {
            $subjectCols['semester'] = 1;
        }
        $subjectId = DB::table('subjects')->insertGetId($subjectCols);

        $classCols = [
            'name' => 'Lop '.$tag,
            'code' => 'CLS-'.$tag.'-'.uniqid(),
            'specialization_id' => $specId,
            'instructor_id' => $instructorId,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'duration_months' => 6,
            'management_unit' => 'Test',
            'max_students' => 30,
            'current_students' => 0,
            'is_active' => 1,
            'description' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        // schema lịch sử: classroom string hoặc classroom_id
        if (Schema::hasColumn('classes', 'classroom')) {
            $classCols['classroom'] = 'P1';
        }
        if (Schema::hasColumn('classes', 'classroom_id')) {
            $classCols['classroom_id'] = null;
        }
        $classId = DB::table('classes')->insertGetId($classCols);

        return [(int) $subjectId, (int) $classId];
    }
}
