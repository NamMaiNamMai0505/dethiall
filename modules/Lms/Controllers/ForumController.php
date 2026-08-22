<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsForumReply;
use Modules\Lms\Models\LmsForumTopic;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class ForumController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.forum', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $topics = LmsForumTopic::query()
            ->where('lms_course_id', $course->id)
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('id')
            ->paginate(20);

        return $this->viewFor('lms::forum.index', 'lms::learn.forum-index', compact('course', 'topics'));
    }

    public function storeTopic(Request $request, LmsCourse $course)
    {
        $this->ensureVisible($course);
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:10000',
        ]);

        LmsForumTopic::create([
            'lms_course_id' => $course->id,
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'body' => $data['body'],
            'last_reply_at' => now(),
        ]);

        if (! LmsAccess::usesAdminShell()) {
            return redirect()
                ->to(route('lms.learn.courses.show', $course).'?tab=forum')
                ->with('success', 'Đã tạo chủ đề thảo luận.');
        }

        return back()->with('success', 'Đã tạo chủ đề thảo luận.');
    }

    public function show(LmsCourse $course, LmsForumTopic $topic)
    {
        $this->ensureVisible($course);
        $this->ensureTopic($course, $topic);
        $topic->load(['author', 'replies.author']);

        return $this->viewFor('lms::forum.show', 'lms::learn.forum-show', compact('course', 'topic'));
    }

    public function storeReply(Request $request, LmsCourse $course, LmsForumTopic $topic)
    {
        $this->ensureVisible($course);
        $this->ensureTopic($course, $topic);
        if ($topic->is_locked && ! $this->canModerate($course)) {
            return back()->with('error', 'Chủ đề đã khoá.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:10000',
        ]);

        LmsForumReply::create([
            'lms_forum_topic_id' => $topic->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);

        $topic->update([
            'replies_count' => $topic->replies()->count(),
            'last_reply_at' => now(),
        ]);

        if (! LmsAccess::usesAdminShell()) {
            return redirect()
                ->route('lms.learn.forum.show', [$course, $topic])
                ->with('success', 'Đã gửi phản hồi.');
        }

        return back()->with('success', 'Đã gửi phản hồi.');
    }

    /** GV-7: ghim / bỏ ghim */
    public function togglePin(LmsCourse $course, LmsForumTopic $topic)
    {
        $this->ensureVisible($course);
        $this->ensureTopic($course, $topic);
        if (! $this->canModerate($course)) {
            abort(403, 'Chỉ giảng viên / quản trị được ghim chủ đề.');
        }

        $topic->update(['is_pinned' => ! (bool) $topic->is_pinned]);

        return $this->backForum(
            $course,
            $topic,
            $topic->is_pinned ? 'Đã ghim chủ đề.' : 'Đã bỏ ghim chủ đề.'
        );
    }

    /** GV-7: khóa / mở chủ đề */
    public function toggleLock(LmsCourse $course, LmsForumTopic $topic)
    {
        $this->ensureVisible($course);
        $this->ensureTopic($course, $topic);
        if (! $this->canModerate($course)) {
            abort(403, 'Chỉ giảng viên / quản trị được khóa chủ đề.');
        }

        $topic->update(['is_locked' => ! (bool) $topic->is_locked]);

        return $this->backForum(
            $course,
            $topic,
            $topic->is_locked ? 'Đã khóa chủ đề.' : 'Đã mở lại chủ đề.'
        );
    }

    protected function viewFor(string $admin, string $learn, array $data)
    {
        $view = LmsAccess::usesAdminShell() ? $admin : $learn;

        return view($view, $data);
    }

    protected function canModerate(LmsCourse $course): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if (LmsAccess::usesAdminShell($user)) {
            return true;
        }

        return LmsAccess::canTeachCourse($course, $user);
    }

    protected function backForum(LmsCourse $course, LmsForumTopic $topic, string $message)
    {
        if (! LmsAccess::usesAdminShell()) {
            $url = route('lms.learn.courses.show', $course).'?mode=teach&tab=forum';
            if (request()->boolean('stay') || request()->query('from') === 'show') {
                $url = route('lms.learn.forum.show', [$course, $topic]);
            }

            return redirect()->to($url)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureTopic(LmsCourse $course, LmsForumTopic $topic): void
    {
        if ((int) $topic->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }
}
