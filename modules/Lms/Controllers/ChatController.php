<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsChatMessage;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class ChatController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.chat', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $members = $this->membersForChat($course);
        $canModerate = $this->canModerate($course);
        $messages = $this->queryConversation($course, null)
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values();

        return $this->viewFor('lms::chat.index', 'lms::learn.chat', compact('course', 'messages', 'members', 'canModerate'));
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureVisible($course);

        $canModerate = $this->canModerate($course);
        if ($course->chat_locked && ! $canModerate) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Chat đang bị khóa.'], 403);
            }

            return back()->with('error', 'Chat đang bị khóa bởi giảng viên / quản trị.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:2000',
            'recipient_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $recipientId = ! empty($data['recipient_user_id']) ? (int) $data['recipient_user_id'] : null;
        if ($recipientId === (int) Auth::id()) {
            return $this->fail($request, 'Không thể chat với chính mình.', 422);
        }
        if ($recipientId && ! $this->canMessageUser($course, $recipientId)) {
            return $this->fail($request, 'Người nhận không thuộc khóa học.', 403);
        }

        $msg = LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => Auth::id(),
            'recipient_user_id' => $recipientId,
            'body' => trim($data['body']),
        ]);
        $msg->load('author:id,name');

        $payload = $this->mapMessage($msg, $course);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $payload]);
        }

        return back()->with('success', 'Đã gửi tin nhắn.');
    }

    public function poll(LmsCourse $course, Request $request)
    {
        $this->ensureVisible($course);
        $afterId = (int) $request->query('after_id', 0);
        $recipientId = $request->filled('recipient_user_id')
            ? (int) $request->query('recipient_user_id')
            : null;
        if ($recipientId === (int) Auth::id()) {
            return response()->json(['message' => 'Không thể chat với chính mình.'], 422);
        }
        if ($recipientId && ! $this->canMessageUser($course, $recipientId)) {
            return response()->json(['message' => 'Người nhận không thuộc khóa học.'], 403);
        }

        $messages = $this->queryConversation($course, $recipientId)
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn ($m) => $this->mapMessage($m, $course));

        return response()->json([
            'messages' => $messages,
            'chat_locked' => (bool) $course->chat_locked,
            'can_moderate' => $this->canModerate($course),
            'can_send' => ! $course->chat_locked || $this->canModerate($course),
        ]);
    }

    public function history(LmsCourse $course, Request $request)
    {
        $this->ensureVisible($course);
        $recipientId = $request->filled('recipient_user_id')
            ? (int) $request->query('recipient_user_id')
            : null;
        if ($recipientId === (int) Auth::id()) {
            return response()->json(['message' => 'Không thể chat với chính mình.'], 422);
        }
        if ($recipientId && ! $this->canMessageUser($course, $recipientId)) {
            return response()->json(['message' => 'Người nhận không thuộc khóa học.'], 403);
        }

        $messages = $this->queryConversation($course, $recipientId)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => $this->mapMessage($m, $course));

        return response()->json([
            'messages' => $messages,
            'can_moderate' => $this->canModerate($course),
            'chat_locked' => (bool) $course->chat_locked,
            'can_send' => ! $course->chat_locked || $this->canModerate($course),
        ]);
    }

    public function toggleLock(LmsCourse $course)
    {
        $this->ensureVisible($course);
        if (! $this->canModerate($course)) {
            abort(403, 'Chỉ giảng viên / quản trị được khóa chat.');
        }

        $course->update(['chat_locked' => ! (bool) $course->chat_locked]);

        return back()->with(
            'success',
            $course->chat_locked ? 'Đã khóa chat khóa học.' : 'Đã mở lại chat khóa học.'
        );
    }

    /** GV-7: xóa tin nhắn (moderator) */
    public function destroy(Request $request, LmsCourse $course, LmsChatMessage $message)
    {
        $this->ensureVisible($course);
        if (! $this->canModerate($course)) {
            return $this->fail($request, 'Chỉ giảng viên / quản trị được xóa tin.', 403);
        }
        if ((int) $message->lms_course_id !== (int) $course->id) {
            abort(404);
        }

        $id = $message->id;
        $message->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'id' => $id]);
        }

        return back()->with('success', 'Đã xóa tin nhắn.');
    }

    protected function queryConversation(LmsCourse $course, ?int $recipientId)
    {
        $me = (int) Auth::id();

        return LmsChatMessage::query()
            ->where('lms_course_id', $course->id)
            ->with('author:id,name')
            ->when(
                $recipientId === null,
                // Chat chung khóa
                fn ($q) => $q->whereNull('recipient_user_id'),
                // DM 2 chiều
                fn ($q) => $q->where(function ($inner) use ($me, $recipientId) {
                    $inner->where(function ($a) use ($me, $recipientId) {
                        $a->where('user_id', $me)->where('recipient_user_id', $recipientId);
                    })->orWhere(function ($b) use ($me, $recipientId) {
                        $b->where('user_id', $recipientId)->where('recipient_user_id', $me);
                    });
                })
            );
    }

    protected function mapMessage(LmsChatMessage $m, ?LmsCourse $course = null): array
    {
        $canDelete = false;
        if ($course) {
            $canDelete = $this->canModerate($course);
        }

        return [
            'id' => $m->id,
            'body' => $m->body,
            'user' => $m->author->name ?? 'User',
            'user_id' => (int) $m->user_id,
            'recipient_user_id' => $m->recipient_user_id ? (int) $m->recipient_user_id : null,
            'mine' => (int) $m->user_id === (int) Auth::id(),
            'at' => $m->created_at?->format('H:i d/m'),
            'can_delete' => $canDelete,
        ];
    }

    protected function membersForChat(LmsCourse $course)
    {
        return LmsCourseMember::query()
            ->where('lms_course_id', $course->id)
            ->where('status', LmsCourseMember::STATUS_ACTIVE)
            ->with('user:id,name,email')
            ->where('user_id', '!=', Auth::id())
            ->orderBy('role')
            ->get()
            ->filter(fn ($m) => $m->user)
            ->values();
    }

    protected function canMessageUser(LmsCourse $course, int $userId): bool
    {
        return LmsCourseMember::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', LmsCourseMember::STATUS_ACTIVE)
            ->exists();
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

    protected function fail(Request $request, string $message, int $code = 400)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], $code);
        }

        return back()->with('error', $message);
    }

    protected function viewFor(string $admin, string $learn, array $data)
    {
        return view(LmsAccess::usesAdminShell() ? $admin : $learn, $data);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }
}
