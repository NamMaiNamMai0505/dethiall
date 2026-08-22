<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;

/** Ghi danh thủ công cho lớp học phần độc lập (Sprint 4). */
class MemberController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.members', ApplicationRegistry::ACTION_VIEW));
        $this->middleware(ApplicationGate::middleware('lms.members', ApplicationRegistry::ACTION_EDIT))->except(['index', 'show']);
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensure($course);
        $data = $request->validate([
            'email' => 'nullable|email',
            'user_id' => 'nullable|integer|exists:users,id',
            'role' => 'nullable|in:student,lecturer,assistant',
        ]);

        $user = null;
        if (! empty($data['user_id'])) {
            $user = User::find($data['user_id']);
        } elseif (! empty($data['email'])) {
            $user = User::query()->where('email', $data['email'])->first();
        }

        if (! $user) {
            return back()->with('error', 'Không tìm thấy người dùng.');
        }

        LmsCourseMember::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'user_id' => $user->id],
            [
                'role' => $data['role'] ?? LmsCourseMember::ROLE_STUDENT,
                'source' => 'manual',
                'status' => LmsCourseMember::STATUS_ACTIVE,
                'joined_at' => now(),
                'synced_at' => now(),
                'left_at' => null,
            ]
        );

        return back()->with('success', 'Đã ghi danh '.$user->name);
    }

    public function destroy(LmsCourse $course, LmsCourseMember $member)
    {
        $this->ensure($course);
        if ((int) $member->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $member->delete();

        return back()->with('success', 'Đã gỡ thành viên.');
    }

    protected function ensure(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
        if (! Auth::user()?->can('lms.edit')) {
            abort(403);
        }
    }
}
