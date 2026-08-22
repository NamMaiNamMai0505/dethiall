<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use Modules\Grades\Models\GradeChangeRequest;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeBookService;
use Modules\Grades\Services\GradeExportService;
use Modules\Subject\Models\Subject;

class GradeBookController extends Controller
{
    public function __construct(private readonly GradeBookService $service)
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $classId = $request->integer('class_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;
        $q = GradeAccess::booksQuery($user)->orderByDesc('id');
        if ($classId) {
            abort_unless(GradeAccess::canAccessClass($user, $classId), 403);
            $q->where('class_id', $classId);
        }
        if ($subjectId) {
            abort_unless(GradeAccess::canAccessSubject($user, $subjectId), 403);
            $q->where('subject_id', $subjectId);
        }

        $books = $q->paginate(20)->withQueryString();
        $subjects = GradeAccess::accessibleSubjects($user);
        $classes = $subjectId
            ? GradeAccess::classesForSubject($user, $subjectId)
            : collect();
        $currentClass = $classId ? ClassModel::query()->find($classId) : null;
        $currentSubject = $subjectId ? Subject::query()->find($subjectId) : null;

        return view('grades::books.index', compact(
            'books',
            'classes',
            'subjects',
            'classId',
            'subjectId',
            'currentClass',
            'currentSubject'
        ));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        // Wizard: ưu tiên prefill subject rồi class (GV: Môn→Lớp)
        $prefillSubjectId = (int) ($request->integer('subject_id') ?: old('subject_id') ?: 0) ?: null;
        $prefillClassId = (int) ($request->integer('class_id') ?: old('class_id') ?: 0) ?: null;

        if ($prefillSubjectId) {
            abort_unless(GradeAccess::canAccessSubject($user, $prefillSubjectId), 403);
        }

        $subjects = GradeAccess::accessibleSubjects($user)->pluck('name', 'id');
        if ($subjects->isEmpty() && GradeAccess::usesFacultyWizard($user)) {
            $subjects = Subject::query()->orderBy('name')->pluck('name', 'id');
        }

        $classes = collect();
        if ($prefillSubjectId) {
            $classes = GradeAccess::classesForSubject($user, $prefillSubjectId)->pluck('name', 'id');
        }

        if ($prefillClassId && $prefillSubjectId) {
            abort_unless(
                GradeAccess::canAccessClassSubject($user, $prefillClassId, $prefillSubjectId),
                403
            );
        }

        // Map môn → lớp (JS)
        $classesBySubject = [];
        foreach (GradeAccess::accessibleSubjects($user) as $s) {
            $classesBySubject[$s->id] = GradeAccess::classesForSubject($user, (int) $s->id)
                ->pluck('name', 'id')
                ->toArray();
        }

        return view('grades::books.create', compact(
            'classes',
            'subjects',
            'prefillClassId',
            'prefillSubjectId',
            'classesBySubject'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'academic_year' => 'nullable|string|max:20|exists:academic_years,code',
        ]);

        abort_unless(
            GradeAccess::canAccessClass($user, (int) $data['class_id']),
            403,
            'Bạn không dạy / không quản lý lớp này.'
        );
        abort_unless(
            GradeAccess::canAccessClassSubject($user, (int) $data['class_id'], (int) $data['subject_id']),
            403,
            'Bạn không dạy môn này trong lớp đã chọn.'
        );

        if ($user->isInstructor()) {
            $data['instructor_id'] = $user->instructor_id;
        }

        $book = $this->service->createWithDefaultColumns($data);

        return redirect()->route('grades.books.show', $book)
            ->with('success', 'Đã tạo bảng điểm với các cột mặc định (15p, 1 tiết, GK, thi).');
    }

    public function show(GradeBook $book)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(
            GradeAccess::booksQuery($user)->whereKey($book->id)->exists() || $user->isSuperAdmin(),
            403
        );

        $book->load(['columns', 'classModel', 'subject', 'instructor']);
        $students = $this->service->studentsForBook($book);
        $cells = GradeCell::query()
            ->where('grade_book_id', $book->id)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->keyBy('grade_column_id'));

        $canEdit = GradeAccess::canEditBook($user, $book);
        $isPdot = GradeAccess::isPdot($user);
        $isDirector = GradeAccess::isDirector($user);
        $requests = $book->changeRequests()->with('requester')->orderByDesc('id')->limit(10)->get();

        return view('grades::books.show', compact(
            'book', 'students', 'cells', 'canEdit', 'isPdot', 'isDirector', 'requests'
        ));
    }

    public function saveScores(Request $request, GradeBook $book)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEditBook($user, $book) || GradeAccess::isPdot($user), 403);

        $data = $request->validate([
            'scores' => 'required|array',
        ]);

        try {
            $this->service->saveScores($book, $data['scores'], $user);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã lưu điểm.');
    }

    public function lock(GradeBook $book)
    {
        $user = Auth::user();
        abort_unless($user->isInstructor() && (int) $user->instructor_id === (int) $book->instructor_id, 403);

        try {
            $this->service->lockByInstructor($book, $user);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã khóa bảng điểm và gửi PDOT duyệt. Muốn sửa sau khóa: gửi ý kiến mở khóa.');
    }

    public function approve(GradeBook $book)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::isPdot($user), 403);
        $this->service->approveByPdot($book, $user);

        return back()->with('success', 'Đã duyệt bảng điểm.');
    }

    public function requestUnlock(Request $request, GradeBook $book)
    {
        $user = Auth::user();
        abort_unless(
            ($user->isInstructor() && (int) $user->instructor_id === (int) $book->instructor_id)
            || GradeAccess::isPdot($user),
            403
        );
        abort_unless($book->isLocked() || $book->status === GradeBook::STATUS_APPROVED, 422, 'Bảng chưa khóa.');

        $data = $request->validate(['reason' => 'required|string|max:2000']);
        $this->service->requestUnlock($book, $user, $data['reason']);

        return back()->with('success', 'Đã gửi ý kiến mở khóa lên PDOT / Chủ nhiệm.');
    }

    public function export(Request $request, GradeBook $book, GradeExportService $exportService)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(
            GradeAccess::booksQuery($user)->whereKey($book->id)->exists() || $user->isSuperAdmin(),
            403
        );

        return $exportService->download(
            $book,
            $request->integer('template_id') ?: null,
            $request->input('format', 'excel')
        );
    }

    public function reviewRequest(Request $request, GradeChangeRequest $changeRequest)
    {
        $user = Auth::user();
        $data = $request->validate([
            'action' => 'required|in:pdot_forward,approve,reject',
            'note' => 'nullable|string|max:2000',
        ]);

        if ($data['action'] === 'pdot_forward') {
            abort_unless(GradeAccess::isPdot($user), 403);
            $this->service->pdotForwardRequest($changeRequest, $user, $data['note'] ?? null);
            $msg = 'Đã chuyển ý kiến lên Chủ nhiệm PDOT.';
        } elseif ($data['action'] === 'approve') {
            abort_unless(GradeAccess::isDirector($user), 403);
            $this->service->directorApproveRequest($changeRequest, $user, $data['note'] ?? null);
            $msg = 'Đã duyệt mở khóa — GV có thể sửa điểm (trạng thái revision).';
        } else {
            abort_unless(GradeAccess::isPdot($user) || GradeAccess::isDirector($user), 403);
            $this->service->rejectRequest($changeRequest, $user, $data['note'] ?? null);
            $msg = 'Đã từ chối yêu cầu.';
        }

        return back()->with('success', $msg);
    }
}
