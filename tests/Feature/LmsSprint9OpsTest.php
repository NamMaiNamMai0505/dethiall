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
use Modules\Lms\Models\LmsAssignmentSubmissionVersion;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsScormPackage;
use Modules\Lms\Services\LmsMaterialService;
use Modules\Lms\Services\LmsScormService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sprint 9 smoke — T3/T4/H3/T8 (T5/H5 out of scope).
 */
class LmsSprint9OpsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gv;

    protected User $student;

    protected Instructor $inst;

    protected LmsCourse $course;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'lms.index', 'lms.show', 'lms.create', 'lms.edit', 'lms.manage',
            'instructor-schedule.index',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $adminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->pluck('name'));
        $instRole = Role::findOrCreate('instructor', 'web');
        $instRole->syncPermissions(['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index']);
        $stuRole = Role::findOrCreate('student', 'web');
        $stuRole->syncPermissions(['lms.index', 'lms.show']);

        $this->admin = User::factory()->create(['email' => 'a-s9@test.local', 'user_type' => 'internal_user', 'status' => 1]);
        $this->admin->assignRole($adminRole);
        $this->gv = User::factory()->create(['email' => 'g-s9@test.local', 'user_type' => 'instructor', 'status' => 1]);
        $this->gv->assignRole($instRole);
        $this->student = User::factory()->create(['email' => 's-s9@test.local', 'user_type' => 'student', 'status' => 1]);
        $this->student->assignRole($stuRole);

        $this->inst = Instructor::query()->create([
            'name' => 'GV S9', 'code' => 'GV-S9', 'email' => 'gvs9@test.local', 'status' => 'active',
            'created_by' => $this->admin->id, 'updated_by' => $this->admin->id,
        ]);
        $this->gv->forceFill(['instructor_id' => $this->inst->id])->save();

        [$sid, $cid] = $this->seedSubjectAndClass('S9', $this->inst->id, $this->admin->id);
        $this->course = LmsCourse::query()->create([
            'title' => 'Course S9', 'code' => 'LMS-S9', 'status' => 'published',
            'subject_id' => $sid, 'class_id' => $cid, 'instructor_id' => $this->inst->id,
            'created_by' => $this->admin->id, 'updated_by' => $this->admin->id,
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id, 'user_id' => $this->gv->id,
            'role' => LmsCourseMember::ROLE_LECTURER, 'joined_at' => now(),
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id, 'user_id' => $this->student->id,
            'role' => LmsCourseMember::ROLE_STUDENT, 'joined_at' => now(),
        ]);
    }

    public function test_submission_creates_version_on_resubmit(): void
    {
        if (! Schema::hasTable('lms_assignment_submission_versions')) {
            $this->markTestSkipped('versions table missing — run migrate');
        }

        $asg = LmsAssignment::query()->create([
            'lms_course_id' => $this->course->id,
            'title' => 'BT S9',
            'max_score' => 10,
            'is_published' => true,
            'allow_late' => true,
            'created_by' => $this->gv->id,
        ]);

        $this->actingAs($this->student)->post(route('lms.learn.assignments.submit', [$this->course, $asg]), [
            'text_answer' => 'version 1',
        ])->assertRedirect();

        $sub = LmsAssignmentSubmission::query()->where('lms_assignment_id', $asg->id)->where('user_id', $this->student->id)->first();
        $this->assertNotNull($sub);
        $sub->update(['status' => 'graded', 'score' => 6, 'feedback' => 'ok', 'graded_at' => now()]);

        $this->actingAs($this->student)->post(route('lms.learn.assignments.submit', [$this->course, $asg]), [
            'text_answer' => 'version 2 after feedback',
        ])->assertRedirect();

        $sub->refresh();
        $this->assertSame('submitted', $sub->status);
        $this->assertGreaterThanOrEqual(2, (int) $sub->version_count);
        $this->assertGreaterThanOrEqual(2, LmsAssignmentSubmissionVersion::query()->where('lms_assignment_submission_id', $sub->id)->count());
    }

    public function test_scorm_commit_tracks_completion(): void
    {
        if (! Schema::hasTable('lms_scorm_attempts')) {
            $this->markTestSkipped('scorm_attempts missing');
        }

        $pkg = LmsScormPackage::query()->create([
            'lms_course_id' => $this->course->id,
            'title' => 'SCORM demo',
            'version' => '1.2',
            'launch_path' => 'index.html',
            'extract_path' => 'lms/scorm/demo',
            'is_published' => true,
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($this->student);
        $attempt = app(LmsScormService::class)->commit($this->course, $pkg, [
            'cmi.core.lesson_status' => 'completed',
            'cmi.core.score.raw' => '90',
            'cmi.core.score.max' => '100',
            'cmi.core.session_time' => '0000:05:00',
        ], $this->student);

        $this->assertTrue($attempt->isComplete());
        $this->assertEquals(90.0, (float) $attempt->score_raw);
    }

    public function test_scorm_upload_rejects_path_traversal_and_cleans_partial_material(): void
    {
        Storage::fake('public');
        $tmp = tempnam(sys_get_temp_dir(), 'lms-scorm-');
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('../escape.php', '<?php echo "unsafe";');
        $zip->addFromString('imsmanifest.xml', '<manifest></manifest>');
        $zip->close();

        $upload = new UploadedFile($tmp, 'unsafe-scorm.zip', 'application/zip', null, true);

        $this->actingAs($this->gv);
        try {
            app(LmsMaterialService::class)->storeScorm($this->course, $upload);
            $this->fail('Gói SCORM traversal phải bị từ chối.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('đường dẫn', $e->getMessage());
        } finally {
            @unlink($tmp);
        }

        $this->assertSame(0, LmsMaterial::withTrashed()->where('lms_course_id', $this->course->id)->count());
    }

    public function test_proctor_heartbeat_appends_events(): void
    {
        $exam = LmsExam::query()->create([
            'lms_course_id' => $this->course->id,
            'title' => 'Exam S9',
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'proctor_basic' => true,
            'require_fullscreen' => true,
            'is_published' => true,
            'created_by' => $this->gv->id,
        ]);
        $attempt = LmsExamAttempt::query()->create([
            'lms_exam_id' => $exam->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
            'status' => 'in_progress',
            'question_order' => [],
            'answers' => [],
            'proctor_events' => [],
            'blur_count' => 0,
        ]);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.exams.proctor', [$this->course, $exam, $attempt]), [
                'proctor_events' => [
                    ['type' => 'exit_fullscreen', 'detail' => 'test', 'at' => now()->toIso8601String()],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $attempt->refresh();
        $this->assertGreaterThanOrEqual(1, $attempt->blur_count);
        $this->assertNotEmpty($attempt->proctor_events);
    }

    public function test_prune_command_runs(): void
    {
        $code = Artisan::call('lms:prune-submissions', ['--dry-run' => true, '--months' => 24]);
        $this->assertSame(0, $code);
    }

    public function test_survey_templates_admin_page(): void
    {
        if (! Schema::hasTable('lms_survey_templates')) {
            $this->markTestSkipped('templates missing');
        }
        $this->actingAs($this->admin)
            ->get(route('lms.survey-templates.index'))
            ->assertOk();
    }

    /** @return array{0:int,1:int} */
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
}
