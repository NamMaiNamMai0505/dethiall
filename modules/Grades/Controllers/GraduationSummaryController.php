<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Grades\Models\GraduationResult;
use Modules\Grades\Models\GraduationSession;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeActor;
use Modules\Grades\Services\GraduationCalculator;

class GraduationSummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $sessions = GraduationSession::query()->orderByDesc('academic_year')->orderByDesc('id')->paginate(15);
        $canEdit = GradeActor::canEditOperational($user);
        $canApprove = GradeActor::canApprove($user);

        return view('grades::academic.summary.index', compact('sessions', 'canEdit', 'canApprove'));
    }

    public function storeSession(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'academic_year' => 'required|string|max:20|exists:academic_years,code',
            'title' => 'required|string|max:255',
            'session_type' => 'required|in:year_end,graduation',
            'note' => 'nullable|string|max:2000',
        ]);

        $session = GraduationSession::query()->create([
            ...$data,
            'status' => GraduationSession::STATUS_OPEN,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('grades.academic.summary.show', $session)
            ->with('success', 'Đã tạo đợt tổng kết / xét TN.');
    }

    public function show(Request $request, GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $filter = $request->query('status');
        $q = $session->results()->with(['student', 'classModel'])->orderByRaw('rank_order is null')->orderBy('rank_order');
        if ($filter) {
            $q->where('result_status', $filter);
        }

        // GV: chỉ lớp mình
        if (GradeActor::isInstructorScoped($user) && $user->instructor_id) {
            $classIds = GradeAccess::teachingPairs($user)->pluck('class_id')->unique()->all();
            $q->whereIn('class_id', $classIds ?: [0]);
        }

        $results = $q->paginate(40)->withQueryString();
        $canEdit = GradeActor::canEditOperational($user);
        $canApprove = GradeActor::canApprove($user);
        $statuses = GraduationResult::STATUSES;

        return view('grades::academic.summary.show', compact(
            'session', 'results', 'filter', 'canEdit', 'canApprove', 'statuses'
        ));
    }

    public function calculate(GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $stats = app(\Modules\Grades\Services\GraduationCalculator::class)->calculate($session, $user);

        return back()->with(
            'success',
            "Đã tính kết quả cho {$stats['count']} học viên ({$stats['with_score']} có điểm). Xếp hạng theo GPA; xét RL/kỷ luật nếu có."
        );
    }

    public function updateResult(Request $request, GraduationResult $result)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'result_status' => 'required|in:'.implode(',', array_keys(GraduationResult::STATUSES)),
            'gpa' => 'nullable|numeric|min:0|max:'.\Modules\Grades\Support\GradeSettings::maxScore(),
            'exam_score' => 'nullable|numeric|min:0|max:'.\Modules\Grades\Support\GradeSettings::maxScore(),
            'retake_registered' => 'nullable|boolean',
            'rank_order' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);
        $data['retake_registered'] = $request->boolean('retake_registered');
        $data['updated_by'] = $user->id;
        $result->update($data);

        return back()->with('success', 'Đã cập nhật kết quả học viên.');
    }

    public function reorder(Request $request, GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer|min:1',
        ]);

        foreach ($data['orders'] as $id => $order) {
            GraduationResult::query()
                ->where('session_id', $session->id)
                ->whereKey($id)
                ->update(['rank_order' => $order, 'updated_by' => $user->id]);
        }

        return back()->with('success', 'Đã xếp lại thứ tự danh sách.');
    }

    public function submitApprove(GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);
        $session->update(['status' => GraduationSession::STATUS_PENDING]);

        return back()->with('success', 'Đã gửi Ban giám hiệu phê duyệt.');
    }

    public function approve(GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canApprove($user) || GradeActor::canFull($user), 403);
        $session->update([
            'status' => GraduationSession::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Đã phê duyệt đợt xét tốt nghiệp.');
    }

    public function printBook(GraduationSession $session)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canPrint($user), 403);

        $q = $session->results()->with(['student', 'classModel'])->orderBy('rank_order');
        if (GradeActor::isInstructorScoped($user) && $user->instructor_id) {
            $classIds = GradeAccess::teachingPairs($user)->pluck('class_id')->unique()->all();
            $q->whereIn('class_id', $classIds ?: [0]);
        }
        $results = $q->get();

        return view('grades::academic.summary.print', compact('session', 'results'));
    }
}
