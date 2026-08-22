<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

/**
 * Sprint GV-5 — Điểm danh lớp trên portal GV (mode teach).
 */
class AttendanceController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware(['auth', 'permission:lms.edit']);
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'session_date' => 'nullable|date',
            'mode' => 'required|in:manual,self,qr,gps',
            'require_campus_wifi' => 'nullable|boolean',
            'require_gps' => 'nullable|boolean',
            'allow_gps_bypass' => 'nullable|boolean',
            'open_until' => 'nullable|date',
            'qr_ttl_minutes' => 'nullable|integer|min:5|max:1440',
            'note' => 'nullable|string|max:2000',
        ]);

        $date = $data['session_date'] ?? now()->toDateString();
        $openUntil = $data['open_until'] ?? now()->endOfDay()->addDays(1);

        // QR mặc định bắt Wi‑Fi; GPS mặc định bắt toạ độ + cho bypass mạng
        $requireWifi = $request->boolean('require_campus_wifi', $data['mode'] === 'qr');
        $requireGps = $request->boolean('require_gps', $data['mode'] === 'gps');
        $gpsBypass = $request->boolean('allow_gps_bypass', $data['mode'] === 'gps');

        $tokenPayload = LmsAttendanceSession::initialTokenPayload(
            $data['mode'],
            isset($data['qr_ttl_minutes']) ? (int) $data['qr_ttl_minutes'] : null,
            $openUntil
        );

        $session = LmsAttendanceSession::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'session_date' => $date,
            'mode' => $data['mode'],
            'require_campus_wifi' => $requireWifi,
            'require_gps' => $requireGps,
            'allow_gps_bypass' => $gpsBypass,
            'status' => 'open',
            'open_from' => now()->startOfDay(),
            'open_until' => $openUntil,
            'checkin_token' => $tokenPayload['checkin_token'],
            'qr_ttl_minutes' => $tokenPayload['qr_ttl_minutes'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'note' => $data['note'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return $this->backAttend($course, $session->id, 'Đã tạo ngày điểm danh.');
    }

    /** P1: làm mới token QR (vô hiệu link cũ + TTL mới). */
    public function rotateToken(Request $request, LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureTeach($course);
        $this->ensureSession($course, $session);

        if (! in_array($session->mode, ['self', 'qr'], true)) {
            return $this->backAttend($course, $session->id, 'Chỉ session QR/self mới có token.', true);
        }

        $data = $request->validate([
            'qr_ttl_minutes' => 'nullable|integer|min:5|max:1440',
        ]);

        $result = $session->rotateToken(
            isset($data['qr_ttl_minutes']) ? (int) $data['qr_ttl_minutes'] : null
        );

        $until = $result['token_expires_at']?->format('H:i d/m') ?? '—';

        return $this->backAttend(
            $course,
            $session->id,
            'Đã làm mới mã QR (TTL '.$result['qr_ttl_minutes'].' phút, hết hạn '.$until.'). Link cũ không còn dùng được.'
        );
    }

    public function close(LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureTeach($course);
        $this->ensureSession($course, $session);
        $session->update(['status' => 'closed']);

        return $this->backAttend($course, $session->id, 'Đã đóng điểm danh ngày này.');
    }

    public function reopen(LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureTeach($course);
        $this->ensureSession($course, $session);
        $session->update([
            'status' => 'open',
            'open_until' => now()->endOfDay()->addDays(1),
        ]);

        return $this->backAttend($course, $session->id, 'Đã mở lại điểm danh.');
    }

    public function mark(Request $request, LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureTeach($course);
        $this->ensureSession($course, $session);

        $data = $request->validate([
            'records' => 'required|array',
            'records.*.user_id' => 'required|integer|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
        ]);

        // Chỉ cho phép HV thuộc khóa
        $studentIds = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->pluck('user_id')
            ->all();

        foreach ($data['records'] as $row) {
            if (! in_array((int) $row['user_id'], array_map('intval', $studentIds), true)) {
                continue;
            }
            LmsAttendanceRecord::query()->updateOrCreate(
                [
                    'lms_attendance_session_id' => $session->id,
                    'user_id' => $row['user_id'],
                ],
                [
                    'status' => $row['status'],
                    'method' => 'manual',
                    'checked_in_at' => in_array($row['status'], ['present', 'late'], true) ? now() : null,
                    'marked_by' => Auth::id(),
                ]
            );
        }

        return $this->backAttend($course, $session->id, 'Đã lưu điểm danh lớp.');
    }

    public function markAllPresent(LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureTeach($course);
        $this->ensureSession($course, $session);

        $students = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->pluck('user_id');

        foreach ($students as $uid) {
            LmsAttendanceRecord::query()->updateOrCreate(
                [
                    'lms_attendance_session_id' => $session->id,
                    'user_id' => $uid,
                ],
                [
                    'status' => 'present',
                    'method' => 'manual',
                    'checked_in_at' => now(),
                    'marked_by' => Auth::id(),
                ]
            );
        }

        return $this->backAttend($course, $session->id, 'Đã điểm có mặt cả lớp.');
    }

    protected function ensureTeach(LmsCourse $course): void
    {
        if (! LmsAccess::canTeachCourse($course)) {
            abort(403, 'Bạn không phụ trách khóa học này.');
        }
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureSession(LmsCourse $course, LmsAttendanceSession $session): void
    {
        if ((int) $session->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function backAttend(LmsCourse $course, ?int $sessionId, string $message, bool $error = false)
    {
        $url = route('lms.learn.courses.show', $course).'?mode=teach&tab=attend';
        if ($sessionId) {
            $url .= '&session='.$sessionId;
        }

        return redirect()->to($url)->with($error ? 'error' : 'success', $message);
    }
}
