<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCheckinEvent;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\CampusNetwork;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsCampus;

class AttendanceController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.attendance', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $sessions = LmsAttendanceSession::query()
            ->where('lms_course_id', $course->id)
            ->withCount('records')
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get();

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::attendance.index'
            : 'lms::learn.attendance';

        return view($view, compact('course', 'sessions'));
    }

    public function storeSession(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'session_date' => 'nullable|date',
            'mode' => 'required|in:manual,qr,self,gps',
            'open_from' => 'nullable|date',
            'open_until' => 'nullable|date|after_or_equal:open_from',
            'qr_ttl_minutes' => 'nullable|integer|min:5|max:1440',
            'require_campus_wifi' => 'nullable|boolean',
            'require_gps' => 'nullable|boolean',
            'allow_gps_bypass' => 'nullable|boolean',
            'note' => 'nullable|string|max:2000',
        ]);

        $openUntil = $data['open_until'] ?? now()->addHours(2);
        $tokenPayload = LmsAttendanceSession::initialTokenPayload(
            $data['mode'],
            isset($data['qr_ttl_minutes']) ? (int) $data['qr_ttl_minutes'] : null,
            $openUntil
        );

        $requireWifi = $request->boolean('require_campus_wifi', $data['mode'] === 'qr');
        $requireGps = $request->boolean('require_gps', $data['mode'] === 'gps');
        $gpsBypass = $request->boolean('allow_gps_bypass', $data['mode'] === 'gps');

        LmsAttendanceSession::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'session_date' => $data['session_date'] ?? now()->toDateString(),
            'mode' => $data['mode'],
            'status' => 'open',
            'require_campus_wifi' => $requireWifi,
            'require_gps' => $requireGps,
            'allow_gps_bypass' => $gpsBypass,
            'open_from' => $data['open_from'] ?? now(),
            'open_until' => $openUntil,
            'checkin_token' => $tokenPayload['checkin_token'],
            'qr_ttl_minutes' => $tokenPayload['qr_ttl_minutes'],
            'token_expires_at' => $tokenPayload['token_expires_at'],
            'note' => $data['note'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Đã tạo buổi điểm danh.');
    }

    public function show(LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureVisible($course);
        $this->ensureSession($course, $session);

        $students = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->with('user')
            ->get();

        $records = $session->records()->get()->keyBy('user_id');

        return view('lms::attendance.show', compact('course', 'session', 'students', 'records'));
    }

    public function mark(Request $request, LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureEdit($course);
        $this->ensureSession($course, $session);

        $data = $request->validate([
            'records' => 'required|array',
            'records.*.user_id' => 'required|integer|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
        ]);

        foreach ($data['records'] as $row) {
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

        return back()->with('success', 'Đã lưu điểm danh.');
    }

    public function close(LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureEdit($course);
        $this->ensureSession($course, $session);
        $session->update(['status' => 'closed']);

        return back()->with('success', 'Đã đóng buổi điểm danh.');
    }

    /**
     * Learner self / QR / GPS check-in.
     * P0: IP · P1: probe + QR TTL · P2: GPS soft/hard + bypass + event log.
     */
    public function checkin(Request $request, LmsCourse $course, LmsAttendanceSession $session)
    {
        $this->ensureVisible($course);
        $this->ensureSession($course, $session);

        $eventBase = [
            'lms_course_id' => $course->id,
            'lms_attendance_session_id' => $session->id,
            'user_id' => Auth::id(),
            'client_ip' => $request->ip(),
        ];

        if (! in_array($session->mode, ['self', 'qr', 'gps'], true)) {
            return $this->failCheckin($request, $course, $eventBase, 'manual_only', 'Ngày này chỉ điểm danh thủ công bởi giảng viên.');
        }

        $todayOk = $session->status === 'open'
            && $session->session_date
            && $session->session_date->toDateString() === now()->toDateString();

        if (! $session->isOpen() && ! $todayOk) {
            return $this->failCheckin($request, $course, $eventBase, 'closed', 'Ngày điểm danh đã đóng hoặc hết giờ.');
        }

        // P1: QR/self token + TTL
        if (in_array($session->mode, ['qr', 'self'], true) && $session->checkin_token) {
            $token = $request->input('token') ?? $request->query('token');
            if ($session->mode === 'qr' || $token) {
                if (! $token || ! hash_equals((string) $session->checkin_token, (string) $token)) {
                    return $this->failCheckin($request, $course, $eventBase, 'token', 'Mã QR / token không hợp lệ (có thể GV đã làm mới QR).');
                }
                if ($session->isTokenExpired()) {
                    return $this->failCheckin(
                        $request,
                        $course,
                        $eventBase,
                        'expired',
                        'Mã QR đã hết hạn'
                            .($session->token_expires_at ? ' lúc '.$session->token_expires_at->format('H:i d/m') : '')
                            .'. Nhờ GV hiện QR mới.'
                    );
                }
            }
        }

        // P2 GPS evaluate (luôn parse nếu client gửi)
        $gps = LmsCampus::evaluateGps(
            $request->input('lat', $request->input('checkin_lat')),
            $request->input('lng', $request->input('checkin_lng')),
            $request->input('accuracy', $request->input('checkin_accuracy_m'))
        );
        $eventBase['lat'] = $gps['lat'];
        $eventBase['lng'] = $gps['lng'];
        $eventBase['distance_m'] = $gps['distance_m'];
        $eventBase['gps_ok'] = $gps['provided'] ? $gps['ok'] : null;

        $needNetwork = $session->require_campus_wifi || $session->mode === 'qr';
        // mode gps: mạng soft (không bắt) trừ khi require_campus_wifi
        if ($session->mode === 'gps' && ! $session->require_campus_wifi) {
            $needNetwork = false;
        }

        $probeUrls = $needNetwork ? CampusNetwork::activeProbeUrls() : [];
        $probeRequired = $probeUrls !== [];

        // P1 probe gate (trước evaluate) — GPS mode hard không chặn ở đây nếu không needNetwork
        if ($needNetwork && $probeRequired && ! $request->boolean('probe_ok') && ! $request->boolean('skip_probe')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'need_probe' => true,
                    'probe_urls' => $probeUrls,
                    'need_gps' => $session->requiresGpsHard(),
                    'campus' => LmsCampus::meta(),
                    'message' => 'Cần xác minh probe LAN (Wi‑Fi trường) trước khi điểm danh.',
                ], 422);
            }

            return view('lms::learn.checkin-gate', [
                'course' => $course,
                'session' => $session,
                'probeUrls' => $probeUrls,
                'token' => $request->input('token') ?? $request->query('token'),
                'needGps' => $session->requiresGpsHard() || $session->allowsGpsBypass(),
                'campus' => LmsCampus::meta(),
            ]);
        }

        // Hard GPS missing: GET → gate UI; POST/JSON → fail + log (client sẽ retry với toạ độ)
        if ($session->requiresGpsHard() && ! $gps['provided']) {
            if ($request->isMethod('get') && ! $request->wantsJson() && ! $request->ajax()) {
                return view('lms::learn.checkin-gate', [
                    'course' => $course,
                    'session' => $session,
                    'probeUrls' => $probeUrls,
                    'token' => $request->input('token') ?? $request->query('token'),
                    'needGps' => true,
                    'campus' => LmsCampus::meta(),
                ]);
            }

            return $this->failCheckin(
                $request,
                $course,
                $eventBase,
                'gps',
                'Cần chia sẻ vị trí GPS trong bán kính trường ('.LmsCampus::radiusMeters().'m).',
                ['need_gps' => true, 'campus' => LmsCampus::meta()]
            );
        }

        $net = [
            'ok' => true,
            'note' => null,
            'ip' => $request->ip(),
            'probe_ok' => null,
            'probe_note' => null,
            'probe_required' => false,
        ];

        if ($needNetwork) {
            $probeOk = $request->boolean('probe_ok');
            $net = CampusNetwork::evaluate($request->ip(), [
                'probe_ok' => $probeRequired ? $probeOk : null,
                'skip_probe' => ! $probeRequired,
            ]);

            if (! $net['ok']) {
                // P2: GPS bypass khi mạng fail
                if ($session->allowsGpsBypass() && $gps['provided'] && $gps['ok']) {
                    $net['ok'] = true;
                    $net['note'] = ($net['note'] ?? 'Mạng không khớp').' · Được chấp nhận nhờ GPS trong campus (bypass).';
                    $net['probe_ok'] = $probeRequired ? $probeOk : null;
                } else {
                    $eventBase['network_ok'] = false;
                    $eventBase['probe_ok'] = $net['probe_ok'] ?? null;
                    $reason = (! empty($net['probe_required']) && empty($net['probe_ok'])) ? 'probe' : 'network';

                    return $this->failCheckin(
                        $request,
                        $course,
                        $eventBase,
                        $reason,
                        ($net['note'] ?? 'Không đạt kiểm tra mạng trường.')
                            .($session->allowsGpsBypass()
                                ? ' Bật định vị trong bán kính trường để thử bypass, hoặc nhờ GV điểm miệng.'
                                : ' (Hoặc nhờ giảng viên điểm miệng.)'),
                        [
                            'need_probe' => ! empty($net['probe_required']) && empty($net['probe_ok']),
                            'probe_urls' => $net['probe_urls'] ?? [],
                            'need_gps' => $session->allowsGpsBypass() || $session->requiresGpsHard(),
                            'campus' => LmsCampus::meta(),
                        ]
                    );
                }
            }
        }

        // P2 hard GPS
        if ($session->requiresGpsHard()) {
            if (! $gps['provided']) {
                return $this->failCheckin(
                    $request,
                    $course,
                    array_merge($eventBase, ['network_ok' => $net['ok'] ?? null, 'probe_ok' => $net['probe_ok'] ?? null]),
                    'gps',
                    'Cần chia sẻ vị trí GPS trong bán kính trường ('.LmsCampus::radiusMeters().'m).',
                    ['need_gps' => true, 'campus' => LmsCampus::meta()]
                );
            }
            if (! $gps['ok']) {
                return $this->failCheckin(
                    $request,
                    $course,
                    array_merge($eventBase, ['network_ok' => $net['ok'] ?? null, 'probe_ok' => $net['probe_ok'] ?? null]),
                    'gps',
                    $gps['note'].' Hãy đến khuôn viên hoặc nhờ GV điểm miệng.',
                    ['need_gps' => true, 'campus' => LmsCampus::meta(), 'gps' => $gps]
                );
            }
        }

        $method = match ($session->mode) {
            'qr' => 'qr',
            'gps' => 'gps',
            default => 'self',
        };

        $notes = array_filter([
            $net['note'] ?? null,
            $gps['provided'] ? $gps['note'] : null,
        ]);

        LmsAttendanceRecord::query()->updateOrCreate(
            [
                'lms_attendance_session_id' => $session->id,
                'user_id' => Auth::id(),
            ],
            [
                'status' => 'present',
                'method' => $method,
                'client_ip' => $net['ip'] ?? $request->ip(),
                'network_ok' => (bool) ($net['ok'] ?? true),
                'network_note' => $net['note'] ?? null,
                'probe_ok' => array_key_exists('probe_ok', $net) ? $net['probe_ok'] : null,
                'probe_note' => $net['probe_note'] ?? null,
                'gps_ok' => $gps['provided'] ? $gps['ok'] : null,
                'checkin_lat' => $gps['lat'],
                'checkin_lng' => $gps['lng'],
                'checkin_accuracy_m' => $gps['accuracy_m'],
                'distance_m' => $gps['distance_m'],
                'checked_in_at' => now(),
                'marked_by' => Auth::id(),
                'note' => $notes !== [] ? implode(' · ', $notes) : null,
            ]
        );

        LmsCheckinEvent::logAttempt(array_merge($eventBase, [
            'ok' => true,
            'reason' => 'ok',
            'network_ok' => $net['ok'] ?? true,
            'probe_ok' => $net['probe_ok'] ?? null,
            'gps_ok' => $gps['provided'] ? $gps['ok'] : null,
            'note' => implode(' · ', $notes) ?: 'Điểm danh thành công',
        ]));

        $msg = 'Đã điểm danh thành công.';
        if ($notes !== []) {
            $msg .= ' '.implode(' ', $notes);
        }

        return $this->checkinResponse($request, true, $msg, $course);
    }

    protected function failCheckin(
        Request $request,
        LmsCourse $course,
        array $eventBase,
        string $reason,
        string $message,
        array $extra = []
    ) {
        LmsCheckinEvent::logAttempt(array_merge($eventBase, [
            'ok' => false,
            'reason' => $reason,
            'note' => $message,
            'network_ok' => $eventBase['network_ok'] ?? null,
            'probe_ok' => $eventBase['probe_ok'] ?? null,
            'gps_ok' => $eventBase['gps_ok'] ?? null,
        ]));

        return $this->checkinResponse($request, false, $message, $course, $extra);
    }

    protected function checkinResponse(
        Request $request,
        bool $ok,
        string $message,
        ?LmsCourse $course = null,
        array $extra = []
    ) {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'ok' => $ok,
                'message' => $message,
            ], $extra), $ok ? 200 : 422);
        }

        if ($course && ($request->isMethod('get') || $request->query('token'))) {
            $url = route('lms.learn.courses.show', $course).'?tab=attendance';

            return redirect()->to($url)->with($ok ? 'success' : 'error', $message);
        }

        return $ok
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureEdit(LmsCourse $course): void
    {
        if (! Auth::user()?->can('lms.edit') && ! LmsAccess::usesAdminShell()) {
            $isLecturer = $course->members()
                ->where('user_id', Auth::id())
                ->whereIn('role', [LmsCourseMember::ROLE_LECTURER, 'assistant'])
                ->exists();
            if (! $isLecturer && ! (Auth::user()?->instructor_id && (int) Auth::user()->instructor_id === (int) $course->instructor_id)) {
                abort(403);
            }
        }
        $this->ensureVisible($course);
    }

    protected function ensureSession(LmsCourse $course, LmsAttendanceSession $session): void
    {
        if ((int) $session->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }
}
