<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\CampusNetworkSetting;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCheckinEvent;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Support\LmsCampus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * P2: GPS hard/soft/bypass + event log + stats.
 */
class CampusNetworkP2Test extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $teacher;

    protected User $admin;

    protected LmsCourse $course;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['lms.index', 'lms.show', 'lms.edit', 'campus-network.index'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->student = User::factory()->create([
            'email' => 'hv-p2@test.local',
            'user_type' => 'student',
            'status' => 1,
        ]);
        $this->student->givePermissionTo(['lms.index', 'lms.show']);

        $this->teacher = User::factory()->create([
            'email' => 'gv-p2@test.local',
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $this->teacher->givePermissionTo(['lms.index', 'lms.show', 'lms.edit']);

        $this->admin = User::factory()->create([
            'email' => 'admin-p2@test.local',
            'status' => 1,
        ]);
        $this->admin->givePermissionTo(['campus-network.index']);

        $inst = Instructor::query()->create([
            'name' => 'GV P2',
            'code' => 'GV-P2',
            'email' => 'gvp2@test.local',
            'status' => 'active',
            'created_by' => $this->teacher->id,
            'updated_by' => $this->teacher->id,
        ]);
        $this->teacher->forceFill(['instructor_id' => $inst->id])->save();

        [$subjectId, $classId] = $this->seedSubjectAndClass('P2', $inst->id, $this->teacher->id);

        $this->course = LmsCourse::query()->create([
            'code' => 'LMS-P2-001',
            'title' => 'Khóa P2',
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
            'role' => LmsCourseMember::ROLE_LECTURER,
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'role' => LmsCourseMember::ROLE_STUDENT,
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
            $classCols['classroom'] = 'P2';
        }
        if (Schema::hasColumn('classes', 'classroom_id')) {
            $classCols['classroom_id'] = null;
        }
        $classId = DB::table('classes')->insertGetId($classCols);

        return [(int) $subjectId, (int) $classId];
    }

    protected function makeSession(array $extra = []): LmsAttendanceSession
    {
        $mode = $extra['mode'] ?? 'gps';
        $tokenPayload = LmsAttendanceSession::initialTokenPayload($mode, 60, now()->endOfDay());

        return LmsAttendanceSession::query()->create(array_merge([
            'lms_course_id' => $this->course->id,
            'title' => 'Buổi P2',
            'session_date' => now()->toDateString(),
            'mode' => $mode,
            'status' => 'open',
            'require_campus_wifi' => false,
            'require_gps' => $mode === 'gps',
            'allow_gps_bypass' => $mode === 'gps',
            'open_from' => now()->startOfDay(),
            'open_until' => now()->endOfDay(),
            'checkin_token' => $tokenPayload['checkin_token'],
            'qr_ttl_minutes' => $tokenPayload['qr_ttl_minutes'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'created_by' => $this->teacher->id,
        ], $extra));
    }

    public function test_gps_mode_requires_coordinates(): void
    {
        CampusNetworkSetting::query()->delete();
        $session = $this->makeSession(['mode' => 'gps']);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]))
            ->assertStatus(422)
            ->assertJsonPath('need_gps', true);

        $this->assertDatabaseHas('lms_checkin_events', [
            'user_id' => $this->student->id,
            'ok' => 0,
            'reason' => 'gps',
        ]);
    }

    public function test_gps_mode_accepts_within_radius(): void
    {
        CampusNetworkSetting::query()->delete();
        $session = $this->makeSession(['mode' => 'gps']);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'lat' => LmsCampus::LAT,
                'lng' => LmsCampus::LNG,
                'accuracy' => 15,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('lms_attendance_records', [
            'lms_attendance_session_id' => $session->id,
            'user_id' => $this->student->id,
            'method' => 'gps',
            'gps_ok' => 1,
        ]);

        $this->assertDatabaseHas('lms_checkin_events', [
            'user_id' => $this->student->id,
            'ok' => 1,
            'reason' => 'ok',
        ]);
    }

    public function test_gps_mode_rejects_outside(): void
    {
        CampusNetworkSetting::query()->delete();
        $session = $this->makeSession(['mode' => 'gps']);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'lat' => 21.0,
                'lng' => 105.8,
                'accuracy' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertSame(0, LmsCheckinEvent::query()->where('ok', true)->count());
        $this->assertTrue(LmsCheckinEvent::query()->where('reason', 'gps')->exists());
    }

    public function test_gps_bypass_allows_when_network_fails(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Only 10.x',
            'ip_cidrs' => '10.0.0.0/8',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $session = $this->makeSession([
            'mode' => 'qr',
            'require_campus_wifi' => true,
            'require_gps' => false,
            'allow_gps_bypass' => true,
        ]);

        // Default test IP is usually 127.0.0.1 — not in 10.0.0.0/8
        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
                'lat' => LmsCampus::LAT,
                'lng' => LmsCampus::LNG,
                'accuracy' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('lms_attendance_records', [
            'user_id' => $this->student->id,
            'gps_ok' => 1,
        ]);
    }

    public function test_network_fail_without_bypass_logs_event(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Only 10.x',
            'ip_cidrs' => '10.0.0.0/8',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $session = $this->makeSession([
            'mode' => 'qr',
            'require_campus_wifi' => true,
            'require_gps' => false,
            'allow_gps_bypass' => false,
        ]);

        $this->actingAs($this->student)
            ->postJson(route('lms.learn.attendance.checkin', [$this->course, $session]), [
                'token' => $session->checkin_token,
            ])
            ->assertStatus(422);

        $this->assertTrue(LmsCheckinEvent::query()->where('reason', 'network')->where('ok', false)->exists());
    }

    public function test_stats_page_and_json(): void
    {
        LmsCheckinEvent::logAttempt([
            'lms_course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'ok' => false,
            'reason' => 'network',
            'client_ip' => '1.2.3.4',
            'note' => 'test fail',
        ]);

        $this->actingAs($this->admin)
            ->get(route('campus-network.stats'))
            ->assertOk()
            ->assertSee('THỐNG KÊ CHECK-IN', false);

        $this->actingAs($this->admin)
            ->getJson(route('campus-network.stats', ['json' => 1]))
            ->assertOk()
            ->assertJsonPath('stats.events.fail', 1)
            ->assertJsonStructure(['stats', 'campus']);
    }

    public function test_permissions_policy_allows_geolocation_self(): void
    {
        $response = $this->get('/login');
        $policy = $response->headers->get('Permissions-Policy') ?? '';
        $this->assertStringContainsString('geolocation=(self)', $policy);
        $this->assertStringNotContainsString('geolocation=()', $policy);
    }
}
