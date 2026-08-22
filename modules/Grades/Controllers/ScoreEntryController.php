<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\ConductRecord;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeActor;
use Modules\Grades\Services\GradeBookService;
use Modules\Grades\Services\GradeScoreImportService;

/**
 * Form “CHỌN LỚP, MÔN HỌC CẦN VÀO ĐIỂM”
 */
class ScoreEntryController extends Controller
{
    public function __construct(
        private readonly GradeBookService $books,
        private readonly GradeScoreImportService $importer,
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $classes = GradeAccess::usesFacultyWizard($user)
            ? ClassModel::query()->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        if (! GradeAccess::usesFacultyWizard($user)) {
            if ($request->integer('subject_id')) {
                $classes = GradeAccess::classesForSubject($user, (int) $request->integer('subject_id'));
            } else {
                $ids = GradeAccess::teachingPairs($user)->pluck('class_id')->unique()->filter()->all();
                $classes = ClassModel::query()->whereIn('id', $ids ?: [0])->orderBy('name')->get(['id', 'name', 'code']);
            }
        }

        $subjects = GradeAccess::accessibleSubjects($user);
        $canEnter = GradeActor::canEnterScores($user) || GradeActor::canFull($user);

        $classId = $request->integer('class_id') ?: null;
        $subjectId = $request->integer('subject_id') ?: null;
        $year = $request->query('academic_year');

        $books = collect();
        $students = collect();
        $conducts = collect();

        if ($classId && $subjectId) {
            abort_unless(
                GradeAccess::canAccessClassSubject($user, $classId, $subjectId) || GradeAccess::usesFacultyWizard($user),
                403
            );
            $books = GradeBook::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->when($year, fn ($q) => $q->where('academic_year', $year))
                ->orderByDesc('id')
                ->get();

            $students = User::query()
                ->where('user_type', 'student')
                ->where('class_id', $classId)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            $conducts = ConductRecord::query()
                ->whereIn('user_id', $students->pluck('id'))
                ->when($year, fn ($q) => $q->where('academic_year', $year))
                ->get()
                ->keyBy('user_id');
        }

        return view('grades::academic.entry.index', compact(
            'classes', 'subjects', 'canEnter', 'classId', 'subjectId', 'year', 'books', 'students', 'conducts'
        ));
    }

    public function openBook(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEnterScores($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year' => 'nullable|string|max:20|exists:academic_years,code',
            'title' => 'nullable|string|max:255',
        ]);

        abort_unless(
            GradeAccess::canAccessClassSubject($user, (int) $data['class_id'], (int) $data['subject_id'])
            || GradeAccess::usesFacultyWizard($user),
            403
        );

        $existing = GradeBook::query()
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->when(
                ! empty($data['academic_year']),
                fn ($q) => $q->where('academic_year', $data['academic_year'])
            )
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('grades.books.show', $existing);
        }

        $book = $this->books->createWithDefaultColumns([
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'],
            'instructor_id' => $user->instructor_id,
            'academic_year' => $data['academic_year'] ?? null,
            'title' => $data['title'] ?? ('Bảng điểm '.now()->format('m/Y')),
        ]);

        return redirect()->route('grades.books.show', $book)
            ->with('success', 'Đã mở bảng điểm để vào điểm trực tiếp.');
    }

    public function importFile(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEnterScores($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year' => 'nullable|string|max:20|exists:academic_years,code',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        abort_unless(
            GradeAccess::canAccessClassSubject($user, (int) $data['class_id'], (int) $data['subject_id'])
            || GradeAccess::usesFacultyWizard($user),
            403
        );

        $book = GradeBook::query()
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->when(
                ! empty($data['academic_year']),
                fn ($q) => $q->where('academic_year', $data['academic_year'])
            )
            ->orderByDesc('id')
            ->first();

        if (! $book) {
            $book = $this->books->createWithDefaultColumns([
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id'],
                'instructor_id' => $user->instructor_id,
                'academic_year' => $data['academic_year'] ?? null,
                'title' => 'Bảng điểm import '.now()->format('d/m/Y'),
            ]);
        }

        try {
            $result = $this->importer->import($book, $request->file('file'), $user);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import thất bại: '.$e->getMessage());
        }

        $msg = "Đã import {$result['imported']} học viên";
        if ($result['skipped'] > 0) {
            $msg .= ", bỏ qua {$result['skipped']}";
        }
        if ($result['unmatched'] !== []) {
            $msg .= '. Không khớp: '.implode('; ', array_slice($result['unmatched'], 0, 5));
        }

        return redirect()
            ->route('grades.books.show', $result['book'])
            ->with('success', $msg);
    }

    public function saveConduct(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEnterScores($user) || GradeActor::canFull($user), 403);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year' => 'nullable|string|max:20|exists:academic_years,code',
            'conduct_rank' => 'nullable|string|max:40',
            'discipline' => 'nullable|string|max:120',
            'suspended' => 'nullable|boolean',
            'note' => 'nullable|string|max:1000',
        ]);

        $student = User::query()->findOrFail($data['user_id']);
        abort_unless($student->user_type === 'student', 422, 'Chỉ ghi cho học viên.');
        if (! empty($data['class_id']) && (int) $student->class_id !== (int) $data['class_id']) {
            return back()->with('error', 'Học viên không thuộc lớp đã chọn.');
        }

        ConductRecord::query()->updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'academic_year' => $data['academic_year'] ?? null,
            ],
            [
                'class_id' => $data['class_id'] ?? $student->class_id,
                'conduct_rank' => $data['conduct_rank'] ?? null,
                'discipline' => $data['discipline'] ?? null,
                'suspended' => $request->boolean('suspended'),
                'note' => $data['note'] ?? null,
                'updated_by' => $user->id,
            ]
        );

        return back()->with('success', 'Đã ghi rèn luyện / kỷ luật / tạm ngừng học cho '.$student->name.'.');
    }
}
