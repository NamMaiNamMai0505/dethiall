<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\CampusNetworkSetting;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * P1 feature: check-in probe gate, QR TTL reject, rotate token.
 */
class CampusNetworkP1Test extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $teacher;

    protected LmsCourse $course;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['lms.index', 'lms.show', 'lms.edit', 'campus-network.index', 'campus-network.create'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->student = User::factory()->create([
            'email' => 'hv-p1@test.local',
            'user_type' => 'student',
            'status' => 1,
        ]);
        $this->student->givePermissionTo(['lms.index', 'lms.show']);

        $this->teacher = User::factory()->create([
            'email' => 'gv-p1@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->teacher->givePermissionTo(['lms.index', 'lms.show', 'lms.edit']);

        $inst = Instructor::query()->create([
            'name' => 'GV P1',
            'code' => 'GV-P1',
            'email' => 'gvp1@test.local',
            'status' => 'active',
            'created_by' => $this->teacher->id,
            'updated_by' => $this->teacher->id,
        ]);
        $this->teacher->forceFill(['instructor_id' => $inst->id])->save();

        [$subjectId, $classId] = $this->seedSubjectAndClass('P1', $inst->id, $this->teacher->id);

        $this->course = LmsCourse::query()->create([
            'code' => 'LMS-P1-001',
            'title' => 'Khóa P1',
            'status' => 'published',
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'instructor_id' => $inst->id,
            'created_by' => $this->teacher->id,
            'updated_by' => $this->teacher->id,
        ]);

        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->teacher->id,
            'role' => LmsCourseMember::ROLE_LECTURER ?? 'lecturer',
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => LmsCourseMember::ROLE_STUDENT ?? 'student',
        ]);
    }

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

    protected function makeSession(array $extra = []): LmsAttendanceSession
    {
        $tokenPayload = LmsAttendanceSession::initialTokenPayload(
            $extra['mode'] ?? 'qr',
            $extra['qr_ttl_minutes'] ?? 60,
            $extra['open_until'] ?? now()->endOfDay()
        );

        return LmsAttendanceSession::query()->create(array_merge([
            'lms_course_id' => $this->course->id,
            'title' => 'Buổi P1',
            'session_date' => now()->toDateString(),
            'mode' => 'qr',
            'status' => 'open',
            'require_campus_wifi' => true,
            'open_from' => now()->startOfDay(),
            'open_until' => now()->endOfDay(),
            'checkin_token' => $tokenPayload['checkin_token'],
            'qr_ttl_minutes' => $tokenPayload['qr_ttl_minutes'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'created_by' => $this->teacher->id,
        ], $extra, [
            // keep token fields from payload unless overridden
            'checkin_token' => $extra['checkin_token'] ?? $tokenPayload['checkin_token'],
            'qr_ttl_minutes' => $extra['qr_ttl_minutes'] ?? $tokenPayload['qr_ttl_minutes'],
            'token_expires_at' => $extra['token_expires_at'] ?? $tokenPayload['token_expires_at'],
        ]));
    }

    public function test_checkin_json_asks_for_probe_when_configured(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Probe LAN',
            'ip_cidrs' => '127.0.0.0/8,10.0.0.0/8,192.168.0.0/16',
            'probe_url' => 'http://10.9.9.9/ping',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $session = $this->makeSession();

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
            ])
            ->assertStatus(422)
            ->assertJsonPath('need_probe', true)
            ->assertJsonFragment(['http://10.9.9.9/ping']);
    }

    public function test_checkin_succeeds_with_probe_ok_and_matching_ip(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Probe LAN',
            'ip_cidrs' => '127.0.0.0/8',
            'probe_url' => 'http://10.9.9.9/ping',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $session = $this->makeSession();

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
                'probe_ok' => true,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('lms_attendance_records', [
            'lms_attendance_session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => 'present',
            'probe_ok' => 1,
        ]);
    }

    public function test_checkin_rejects_expired_qr_token(): void
    {
        CampusNetworkSetting::query()->delete();

        $session = $this->makeSession([
            'token_expires_at' => now()->subMinutes(5),
            'qr_ttl_minutes' => 10,
        ]);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $body = $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
            ])
            ->json('message');

        $this->assertStringContainsString('hết hạn', mb_strtolower($body));
    }

    public function test_rotate_token_invalidates_old(): void
    {
        $session = $this->makeSession();
        $old = $session->checkin_token;

        $this->actingAs($this->teacher)
            ->post(route('lms.teach.attendance.rotate-token', [$this->course, $session]), [
                'qr_ttl_minutes' => 45,
            ])
            ->assertRedirect();

        $session->refresh();
        $this->assertNotSame($old, $session->checkin_token);
        $this->assertSame(45, (int) $session->qr_ttl_minutes);
        $this->assertNotNull($session->token_expires_at);
        $this->assertTrue($session->token_expires_at->greaterThan(now()->addMinutes(40)));
    }

    public function test_get_checkin_shows_probe_gate_view(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Probe LAN',
            'ip_cidrs' => '127.0.0.0/8',
            'probe_url' => 'http://10.9.9.9/ping',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $session = $this->makeSession();

        $this->actingAs($this->student)
            ->get(route('lms.learn.attendance.checkin', [$this->course, $session, 'token' => $session->checkin_token]))
            ->assertOk()
            ->assertSee('Xác minh điểm danh', false)
            ->assertSee('http://10.9.9.9/ping', false);
    }

    public function test_diagnose_includes_probe_urls(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Probe LAN',
            'ip_cidrs' => '10.0.0.0/8',
            'probe_url' => 'http://10.1.1.1/x',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $admin = User::factory()->create(['email' => 'admin-p1@test.local', 'status' => 1]);
        $admin->givePermissionTo(['campus-network.index']);

        $this->actingAs($admin)
            ->getJson(route('campus-network.test-ip', ['json' => 1]))
            ->assertOk()
            ->assertJsonPath('probe_required', true)
            ->assertJsonFragment(['http://10.1.1.1/x']);
    }
}
