<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Jobs\SendSystemNotificationEmail;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

/**
 * Sprint GV-7 — Thông báo lớp (broadcast) cho học viên trong khóa.
 */
class EngageController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware(['auth', 'permission:lms.edit']);
    }

    public function announce(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
            // H1: tab con khi HV bấm chuông (overview|assignments|exams|attendance|…)
            'link_tab' => 'nullable|string|max:40',
        ]);

        if (! Schema::hasTable('system_notifications')) {
            return $this->backEngage($course, 'Bảng thông báo chưa sẵn sàng.', true);
        }

        $studentIds = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        // Nếu khóa gắn class_id: thêm HV theo lớp (tránh miss member)
        if ($course->class_id) {
            $classUsers = User::query()
                ->where('class_id', $course->class_id)
                ->where(function ($q) {
                    $q->where('user_type', 'student')
                        ->orWhereHas('roles', fn ($r) => $r->where('name', 'student'));
                })
                ->pluck('id');
            $studentIds = $studentIds->merge($classUsers)->unique()->values();
        }

        $allowedTabs = [
            'overview', 'lessons', 'materials', 'assignments', 'exams',
            'attendance', 'progress', 'grades', 'certificates', 'surveys', 'forum', 'chat',
        ];
        $tab = in_array($data['link_tab'] ?? '', $allowedTabs, true)
            ? $data['link_tab']
            : 'overview';
        $url = '/lms/hoc/courses/'.$course->id.'?tab='.$tab;
        $actorId = Auth::id();
        $count = 0;

        // Sprint 8 T2: insert notification + queue email (không chặn request)
        foreach ($studentIds as $uid) {
            if ((int) $uid === (int) $actorId) {
                continue;
            }
            try {
                $notification = SystemNotification::query()->create([
                    'user_id' => $uid,
                    'actor_id' => $actorId,
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'type' => 'lms_class_announce',
                    'module' => 'lms',
                    'action' => 'announce',
                    'url' => $url,
                    'meta' => [
                        'lms_course_id' => $course->id,
                        'course_title' => $course->title,
                        'tab' => $tab,
                    ],
                ]);
                SendSystemNotificationEmail::dispatch($notification->id);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('lms announce notify failed', [
                    'user_id' => $uid,
                    'course_id' => $course->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->backEngage(
            $course,
            $count > 0
                ? "Đã gửi thông báo tới {$count} học viên (chuông LMS + email queue)."
                : 'Không có học viên nào để gửi thông báo.'
        );
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

    protected function backEngage(LmsCourse $course, string $message, bool $error = false)
    {
        return redirect()
            ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=engage')
            ->with($error ? 'error' : 'success', $message);
    }
}
