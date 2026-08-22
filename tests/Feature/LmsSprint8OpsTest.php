<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsChatMessage;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sprint 8 — ops / polish / safety smoke tests.
 */
class LmsSprint8OpsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gv;

    protected User $gvOther;

    protected User $student;

    protected Instructor $inst;

    protected LmsCourse $course;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'lms.index', 'lms.show', 'lms.create', 'lms.edit', 'lms.manage', 'lms.delete',
            'dashboards.index', 'instructor-schedule.index',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $adminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));

        $instRole = Role::findOrCreate('instructor', 'web');
        $instRole->syncPermissions(['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index']);

        $stuRole = Role::findOrCreate('student', 'web');
        $stuRole->syncPermissions(['lms.index', 'lms.show']);

        $this->admin = User::factory()->create([
            'email' => 'admin-s8@test.local',
            'user_type' => 'internal_user',
            'status' => 1,
        ]);
        $this->admin->assignRole($adminRole);

        $this->gv = User::factory()->create([
            'email' => 'gv-s8@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->gv->assignRole($instRole);

        $this->gvOther = User::factory()->create([
            'email' => 'gv2-s8@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->gvOther->assignRole($instRole);

        $this->student = User::factory()->create([
            'email' => 'hv-s8@test.local',
            'user_type' => 'student',
            'status' => 1,
        ]);
        $this->student->assignRole($stuRole);

        $this->inst = Instructor::query()->create([
            'name' => 'GV Sprint8',
            'code' => 'GV-S8',
            'email' => 'gv-s8-prof@test.local',
            'status' => 'active',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $this->gv->forceFill(['instructor_id' => $this->inst->id])->save();

        [$subjectId, $classId] = $this->seedSubjectAndClass('S8', $this->inst->id, $this->admin->id);

        $this->course = LmsCourse::query()->create([
            'title' => 'Course Sprint 8',
            'code' => 'LMS-S8-001',
            'status' => 'published',
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'instructor_id' => $this->inst->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'is_standalone' => false,
        ]);

        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->gv->id,
            'role' => LmsCourseMember::ROLE_LECTURER,
            'joined_at' => now(),
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => LmsCourseMember::ROLE_STUDENT,
            'joined_at' => now(),
        ]);
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
        if (Schema::hasColumn('classes', 'classroom')) {
            $classCols['classroom'] = 'P1';
        }
        if (Schema::hasColumn('classes', 'classroom_id')) {
            $classCols['classroom_id'] = null;
        }
        $classId = DB::table('classes')->insertGetId($classCols);

        return [(int) $subjectId, (int) $classId];
    }

    public function test_gv_outside_course_gets_403(): void
    {
        $response = $this->actingAs($this->gvOther)
            ->get(route('lms.learn.courses.show', ['course' => $this->course, 'mode' => 'teach']));

        $response->assertStatus(403);
    }

    public function test_inactive_lecturer_membership_cannot_open_teach_mode(): void
    {
        LmsCourseMember::query()
            ->where('lms_course_id', $this->course->id)
            ->where('user_id', $this->gv->id)
            ->update(['status' => LmsCourseMember::STATUS_INACTIVE]);
        $this->course->update(['instructor_id' => null]);

        $this->actingAs($this->gv)
            ->get(route('lms.learn.courses.show', ['course' => $this->course, 'mode' => 'teach']))
            ->assertStatus(403);
    }

    public function test_wizard_create_course_requires_permission(): void
    {
        $this->actingAs($this->gv)
            ->get(route('lms.courses.create'))
            ->assertStatus(403);
        $this->actingAs($this->gv)
            ->get(route('lms.provisioning.index'))
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->get(route('lms.courses.create'))
            ->assertOk()
            ->assertSee('Tạo khóa học LMS thủ công', false);
        $this->actingAs($this->admin)
            ->get(route('lms.provisioning.index'))
            ->assertOk()
            ->assertSee('LỊCH ĐÀO TẠO → LỚP HỌC PHẦN', false);
    }

    public function test_sync_members_command_runs(): void
    {
        $code = Artisan::call('lms:sync-members', ['--course' => $this->course->id, '--dry-run' => true]);
        $this->assertSame(0, $code);
    }

    public function test_resubmit_after_graded_resets_status(): void
    {
        $asg = LmsAssignment::query()->create([
            'lms_course_id' => $this->course->id,
            'title' => 'BT1',
            'max_score' => 10,
            'is_published' => true,
            'allow_late' => true,
            'created_by' => $this->gv->id,
        ]);

        $sub = LmsAssignmentSubmission::query()->create([
            'lms_assignment_id' => $asg->id,
            'user_id' => $this->student->id,
            'text_answer' => 'lan 1',
            'submitted_at' => now()->subDay(),
            'status' => 'graded',
            'score' => 7,
            'feedback' => 'Can cai thien',
            'graded_by' => $this->gv->id,
            'graded_at' => now()->subHour(),
        ]);

        $this->actingAs($this->student)
            ->post(route('lms.learn.assignments.submit', [$this->course, $asg]), [
                'text_answer' => 'lan 2 sau feedback',
            ])
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame('submitted', $sub->status);
        $this->assertNull($sub->score);
        $this->assertStringContainsString('lan 2', (string) $sub->text_answer);
    }

    public function test_submission_file_is_private_and_download_requires_owner(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $assignment = LmsAssignment::query()->create([
            'lms_course_id' => $this->course->id,
            'title' => 'Bài nộp riêng tư',
            'max_score' => 10,
            'is_published' => true,
            'allow_late' => true,
            'created_by' => $this->gv->id,
        ]);

        $this->actingAs($this->student)
            ->post(route('lms.learn.assignments.submit', [$this->course, $assignment]), [
                'file' => UploadedFile::fake()->create('bai-lam.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect();

        $submission = LmsAssignmentSubmission::query()->where('lms_assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame('local', $submission->disk);
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing($submission->file_path);
        $this->assertNull($submission->fileUrl());

        $this->actingAs($this->student)
            ->get(route('lms.learn.assignments.download-own', [$this->course, $assignment]))
            ->assertOk()
            ->assertDownload('bai-lam.pdf');

        $this->actingAs($this->gvOther)
            ->get(route('lms.learn.assignments.download-own', [$this->course, $assignment]))
            ->assertForbidden();
    }

    public function test_export_multi_form_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('lms.gradebook.export-multi'))
            ->assertOk()
            ->assertSee('Export', false);
    }

    public function test_chat_store_has_throttle_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('lms.learn.chat.store');
        $this->assertNotNull($route);
        // Bỏ qua middleware dạng Closure (gate theo ứng dụng) khi ghép chuỗi.
        $mws = implode(',', array_filter($route->gatherMiddleware(), 'is_string'));
        $this->assertStringContainsString('throttle', $mws);
    }

    public function test_checkin_has_throttle_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('lms.learn.attendance.checkin');
        $this->assertNotNull($route);
        // Bỏ qua middleware dạng Closure (gate theo ứng dụng) khi ghép chuỗi.
        $mws = implode(',', array_filter($route->gatherMiddleware(), 'is_string'));
        $this->assertStringContainsString('throttle', $mws);
    }

    public function test_chat_supports_group_messages_and_private_threads(): void
    {
        $group = $this->actingAs($this->student)
            ->postJson(route('lms.learn.chat.store', $this->course), [
                'body' => 'Tin nhắn chung từ học viên',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message.mine', true)
            ->json('message');

        $this->actingAs($this->gv)
            ->getJson(route('lms.learn.chat.poll', [
                'course' => $this->course,
                'after_id' => 0,
            ]))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Tin nhắn chung từ học viên'])
            ->assertJsonPath('can_send', true);

        $this->actingAs($this->gv)
            ->postJson(route('lms.learn.chat.store', $this->course), [
                'body' => 'Tin nhắn riêng từ giảng viên',
                'recipient_user_id' => $this->student->id,
            ])
            ->assertOk()
            ->assertJsonPath('message.recipient_user_id', $this->student->id);

        $this->actingAs($this->student)
            ->getJson(route('lms.learn.chat.history', [
                'course' => $this->course,
                'recipient_user_id' => $this->gv->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Tin nhắn riêng từ giảng viên'])
            ->assertJsonMissing(['body' => $group['body']]);

        $this->assertSame(2, LmsChatMessage::query()->where('lms_course_id', $this->course->id)->count());
    }

    public function test_locked_chat_blocks_sending_but_keeps_polling_available(): void
    {
        $this->actingAs($this->gv)
            ->postJson(route('lms.learn.chat.store', $this->course), ['body' => 'Thông báo trước khi khóa'])
            ->assertOk();

        $this->course->update(['chat_locked' => true]);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.chat.store', $this->course), ['body' => 'Không được gửi'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Chat đang bị khóa.');

        $this->actingAs($this->student)
            ->getJson(route('lms.learn.chat.poll', ['course' => $this->course, 'after_id' => 0]))
            ->assertOk()
            ->assertJsonPath('chat_locked', true)
            ->assertJsonPath('can_send', false)
            ->assertJsonFragment(['body' => 'Thông báo trước khi khóa']);
    }

    public function test_inactive_member_cannot_open_or_use_course_chat(): void
    {
        LmsCourseMember::query()
            ->where('lms_course_id', $this->course->id)
            ->where('user_id', $this->student->id)
            ->update(['status' => LmsCourseMember::STATUS_INACTIVE]);

        $this->actingAs($this->student)
            ->get(route('lms.learn.courses.show', $this->course))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.chat.store', $this->course), ['body' => 'Không còn thuộc lớp'])
            ->assertForbidden();
    }

    public function test_admin_chat_page_has_history_endpoint_and_visible_errors(): void
    {
        $this->actingAs($this->admin)
            ->get(route('lms.courses.chat.index', $this->course))
            ->assertOk()
            ->assertSee('lms-chat-room', false)
            ->assertSee(route('lms.courses.chat.history', $this->course), false)
            ->assertSee('chat-room-status', false);

        $this->actingAs($this->admin)
            ->getJson(route('lms.courses.chat.history', $this->course))
            ->assertOk()
            ->assertJsonStructure(['messages', 'can_moderate', 'chat_locked', 'can_send']);
    }
}
