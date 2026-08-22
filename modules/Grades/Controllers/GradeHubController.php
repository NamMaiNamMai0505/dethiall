<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeChangeRequest;
use Modules\Grades\Services\GradeAccess;
use Modules\Subject\Models\Subject;
use Modules\Unit\Models\Unit;

class GradeHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Entry:
     * - GV → chọn Môn
     * - PDOT / super-admin → chọn Khoa
     */
    public function __invoke()
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403, 'Bạn không có quyền vào Quản lý điểm.');

        if (GradeAccess::usesFacultyWizard($user)) {
            $faculties = GradeAccess::accessibleFaculties($user);
            $stats = [
                'faculties' => $faculties->count(),
                'books' => GradeAccess::booksQuery($user)->count(),
                'pending_approve' => GradeAccess::booksQuery($user)
                    ->where('status', GradeBook::STATUS_PENDING_PDOT)
                    ->count(),
            ];

            return view('grades::wizard.faculties', compact('faculties', 'stats'));
        }

        // GV (và manager khoa): Môn → Lớp
        $subjects = GradeAccess::accessibleSubjects($user);
        $classCountBySubject = [];
        foreach ($subjects as $s) {
            $classCountBySubject[$s->id] = GradeAccess::classesForSubject($user, (int) $s->id)->count();
        }
        $stats = [
            'subjects' => $subjects->count(),
            'books' => GradeAccess::booksQuery($user)->count(),
            'pending_approve' => GradeAccess::booksQuery($user)
                ->where('status', GradeBook::STATUS_PENDING_PDOT)
                ->count(),
        ];

        return view('grades::wizard.subjects', compact('subjects', 'classCountBySubject', 'stats'));
    }

    /** GV: sau khi chọn môn → danh sách lớp. */
    public function classesForSubject(Subject $subject)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(GradeAccess::canAccessSubject($user, (int) $subject->id), 403, 'Bạn không dạy môn này.');

        $classes = GradeAccess::classesForSubject($user, (int) $subject->id);
        $bookCounts = GradeAccess::booksQuery($user)
            ->where('subject_id', $subject->id)
            ->selectRaw('class_id, count(*) as c')
            ->groupBy('class_id')
            ->pluck('c', 'class_id');

        return view('grades::wizard.classes', [
            'mode' => 'instructor',
            'subject' => $subject,
            'unit' => null,
            'classes' => $classes,
            'bookCounts' => $bookCounts,
        ]);
    }

    /** GV / PDOT: phòng điểm Lớp + Môn. */
    public function room(Subject $subject, ClassModel $class)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(
            GradeAccess::canAccessClassSubject($user, (int) $class->id, (int) $subject->id),
            403,
            'Bạn không phụ trách cặp lớp–môn này.'
        );

        $books = GradeAccess::booksQuery($user)
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->orderByDesc('id')
            ->get();

        $pendingRequests = GradeChangeRequest::query()
            ->with(['book', 'requester'])
            ->whereHas('book', fn ($q) => $q
                ->where('class_id', $class->id)
                ->where('subject_id', $subject->id))
            ->whereIn('status', [GradeChangeRequest::STATUS_PENDING, GradeChangeRequest::STATUS_PDOT_OK])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('grades::wizard.room', compact('subject', 'class', 'books', 'pendingRequests'));
    }

    /** PDOT: chọn khoa → danh sách môn. */
    public function facultySubjects(Unit $unit)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(GradeAccess::usesFacultyWizard($user), 403);

        $subjects = GradeAccess::subjectsForFaculty($user, (int) $unit->id);
        $classCountBySubject = [];
        foreach ($subjects as $s) {
            $classCountBySubject[$s->id] = GradeAccess::classesForFacultySubject(
                $user,
                (int) $unit->id,
                (int) $s->id
            )->count();
        }

        return view('grades::wizard.faculty-subjects', compact('unit', 'subjects', 'classCountBySubject'));
    }

    /** PDOT: khoa + môn → lớp. */
    public function facultyClasses(Unit $unit, Subject $subject)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(GradeAccess::usesFacultyWizard($user), 403);

        $classes = GradeAccess::classesForFacultySubject($user, (int) $unit->id, (int) $subject->id);
        $bookCounts = GradeAccess::booksQuery($user)
            ->where('subject_id', $subject->id)
            ->selectRaw('class_id, count(*) as c')
            ->groupBy('class_id')
            ->pluck('c', 'class_id');

        return view('grades::wizard.classes', [
            'mode' => 'pdot',
            'subject' => $subject,
            'unit' => $unit,
            'classes' => $classes,
            'bookCounts' => $bookCounts,
        ]);
    }

    /** PDOT: khoa + môn + lớp → bảng điểm. */
    public function facultyRoom(Unit $unit, Subject $subject, ClassModel $class)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless(GradeAccess::usesFacultyWizard($user), 403);

        $books = GradeAccess::booksQuery($user)
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->orderByDesc('id')
            ->get();

        $pendingRequests = GradeChangeRequest::query()
            ->with(['book', 'requester'])
            ->whereHas('book', fn ($q) => $q
                ->where('class_id', $class->id)
                ->where('subject_id', $subject->id))
            ->whereIn('status', [GradeChangeRequest::STATUS_PENDING, GradeChangeRequest::STATUS_PDOT_OK])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('grades::wizard.room', [
            'subject' => $subject,
            'class' => $class,
            'books' => $books,
            'pendingRequests' => $pendingRequests,
            'unit' => $unit,
        ]);
    }
}
