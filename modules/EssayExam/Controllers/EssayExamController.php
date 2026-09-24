<?php

namespace Modules\EssayExam\Controllers;

use App\Models\User;
use App\Models\AcademicYear;
use App\Models\DigitalSignature;
use App\Support\SystemNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\ExportTemplates\Contracts\DocumentConverterInterface;
use Modules\EssayExam\Models\EssayExam;
use Modules\EssayExam\Models\EssayExamQuestion;
use Modules\EssayExam\Models\EssayExamWorkflowLog;
use Modules\EssayExam\Models\EssayExamDraw;
use Modules\EssayExam\Models\EssayExamApprovalDocument;
use Modules\EssayExam\Models\IntegratedAnswerSet;
use Modules\EssayExam\Models\IntegratedAnswerItem;
use Modules\EssayExam\Models\IntegratedAnswerWorkflowLog;
use Modules\Subject\Models\Subject;
use Modules\Class\Models\ClassModel;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsQuestionBank;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Models\LmsQuestion;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\ExamOrganization\Models\ExamOrganizationPlan;

class EssayExamController extends Controller
{
    public function index(Request $request): View
    {
        $query = EssayExam::query();
        if ($request->user()->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department'])) {
            $unitId = $request->user()->unit_id ?: $request->user()->instructor?->unit_id;
            $query->whereHas('creator', fn ($creator) => $creator->where('unit_id', $unitId ?: 0));
        }

        return $this->examList($request, $query, false);
    }

    public function mine(Request $request): View
    {
        return $this->examList($request, EssayExam::query()->where('created_by_user_id', $request->user()->id), true);
    }

    private function examList(Request $request, $query, bool $mine): View
    {
        $classes = ClassModel::with('specialization.trainingSystem')->orderBy('name')->get();
        $specializations = Specialization::orderBy('name')->get();
        $trainingSystems = TrainingSystem::orderBy('name')->get();

        $exams = $query->with(['subject.specialization', 'class.specialization.trainingSystem', 'questions'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($x) => $x->where('code','like',"%$s%")->orWhere('title','like',"%$s%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->filled('training_system_id'), fn ($q) => $q->whereHas('class.specialization', fn ($s) => $s->where('training_system_id', $request->integer('training_system_id'))))
            ->when($request->filled('specialization_id'), fn ($q) => $q->whereHas('class', fn ($c) => $c->where('specialization_id', $request->integer('specialization_id'))))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->integer('class_id')))
            ->latest()->paginate(15)->withQueryString();

        return view('essay-exam::index', compact('exams', 'mine', 'classes', 'specializations', 'trainingSystems'));
    }

    private function examTitle(int $subjectId, int $classId): string
    {
        $subject = Subject::findOrFail($subjectId);
        $class = ClassModel::findOrFail($classId);

        return 'Bộ ĐTTL môn '.$subject->short_label.' Lớp '.($class->code ?: $class->name);
    }

    private function nextExamCode(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('essay_exam_code_sequences')->where('id', 1)->lockForUpdate()->first();
            abort_unless($sequence, 500, 'Chưa khởi tạo dãy mã bộ đề.');
            $number = (int) $sequence->last_number;
            do {
                abort_if($number >= 9999, 422, 'Đã hết dãy mã bộ đề TL0001–TL9999.');
                $code = 'TL'.str_pad((string) ++$number, 4, '0', STR_PAD_LEFT);
            } while (EssayExam::where('code', $code)->exists());

            DB::table('essay_exam_code_sequences')->where('id', 1)->update(['last_number' => $number]);

            return $code;
        });
    }

    public function approval(Request $request): View
    {
        $user = $request->user();
        $fullAdmin = $user?->hasAnyRole(['super-admin','system-manager','manager']);
        $stage = $fullAdmin ? ($request->input('stage') ?: 'PENDING_DEPT') : ($user?->hasAnyRole(['faculty-manager','department-head','head-of-department']) ? 'PENDING_DEPT' : ($user?->hasAnyRole(['training-office-manager','exam-manager','exam-office','testing-office']) ? 'PENDING_EXAM_OFFICE' : 'PENDING_BGH'));
        $query = EssayExam::with(['subject.specialization','class','questions'])->whereHas('questions', fn($q) => $q->where('paper_status',$stage));
        if ($stage === 'PENDING_DEPT' && ! $fullAdmin) {
            $unitId = $user->unit_id ?: $user->instructor?->unit_id;
            $query->whereHas('creator', fn ($creator) => $creator->where('unit_id', $unitId ?: 0));
        }
        if ($request->filled('subject_id')) $query->where('subject_id',$request->integer('subject_id'));
        if ($request->filled('teacher')) $query->where(function($q) use ($request) { $q->where('created_by_display_name','like','%'.$request->teacher.'%')->orWhere('created_by_username','like','%'.$request->teacher.'%'); });
        if ($request->filled('specialization_id')) $query->whereHas('subject', fn($q) => $q->where('specialization_id',$request->integer('specialization_id')));
        $examOptions = (clone $query)->with('subject')->orderBy('code')->get(['id','code','title','subject_id','created_by_display_name','created_by_username']);
        if ($request->filled('exam_id')) $query->whereKey($request->integer('exam_id'));
        $exams = $query->latest()->get();
        $lmsBanks = LmsQuestionBank::with(['course', 'questions.lesson'])
            ->where('status', $stage)
            ->whereHas('questions')
            ->latest()
            ->get();
        $subjects = Subject::orderBy('name')->get(['id','code','name']);
        $specializations = \Modules\Specialization\Models\Specialization::orderBy('name')->get(['id','name','code']);
        return view('essay-exam::approval', compact('exams','lmsBanks','stage','subjects','specializations','examOptions'));
    }

    public function approvalDocuments(Request $request): View
    {
        $documents = EssayExamApprovalDocument::with(['exam', 'subject', 'class', 'approver'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search): void {
                    $nested->where('decision_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%")
                        ->orWhere('subject_name', 'like', "%{$search}%");
                });
            })
            ->latest('approved_at')
            ->paginate(20)
            ->withQueryString();

        return view('essay-exam::approval-documents', compact('documents'));
    }

    public function savePrintedApprovalDocument(Request $request, EssayExam $essayExam): JsonResponse
    {
        $data = $request->validate([
            'print_mode' => 'required|in:unsigned,digital,direct',
            'signature_data' => 'nullable|string|max:7000000',
        ]);
        $signature = null;
        $user = $request->user();
        abort_unless($this->canReviewExamStage($user, $essayExam, 'PENDING_BGH'), 403);
        abort_unless($essayExam->status === 'PENDING_BGH' && $essayExam->questions()->where('paper_status', 'PENDING_BGH')->exists(), 422, 'Bộ đề không còn chờ BGH duyệt.');
        if ($data['print_mode'] === 'digital') {
            $signature = $this->approvalDigitalSignature($user);
        } elseif ($data['print_mode'] === 'direct') {
            $signature = $this->captureDirectApprovalSignature((string) ($data['signature_data'] ?? ''));
        }
        $essayExam->load(['subject', 'class', 'questions']);
        $finalApproval = in_array($data['print_mode'], ['digital', 'direct'], true);
        if ($finalApproval) {
            DB::transaction(function () use ($essayExam, $user): void {
                $updated = $essayExam->questions()->where('paper_status', 'PENDING_BGH')->update(['paper_status' => 'APPROVED']);
                abort_unless($updated > 0, 422, 'Bộ đề đã được tài khoản khác duyệt.');
                abort_unless($essayExam->questions()->where('paper_status', '!=', 'APPROVED')->doesntExist(), 422, 'Bộ đề vẫn còn đề số ở bước duyệt khác.');
                $this->transition($essayExam, 'APPROVED', 'APPROVE_PRINT', $user, 'Duyệt bộ đề bằng bản in có chữ ký.');
                $this->notifyLowerReviewLevel($essayExam, 'PENDING_BGH', 'đã duyệt', $user);
                $essayExam->update([
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                    'locked' => true,
                    'approval_qr' => $essayExam->approval_qr ?: 'QR-EXAM-'.strtoupper(substr(hash('sha256',$essayExam->id.'|'.$essayExam->code.'|'.microtime(true)),0,24)),
                ]);
            });
        }
        $document = EssayExamApprovalDocument::firstOrNew(['essay_exam_id' => $essayExam->id]);
        $document->fill([
            'decision_code' => $document->decision_code ?: 'IN-'.now()->format('YmdHis').'-'.str_pad((string) $essayExam->id, 5, '0', STR_PAD_LEFT),
            'title' => $essayExam->title,
            'class_id' => $essayExam->class_id,
            'class_name' => $essayExam->class?->name,
            'subject_id' => $essayExam->subject_id,
            'subject_name' => $essayExam->subject?->name,
            'approved_by_user_id' => $user->id,
            'approver_name' => $user->name ?: $user->email,
            'approved_at' => $finalApproval ? now() : $document->approved_at,
            'signature_method' => $signature['method'] ?? $document->signature_method,
            'signature_path' => $signature['path'] ?? $document->signature_path,
            'status' => $finalApproval ? 'APPROVED' : ($document->status === 'APPROVED' ? 'APPROVED' : 'PRINTED'),
        ]);
        $document->save();
        $document->load(['exam.subject', 'exam.class', 'exam.questions']);
        $html = view('essay-exam::approval-document', [
            'document' => $document,
            'signatureUrl' => $document->signature_path ? $this->signatureDataUrl($document->signature_path) : null,
        ])->render();
        $path = 'essay-exam/approval-documents/'.$document->decision_code.'.html';
        Storage::disk('local')->put($path, $html);
        $printPath = $finalApproval && $signature
            ? ($this->createSignedExamPdf($essayExam, $user, $signature) ?: $this->examApprovalPrintablePath($essayExam))
            : $this->examApprovalPrintablePath($essayExam);
        $document->update(['document_path' => $printPath ?: $path]);
        if ($finalApproval) $this->archiveImportedSources($essayExam, $document);

        return response()->json([
            'ok' => true,
            'document_id' => $document->id,
            'print_url' => $document->document_path ? route('essay-exams.approval-documents.download', $document).'?inline=1' : null,
        ]);
    }

    public function approvalDocumentTemplate()
    {
        $path = public_path('samples/essay-exam/Đe_dieu_duong_unique.docx');
        abort_unless(is_file($path), 404, 'Chưa có mẫu chuẩn văn bản phê duyệt.');
        return response()->download($path, 'Đe_dieu_duong_unique.docx');
    }

    public function approvalDocumentShow(EssayExamApprovalDocument $document): View
    {
        $document->load(['exam.subject', 'exam.class', 'exam.questions', 'subject', 'class', 'approver']);
        return view('essay-exam::approval-document', [
            'document' => $document,
            'signatureUrl' => $this->signatureDataUrl($document->signature_path),
        ]);
    }

    public function approvalDocumentDownload(EssayExamApprovalDocument $document)
    {
        abort_unless($document->document_path, 404, 'Chưa có file văn bản phê duyệt.');
        $disk = Storage::disk('local')->exists($document->document_path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($document->document_path), 404, 'Chưa có file văn bản phê duyệt.');
        $extension = strtolower(pathinfo($document->document_path, PATHINFO_EXTENSION));
        $path = Storage::disk($disk)->path($document->document_path);
        if (request()->boolean('inline')) {
            return response()->file($path, ['Content-Type' => $extension === 'pdf' ? 'application/pdf' : 'text/html; charset=UTF-8']);
        }
        return response()->download(
            $path,
            $document->decision_code.'.'.$extension,
            ['Content-Type' => $extension === 'pdf' ? 'application/pdf' : 'text/html; charset=UTF-8'],
        );
    }

    public function approveLmsBank(Request $request, LmsQuestionBank $bank): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can('essay-exams.approval.approve'), 403);
        abort_unless(in_array($bank->status, ['PENDING_DEPT', 'PENDING_EXAM_OFFICE', 'PENDING_BGH'], true), 422, 'Ngân hàng LMS không còn ở trạng thái chờ duyệt.');

        if ($bank->status === 'PENDING_DEPT') {
            abort_unless($user->hasAnyRole(['department-head','head-of-department','faculty-manager','super-admin']), 403);
            $bank->update(['status' => 'PENDING_EXAM_OFFICE']);
            return back()->with('success', 'Đã duyệt ngân hàng trắc nghiệm qua cấp khoa, chuyển khảo thí.');
        }

        if ($bank->status === 'PENDING_EXAM_OFFICE') {
            abort_unless($user->hasAnyRole(['exam-manager','exam-office','testing-office','training-office-manager','super-admin']), 403);
            $bank->update(['status' => 'PENDING_BGH']);
            return back()->with('success', 'Đã duyệt ngân hàng trắc nghiệm qua khảo thí, chuyển Ban Giám hiệu.');
        }

        abort_unless($user->hasAnyRole(['bgh','board-of-management','ban giám hiệu','super-admin']), 403);
        $bank->update(['status' => 'APPROVED', 'approved_at' => now(), 'approved_by' => $user->id]);
        return back()->with('success', 'Đã được Ban Giám hiệu duyệt ngân hàng trắc nghiệm.');
    }

    public function approveLmsBanksBulk(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can('essay-exams.approval.approve'), 403);
        $ids = $request->boolean('approve_all')
            ? LmsQuestionBank::query()->whereIn('status', ['PENDING_DEPT', 'PENDING_EXAM_OFFICE', 'PENDING_BGH'])->pluck('id')->all()
            : $request->input('bank_ids', []);
        $banks = LmsQuestionBank::query()->whereIn('id', $ids)->get();
        abort_if($banks->isEmpty(), 422, 'Chưa chọn ngân hàng trắc nghiệm để duyệt.');

        $updated = 0;
        foreach ($banks as $bank) {
            if ($bank->status === 'PENDING_DEPT' && $user->hasAnyRole(['department-head','head-of-department','faculty-manager','super-admin'])) {
                $bank->update(['status' => 'PENDING_EXAM_OFFICE']);
                $updated++;
            } elseif ($bank->status === 'PENDING_EXAM_OFFICE' && $user->hasAnyRole(['exam-manager','exam-office','testing-office','training-office-manager','super-admin'])) {
                $bank->update(['status' => 'PENDING_BGH']);
                $updated++;
            } elseif ($bank->status === 'PENDING_BGH' && $user->hasAnyRole(['bgh','board-of-management','ban giám hiệu','super-admin'])) {
                $bank->update(['status' => 'APPROVED', 'approved_at' => now(), 'approved_by' => $user->id]);
                $updated++;
            }
        }
        abort_if($updated === 0, 403, 'Tài khoản không có quyền duyệt các ngân hàng đã chọn ở cấp hiện tại.');
        return back()->with('success', 'Đã duyệt '.$updated.' ngân hàng trắc nghiệm đã chọn.');
    }

    public function bank(Request $request): View
    {
        $semester = $this->normalizeBankSemester($request->input('semester'));
        $difficultyValues = $this->bankDifficultyValues($request->input('difficulty'));
        $examTypeValues = $this->bankExamTypeValues($request->input('exam_type'));
        $academicYears = EssayExam::query()
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        $exams = EssayExam::with(['subject.specialization','questions'])->withCount('draws')->where('status','APPROVED')->where('locked',true)->whereHas('questions', fn($q) => $q->where('paper_status','APPROVED'))
            ->when($request->search, fn ($q,$s) => $q->where(fn($x) => $x->where('code','like',"%$s%")->orWhere('title','like',"%$s%")))
            ->when($request->used === 'yes', fn ($q) => $q->has('draws'))
            ->when($request->used === 'no', fn ($q) => $q->doesntHave('draws'))
            ->when($request->academic_year, fn ($q,$v) => $q->where('academic_year',$v))
            ->when($semester, fn ($q,$v) => $q->where('semester',$v))
            ->when($difficultyValues, fn ($q,$values) => $q->whereIn('difficulty',$values))
            ->when($examTypeValues, fn ($q,$values) => $q->whereIn('exam_type',$values))
            ->latest('approved_at')->paginate(20)->withQueryString();
        return view('essay-exam::bank', [
            'exams' => $exams,
            'academicYears' => $academicYears,
            'selectedSemester' => $semester,
            'selectedDifficulty' => $request->input('difficulty'),
            'selectedExamType' => $request->input('exam_type'),
        ]);
    }

    private function normalizeBankSemester(?string $value): ?string
    {
        $key = mb_strtolower(trim((string) $value));
        if ($key === '') return null;
        return match ($key) {
            'semester_1', '1', 'hk1', 'hoc ky 1', 'học kỳ 1' => 'semester_1',
            'semester_2', '2', 'hk2', 'hoc ky 2', 'học kỳ 2' => 'semester_2',
            'semester_3', '3', 'hk3', 'hoc ky 3', 'học kỳ 3' => 'semester_3',
            'semester_4', '4', 'hk4', 'hoc ky 4', 'học kỳ 4' => 'semester_4',
            'semester_5', '5', 'hk5', 'hoc ky 5', 'học kỳ 5' => 'semester_5',
            'semester_6', '6', 'hk6', 'hoc ky 6', 'học kỳ 6' => 'semester_6',
            'semester_7', '7', 'hk7', 'hoc ky 7', 'học kỳ 7' => 'semester_7',
            'summer', 'he', 'hè', 'hoc ky he', 'học kỳ hè' => 'summer',
            default => $value,
        };
    }

    private function bankDifficultyValues(?string $value): array
    {
        return match (mb_strtolower(trim((string) $value))) {
            'dễ', 'de', 'easy' => ['Dễ', 'easy', 'de'],
            'vừa', 'vua', 'medium', 'normal', 'trung bình' => ['Vừa', 'medium', 'normal', 'trung bình'],
            'khó', 'kho', 'hard' => ['Khó', 'hard', 'kho'],
            default => trim((string) $value) !== '' ? [trim((string) $value)] : [],
        };
    }

    private function bankExamTypeValues(?string $value): array
    {
        return match (mb_strtolower(trim((string) $value))) {
            'tự luận', 'tu luan', 'essay' => ['Tự luận', 'essay'],
            'trắc nghiệm', 'trac nghiem', 'multiple_choice', 'multiple choice' => ['Trắc nghiệm', 'multiple_choice', 'multiple choice'],
            'tích hợp', 'tich hop', 'integrated' => ['Tích hợp', 'integrated'],
            default => trim((string) $value) !== '' ? [trim((string) $value)] : [],
        };
    }

    public function used(Request $request): View
    {
        $draws = EssayExamDraw::with(['exam.subject'])
            ->when($request->q, function ($q, $v) {
                $term = '%'.trim($v).'%';
                $q->where(function ($x) use ($term) {
                    $x->where('draw_code', 'like', $term)->orWhere('qr_code', 'like', $term)
                        ->orWhereHas('exam', fn ($e) => $e->where('code', 'like', $term));
                });
            })->latest('printed_at')->paginate(20)->withQueryString();
        return view('essay-exam::used', compact('draws'));
    }

    public function draw(Request $request): View
    {
        $subjects = Subject::with('specialization')->active()->orderBy('name')->get();
        $specializations = \Modules\Specialization\Models\Specialization::orderBy('name')->get(['id','code','name']);
        $classes = ClassModel::with('specialization')->where('is_active',true)->whereNotNull('specialization_id')->orderBy('name')->get();
        $user = $request->user();
        $instructorId = $user?->instructor_id ?: Instructor::where('email',$user?->email)->value('id');
        $isInstructor = $user?->hasRole('instructor') && ! $user?->hasAnyRole(['super-admin', 'system-manager', 'manager', 'exam-manager', 'exam-office', 'testing-office']);
        $details = ScheduleDetail::with('trainingSchedule')->when($isInstructor, fn($q) => $q->where('instructor_id',$instructorId ?: 0))->whereNotNull('subject_id')->get();
        $classSubjectMap = $details->filter(fn($d) => $d->trainingSchedule?->class_id)->groupBy(fn($d) => $d->trainingSchedule->class_id)->map(fn($rows) => $rows->pluck('subject_id')->unique()->values())->toArray();
        foreach (DB::table('lms_courses')->whereNotNull('class_id')->when($isInstructor, fn ($q) => $q->where('instructor_id', $instructorId ?: 0))->get(['class_id','subject_id']) as $course) $classSubjectMap[$course->class_id] = collect($classSubjectMap[$course->class_id] ?? [])->push($course->subject_id)->unique()->values()->all();
        $approvedExams = EssayExam::query()->whereHas('questions', fn ($q) => $q->where('paper_status', 'APPROVED'))->get(['subject_id', 'class_id']);
        foreach ($approvedExams as $approvedExam) {
            if (! $isInstructor && $approvedExam->class_id) {
                $classSubjectMap[$approvedExam->class_id] = collect($classSubjectMap[$approvedExam->class_id] ?? [])->push($approvedExam->subject_id)->unique()->values()->all();
            }
        }
        $subjects = $subjects->whereIn('id', $approvedExams->pluck('subject_id')->unique())->values();
        if ($isInstructor) {
            $classes = $classes->whereIn('id', array_keys($classSubjectMap))->values();
            $availableSubjectIds = collect($classSubjectMap)->flatten()->map(fn ($id) => (int) $id)->unique()->values();
            $subjects = $subjects->whereIn('id', $availableSubjectIds)->values();
        }
        $draws = EssayExamDraw::with(['exam.subject'])
            ->when($request->draw_code, fn ($q, $v) => $q->where(function ($x) use ($v) {
                $term = '%'.trim($v).'%';
                $x->where('draw_code', 'like', $term)->orWhere('qr_code', 'like', $term)
                    ->orWhereHas('exam', fn ($e) => $e->where('code', 'like', $term));
            }))
            ->when($request->class_name, fn ($q, $v) => $q->where('class_name', 'like', '%'.trim($v).'%'))
            ->when($request->subject_id, fn ($q, $v) => $q->whereHas('exam', fn ($x) => $x->where('subject_id', $v)))
            ->latest('drawn_at')->paginate(20)->withQueryString();
        $paperCounts = EssayExamQuestion::with('exam')->where('paper_status','APPROVED')
            ->select('essay_exam_id','paper_number')->get()->groupBy(fn($q) => $q->exam->subject_id ?? 0)->map(fn($rows) => $rows->pluck('paper_number')->unique()->count());
        $activeDraws = EssayExamDraw::with('exam')->latest('drawn_at')->get();
        $expiredDraws = $activeDraws->filter(fn($d) => $d->drawn_at && $d->drawn_at->lt(now()->subDays(3)))->values();
        // Trạng thái rút phải độc lập theo dạng đề; tự luận không được khóa tích hợp.
        $drawState = $activeDraws->filter(fn($d) => !$d->drawn_at || $d->drawn_at->gte(now()->subDays(3)))
            ->groupBy(fn($d) => $d->class_name.'|'.($d->exam->subject_id ?? 0).'|'.($d->exam->exam_type ?? 'Tự luận'))->map(fn($rows) => $rows->groupBy('draw_type')->map(fn($r) => $r->sortByDesc('drawn_at')->first()));
        $minuteKeys = $drawState->filter(fn($types) => $types->has('EVEN') && $types->has('ODD'))->keys()->values();
        $lessonStats = LmsLesson::query()->with('course:id,subject_id')->whereHas('course', fn ($q) => $q->whereIn('subject_id', $subjects->pluck('id')->all()))->get()->map(function ($lesson) {
            return ['id'=>$lesson->id, 'subject_id'=>$lesson->course?->subject_id, 'title'=>$lesson->title, 'hours'=>0,
                'mcq_count'=>LmsQuestion::where('lms_lesson_id',$lesson->id)->where('type','mcq')->count(),
                'essay_count'=>EssayExamQuestion::where('lms_lesson_id',$lesson->id)->where('question_type','essay')->where('paper_status','APPROVED')->count()];
        })->values();
        $approvedMcqBanks = LmsQuestionBank::with(['course.subject','questions' => fn ($q) => $q->where('type','mcq')->whereNotNull('lms_lesson_id')])
            ->where('status','APPROVED')->get();
        $integratedMcqBanks = $approvedMcqBanks->groupBy(fn ($bank) => $bank->course?->subject_id.':'.($bank->course?->class_id ?: 0))
            ->map(function ($banks, $key) {
                [$subjectId, $classId] = array_map('intval', explode(':', $key));
                return ['id'=>'mcq:'.$key,'title'=>'Ngân hàng trắc nghiệm tổng hợp - '.($banks->first()->course?->subject?->name ?: 'Môn học'),'course_id'=>$banks->first()->lms_course_id,'subject_id'=>$subjectId,'class_id'=>$classId ?: null,'count'=>$banks->sum(fn ($bank) => $bank->questions->count())];
            })->values();
        $mcqBankLessonStats = $approvedMcqBanks->flatMap(function ($bank) {
            $key = 'mcq:'.$bank->course?->subject_id.':'.($bank->course?->class_id ?: 0);
            return $bank->questions->groupBy('lms_lesson_id')->map(fn ($questions, $lessonId) => ['bank_id'=>$key,'lesson_id'=>(int) $lessonId,'count'=>$questions->count()])->values();
        })->groupBy(fn ($row) => $row['bank_id'].':'.$row['lesson_id'])->map(fn ($rows) => ['bank_id'=>$rows->first()['bank_id'],'lesson_id'=>$rows->first()['lesson_id'],'count'=>$rows->sum('count')])->values();
        $approvedEssayQuestions = EssayExamQuestion::with('exam')
            ->where('question_type','essay')->where('paper_status','APPROVED')->whereHas('exam', fn ($q) => $q->where('exam_type','Tự luận'))->get();
        $integratedEssayPools = $approvedEssayQuestions->groupBy(fn ($question) => $question->exam?->subject_id.':'.($question->exam?->class_id ?: 0))
            ->map(function ($questions, $key) {
                [$subjectId, $classId] = array_map('intval', explode(':', $key));
                return ['id'=>'essay:'.$key,'code'=>'POOL-'.$key,'title'=>'Ngân hàng tự luận tổng hợp','subject_id'=>$subjectId,'class_id'=>$classId ?: null,'count'=>$questions->unique('id')->count(),'papers'=>$questions->pluck('paper_number')->unique()->count()];
            })->values();
        $examPlans = ExamOrganizationPlan::query()
            ->whereDate('exam_date', '>=', today())
            ->orderBy('exam_date')->orderBy('exam_time')
            ->get(['id', 'class_id', 'subject_id', 'exam_date', 'exam_time', 'name'])
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'class_id' => $plan->class_id,
                'subject_id' => $plan->subject_id,
                'date' => $plan->exam_date?->format('Y-m-d'),
                'time' => $plan->exam_time ? substr((string) $plan->exam_time, 0, 5) : '',
                'name' => $plan->name,
            ])->values();
        return view('essay-exam::draw', compact('subjects','specializations','classes','classSubjectMap','draws','paperCounts','drawState','expiredDraws','minuteKeys','lessonStats','integratedMcqBanks','integratedEssayPools','mcqBankLessonStats','examPlans'));
    }

    public function minutes(Request $request): View
    {
        $className = trim((string) $request->query('class_name'));
        $subjectId = (int) $request->query('subject_id');
        $draws = EssayExamDraw::with(['exam.subject', 'drawnBy'])
            ->where('class_name', $className)
            ->where('drawn_at', '>=', now()->subDays(3))
            ->whereHas('exam', fn($q) => $q->where('subject_id', $subjectId))
            ->latest('drawn_at')->get();
        $even = $draws->firstWhere('draw_type', 'EVEN');
        $odd = $draws->firstWhere('draw_type', 'ODD');
        abort_unless($even && $odd, 422, 'Chưa đủ đề chẵn và đề lẻ để lập biên bản.');
        $autoPrint = $request->boolean('auto');
        $representativeName = $even->drawnBy?->name ?: ($odd->drawnBy?->name ?: 'Đại diện Ban Khảo thí');
        return view('essay-exam::minutes', compact('even','odd','className','autoPrint','representativeName'));
    }

    public function drawStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['specialization_id'=>'required|exists:specializations,id','subject_id'=>'required|exists:subjects,id','exam_type'=>'required|in:Tự luận,Tích hợp','draw_type'=>'required|in:EVEN,ODD','paper_number'=>'nullable|in:1,2','class_id'=>'required|exists:classes,id','plan_id'=>'required|exists:exam_organization_plans,id','location'=>'nullable|string|max:255']);
        $examPlan = ExamOrganizationPlan::findOrFail($data['plan_id']);
        abort_unless((int) $examPlan->class_id === (int) $data['class_id'] && (int) $examPlan->subject_id === (int) $data['subject_id'], 422, 'Kế hoạch thi không khớp lớp và môn đã chọn.');
        abort_unless($examPlan->exam_date && $examPlan->exam_date->startOfDay()->gte(today()), 422, 'Ngày thi trong kế hoạch đã qua.');
        $data['exam_date'] = $examPlan->exam_date->format('Y-m-d');
        $data['exam_time'] = $examPlan->exam_time;
        $data['paper_number'] = (int) ($data['paper_number'] ?? 1);
        $sourceData = $request->validate([
            // Đây là mã nguồn tổng hợp theo môn/lớp, không còn là ID của một ngân hàng riêng.
            'mcq_bank_id' => 'nullable|string|max:100',
            'essay_pool_id' => 'nullable|string|max:100',
        ]);
        if ($data['exam_type'] === 'Trắc nghiệm') {
            $requestedPlan = json_decode((string) $request->input('integrated_plan', '{}'), true) ?: [];
            $scoreData = $request->validate(['question_count'=>'required|integer|min:1|max:200','question_points'=>'required|numeric|min:0.01|max:100']);
            $data['question_count'] = (int) $scoreData['question_count'];
            $data['question_points'] = (float) $scoreData['question_points'];
        }
        $chosenSpec = \Modules\Specialization\Models\Specialization::findOrFail($data['specialization_id']);
        $chosenSubject = Subject::with('specialization')->findOrFail($data['subject_id']);
        abort_unless((int) $chosenSubject->specialization_id === (int) $chosenSpec->id, 422, 'Môn học không thuộc ngành đào tạo đã chọn.');
        $class = ClassModel::findOrFail($data['class_id']);
        abort_unless((int) $class->specialization_id === (int) $chosenSpec->id, 422, 'Lớp không thuộc ngành đào tạo đã chọn.');
        $user = $request->user();
        $instructorId = $user?->instructor_id ?: Instructor::where('email',$user?->email)->value('id');
        $isInstructor = $user?->hasRole('instructor') && ! $user?->hasAnyRole(['super-admin', 'system-manager', 'manager', 'exam-manager', 'exam-office', 'testing-office']);
        if ($isInstructor) abort_unless($instructorId && (ScheduleDetail::where('instructor_id',$instructorId)->where('subject_id',$data['subject_id'])->whereHas('trainingSchedule', fn($q) => $q->where('class_id',$class->id))->exists() || DB::table('lms_courses')->where('instructor_id', $instructorId)->where('class_id',$class->id)->where('subject_id',$data['subject_id'])->exists()), 422, 'Giáo viên chưa được phân công môn này cho lớp đã chọn.');
        if ($data['exam_type'] === 'Trắc nghiệm') {
            $pool = EssayExamQuestion::where('question_type','multiple_choice')->where('paper_status','APPROVED')
                ->whereHas('exam', fn($q) => $q->where('subject_id',$data['subject_id'])->where('exam_type','Trắc nghiệm'))
                ->get()->unique(fn($q) => $q->essay_exam_id.':'.$q->paper_number.':'.$q->question_number)->values();
            if ($requestedPlan) {
                $pickedQuestions = collect();
                foreach ($requestedPlan as $lessonId => $counts) {
                    $need = max(0, (int) ($counts['mcq'] ?? 0));
                    if ($need) {
                        $part = $pool->where('lms_lesson_id', (int) $lessonId)->shuffle()->take($need);
                        abort_if($part->count() < $need, 422, 'Bài học không đủ câu trắc nghiệm để rút.');
                        $pickedQuestions = $pickedQuestions->merge($part);
                    }
                }
                $data['question_count'] = $pickedQuestions->count();
                abort_if($pickedQuestions->isEmpty(), 422, 'Hãy nhập số câu trắc nghiệm đề nghị theo bài học.');
            } else {
                abort_if($pool->count() < $data['question_count'], 422, 'Ngân hàng trắc nghiệm không đủ số câu khác nhau để rút.');
                $pickedQuestions = $pool->shuffle()->take($data['question_count'])->values();
            }
            $exam = $pickedQuestions->first()->exam;
            $drawCode = 'RT-'.now()->format('YmdHis').'-'.random_int(100,999);
            $draw = EssayExamDraw::create(['essay_exam_id'=>$exam->id,'paper_number'=>$data['paper_number'],'question_ids'=>$pickedQuestions->pluck('id')->all(),'question_points'=>$data['question_points'],'draw_code'=>$drawCode,'qr_code'=>'QR-'.$drawCode,'draw_type'=>$data['draw_type'],'class_name'=>$class->name,'exam_date'=>$data['exam_date'] ?? null,'exam_time'=>$data['exam_time'] ?? null,'location'=>$data['location'] ?? null,'drawn_by_user_id'=>$request->user()->id,'drawn_at'=>now(),'printed_at'=>now()]);
            return redirect()->route('essay-exams.draw.print', ['draw' => $draw->id, 'auto' => 1]);
        }
        if ($data['exam_type'] === 'Tự luận') {
            // Tự luận luôn bốc ngẫu nhiên một đề hoàn chỉnh (một paper),
            // không bốc từng câu và không nhận số câu từ biểu mẫu.
            $essayQuestionsPool = EssayExamQuestion::query()
                ->where('question_type','essay')
                ->where('paper_status','APPROVED')
                ->whereHas('exam', fn ($q) => $q->where('subject_id', $data['subject_id'])
                    ->where(fn ($x) => $x->where('class_id', $class->id)->orWhereNull('class_id'))
                    ->where('exam_type','Tự luận'))
                ->get();
            abort_unless($essayQuestionsPool->isNotEmpty(), 422, 'Ngân hàng đề tự luận không thuộc môn đã chọn hoặc chưa có câu được duyệt.');
            $printedTypes = EssayExamDraw::where('class_name', $class->name)->where('drawn_at','>=',now()->subDays(3))
                ->whereHas('exam', fn ($q) => $q->where('subject_id', $data['subject_id'])->where('exam_type','Tự luận'))
                ->pluck('draw_type')->unique()->values();
            abort_if($printedTypes->contains($data['draw_type']), 422, 'Loại phiếu này đã được rút cho lớp và môn trong 3 ngày gần đây.');
            $papers = $essayQuestionsPool
                ->groupBy(fn ($question) => $question->essay_exam_id.':'.$question->paper_number)
                ->filter(fn ($questions) => $questions->isNotEmpty());
            $questions = $papers->shuffle()->first()?->sortBy('question_number')->values() ?? collect();
            abort_if($questions->isEmpty(), 422, 'Chưa có đề tự luận hoàn chỉnh được duyệt để bốc.');
            $drawCode = 'RT-'.now()->format('YmdHis').'-'.random_int(100,999);
            $draw = EssayExamDraw::create(['essay_exam_id'=>$questions->first()->essay_exam_id,'paper_number'=>$questions->first()->paper_number,'question_ids'=>$questions->pluck('id')->all(),'draw_code'=>$drawCode,'qr_code'=>'QR-'.$drawCode,'draw_type'=>$data['draw_type'],'class_name'=>$class->name,'exam_date'=>$data['exam_date'] ?? null,'exam_time'=>$data['exam_time'] ?? null,'location'=>$data['location'] ?? null,'drawn_by_user_id'=>$request->user()->id,'drawn_at'=>now(),'printed_at'=>now()]);
            return redirect()->route('essay-exams.draw.print', ['draw' => $draw->id, 'auto' => 1]);
        }
        if ($data['exam_type'] === 'Tích hợp') {
            abort_unless(! empty($sourceData['mcq_bank_id']) && ! empty($sourceData['essay_pool_id']), 422, 'Hãy chọn ngân hàng trắc nghiệm và ngân hàng tự luận cho đề tích hợp.');
            $scoreData = $request->validate(['mcq_total_points'=>'required|numeric|min:0|max:100','essay_total_points'=>'required|numeric|min:0|max:100']);
            abort_if((float) $scoreData['mcq_total_points'] + (float) $scoreData['essay_total_points'] <= 0, 422, 'Tổng điểm đề tích hợp phải lớn hơn 0.');
            $mcqSource = explode(':', (string) $sourceData['mcq_bank_id']);
            $mcqBankIds = LmsQuestionBank::query()->where('status','APPROVED')->whereHas('course', fn ($q) => $q->where('subject_id',$data['subject_id'])->where(fn ($x) => $x->where('class_id',$class->id)->orWhereNull('class_id')))->pluck('id');
            $essaySource = explode(':', (string) $sourceData['essay_pool_id']);
            $essayQuestionsPool = EssayExamQuestion::query()->where('question_type','essay')->where('paper_status','APPROVED')->whereHas('exam', fn ($q) => $q->where('subject_id',$data['subject_id'])->where(fn ($x) => $x->where('class_id',$class->id)->orWhereNull('class_id'))->where('exam_type','Tự luận'))->get();
            abort_unless($mcqBankIds->isNotEmpty() && $essayQuestionsPool->isNotEmpty(), 422, 'Nguồn câu hỏi đã chọn không thuộc môn/lớp hoặc chưa được duyệt.');
            $plan = json_decode((string) $request->input('integrated_plan', '{}'), true) ?: [];
            abort_if(! $plan, 422, 'Hãy nhập cơ cấu đề theo từng bài học.');
            $essayCount = (int) $request->validate(['essay_question_count' => 'required|integer|min:1|max:200'])['essay_question_count'];
            abort_if($essayCount < 1, 422, 'Hãy nhập số câu tự luận cần rút.');
            $course = LmsCourse::where('subject_id', $data['subject_id'])->where('class_id', $class->id)->first();
            abort_unless($course, 422, 'Chưa có khóa LMS cho môn/lớp này.');
            $pickedMcqQuestions = collect();
            foreach ($plan as $lessonId => $counts) {
                $lessonId = (int) $lessonId; $mcqCount = max(0, (int) ($counts['mcq'] ?? 0));
                if ($mcqCount) {
                    $questions = LmsQuestion::whereIn('lms_question_bank_id',$mcqBankIds)->where('lms_lesson_id',$lessonId)->where('type','mcq')->inRandomOrder()->limit($mcqCount)->get();
                    abort_if($questions->count() < $mcqCount, 422, 'Bài học không đủ câu trắc nghiệm để rút.');
                    foreach ($questions as $q) $pickedMcqQuestions->push(['question'=>$q,'lesson_id'=>$lessonId]);
                }
            }
            // Tự luận không chia theo bài học: lấy ngẫu nhiên trong toàn bộ
            // ngân hàng đề Dashboard, bao gồm tất cả các đề/câu đã duyệt.
            $essayQuestions = $essayQuestionsPool
                ->shuffle()->unique(fn ($q) => trim((string) $q->content))->values()->take($essayCount);
            abort_if($essayQuestions->count() < $essayCount, 422, 'Ngân hàng đề tự luận không đủ câu khác nhau để rút.');
            $mcqTotal = $pickedMcqQuestions->count();
            $mcqTotalPoints = (float) $scoreData['mcq_total_points'];
            $essayTotalPoints = (float) $scoreData['essay_total_points'];
            abort_if($mcqTotal === 0 && $mcqTotalPoints > 0, 422, 'Có điểm trắc nghiệm nhưng chưa chọn câu trắc nghiệm.');
            abort_if($essayCount === 0 && $essayTotalPoints > 0, 422, 'Có điểm tự luận nhưng chưa chọn câu tự luận.');
            abort_if($mcqTotal > 0 && $mcqTotalPoints <= 0, 422, 'Hãy nhập tổng điểm trắc nghiệm lớn hơn 0.');
            abort_if($essayCount > 0 && $essayTotalPoints <= 0, 422, 'Hãy nhập tổng điểm tự luận lớn hơn 0.');
            $mcqPoint = $mcqTotal > 0 ? round($mcqTotalPoints / $mcqTotal, 4) : 0;
            $essayPoint = $essayCount > 0 ? round($essayTotalPoints / $essayCount, 4) : 0;
            $code = $this->nextExamCode();
            $exam = EssayExam::create(['code'=>$code,'title'=>$this->examTitle((int) $data['subject_id'], (int) $class->id),'subject_id'=>$data['subject_id'],'class_id'=>$class->id,'duration_minutes'=>60,'exam_type'=>'Tích hợp','created_by_user_id'=>$request->user()->id,'created_by_username'=>$request->user()->email,'created_by_display_name'=>$request->user()->name,'note'=>'Cơ cấu đề: '.json_encode($plan, JSON_UNESCAPED_UNICODE)]);
            $number = 0;
            foreach ($pickedMcqQuestions as $index => $item) {
                $q = $item['question'];
                $point = $index === $mcqTotal - 1 ? round($mcqTotalPoints - ($mcqPoint * max(0, $mcqTotal - 1)), 4) : $mcqPoint;
                $exam->questions()->create(['lms_lesson_id'=>$item['lesson_id'],'paper_number'=>$data['paper_number'],'question_number'=>++$number,'question_type'=>'multiple_choice','content'=>$q->stem,'options'=>$q->options,'answer'=>$q->correctAnswerLabel(),'points'=>$point,'paper_status'=>'APPROVED']);
            }
            foreach ($essayQuestions as $index => $q) { $point = $index === $essayCount - 1 ? round($essayTotalPoints - ($essayPoint * max(0, $essayCount - 1)), 4) : $essayPoint; $exam->questions()->create(['lms_lesson_id'=>null,'paper_number'=>$data['paper_number'],'question_number'=>++$number,'question_type'=>'essay','content'=>$q->content,'options'=>$q->options,'answer'=>$q->answer,'points'=>$point,'paper_status'=>'APPROVED']); }
            $drawCode = 'RT-'.now()->format('YmdHis').'-'.random_int(100,999);
            $draw = EssayExamDraw::create(['essay_exam_id'=>$exam->id,'paper_number'=>$data['paper_number'],'draw_code'=>$drawCode,'qr_code'=>'QR-'.$drawCode,'draw_type'=>$data['draw_type'],'class_name'=>$class->name,'exam_date'=>$data['exam_date'] ?? null,'exam_time'=>$data['exam_time'] ?? null,'location'=>$data['location'] ?? null,'drawn_by_user_id'=>$request->user()->id,'drawn_at'=>now(),'printed_at'=>now()]);
            return redirect()->route('essay-exams.draw.print', ['draw' => $draw->id, 'auto' => 1]);
        }
        $printedTypes = EssayExamDraw::where('class_name', $class->name)->where('drawn_at','>=',now()->subDays(3))
            ->whereHas('exam', fn($q) => $q->where('subject_id', $data['subject_id'])->where('exam_type', $data['exam_type']))
            ->pluck('draw_type')->unique()->values();
        abort_if($printedTypes->contains($data['draw_type']), 422, 'Loại phiếu này đã được rút cho lớp và môn trong 3 ngày gần đây.');
        $used = EssayExamDraw::where('class_name', $class->name)->where('drawn_at','>=',now()->subDays(3))
            ->whereHas('exam', fn($q) => $q->where('subject_id',$data['subject_id'])->where('exam_type', $data['exam_type']))
            ->get(['essay_exam_id','paper_number']);
        $usedKeys = $used->map(fn ($d) => $d->essay_exam_id.':'.$d->paper_number)->all();
        $pairs = EssayExam::with('questions')->where('subject_id',$data['subject_id'])->where('class_id',$class->id)->where('exam_type',$data['exam_type'])->whereHas('questions', fn($q) => $q->where('paper_status','APPROVED'))->get()
            ->flatMap(fn ($e) => $e->questions->where('paper_status','APPROVED')->pluck('paper_number')->unique()->map(fn ($p) => [$e, (int)$p]))
            ->reject(fn ($pair) => in_array($pair[0]->id.':'.$pair[1], $usedKeys, true))->values();
        $picked = $pairs->isEmpty() ? null : $pairs->random();
        $exam = $picked[0] ?? null;
        $paperNumber = $picked[1] ?? 1;
        abort_unless($exam, 422, 'Không còn đề đã duyệt phù hợp để rút.');
        $drawCode = 'RT-'.now()->format('YmdHis').'-'.random_int(100,999);
        $draw = EssayExamDraw::create(['essay_exam_id'=>$exam->id,'paper_number'=>$paperNumber,'draw_code'=>$drawCode,'qr_code'=>'QR-'.$drawCode,'draw_type'=>$data['draw_type'],'class_name'=>$class->name,'exam_date'=>$data['exam_date'] ?? null,'exam_time'=>$data['exam_time'] ?? null,'location'=>$data['location'] ?? null,'drawn_by_user_id'=>$request->user()->id,'drawn_at'=>now(),'printed_at'=>now()]);
        $hasBoth = $data['draw_type'] === 'ODD' && EssayExamDraw::where('class_name', $class->name)
            ->where('drawn_at', '>=', now()->subDays(3))
            ->whereHas('exam', fn($q) => $q->where('subject_id', $data['subject_id'])->where('exam_type', $data['exam_type']))
            ->whereIn('draw_type', ['EVEN','ODD'])->distinct('draw_type')->count('draw_type') === 2;
        return redirect()->route('essay-exams.draw.print', ['draw' => $draw->id, 'auto' => 1]);
    }

    public function printDraw(Request $request, EssayExamDraw $draw): View
    {
        $draw->load(['exam.subject','exam.questions']);
        $questions = $draw->question_ids
            ? EssayExamQuestion::whereIn('id',$draw->question_ids)->get()->sortBy(fn($q)=>array_search($q->id,$draw->question_ids,true))->values()
            : $draw->exam->questions->where('paper_number',(int)$draw->paper_number)->values();
        abort_unless($questions->isNotEmpty(), 422, 'Lượt rút này không còn câu hỏi để in.');
        $withAnswers = $request->boolean('answers');
        $autoPrint = $request->boolean('auto');
        return view('essay-exam::print-draw', compact('draw','questions','withAnswers','autoPrint'));
    }

    public function create(): View {
        $user = request()->user();
        $isInstructor = $user?->user_type === 'instructor' || $user?->hasRole('instructor');
        $instructorId = $user?->instructor_id ?: Instructor::query()->where(function ($q) use ($user) {
            $q->where('email', $user?->email)->orWhere('name', $user?->name);
        })->value('id');
        $assignments = $instructorId ? ScheduleDetail::with(['subject','trainingSchedule.class'])
            ->where('instructor_id', $instructorId)->get() : collect();
        $assignedSubjectIds = $instructorId ? DB::table('teaching_assignment')->where('instructor_id',$instructorId)->pluck('subject_id') : collect();
        $subjectIds = $assignments->pluck('subject_id')->merge($assignedSubjectIds)->unique();
        $subjects = $isInstructor ? Subject::active()->whereIn('id', $subjectIds)->orderBy('name')->get() : Subject::active()->orderBy('name')->get();
        $scheduleClassIds = $assignments->pluck('trainingSchedule.class_id')->filter()->unique();
        $courseClassIds = $instructorId
            ? DB::table('lms_courses')->where('instructor_id',$instructorId)->pluck('class_id')
            : collect();
        if ($instructorId) {
            $subjectIds = $subjectIds->merge(DB::table('lms_courses')->where('instructor_id',$instructorId)->pluck('subject_id'))->unique();
            $subjects = Subject::active()->whereIn('id',$subjectIds)->orderBy('name')->get();
        }
        $classesByInstructor = $instructorId
            ? ClassModel::active()->where(function ($q) use ($instructorId, $scheduleClassIds, $courseClassIds) {
                $q->where('instructor_id',$instructorId)
                    ->orWhereIn('id',$scheduleClassIds->all())
                    ->orWhereIn('id',$courseClassIds->all());
            })->get()
            : collect();
        $classes = $assignments->map(fn ($a) => ['subject_id'=>$a->subject_id,'class_id'=>$a->trainingSchedule?->class_id,'label'=>$a->trainingSchedule?->class?->name ?: $a->trainingSchedule?->class_code])
            ->filter(fn ($x) => $x['class_id'])->unique(fn ($x) => $x['subject_id'].':'.$x['class_id'])->values();
        foreach ($assignedSubjectIds as $sid) {
            foreach ($classesByInstructor as $class) {
                $classes->push(['subject_id'=>$sid,'class_id'=>$class->id,'label'=>$class->name ?: $class->code]);
            }
        }
        foreach ($courseClassIds as $classId) {
            foreach (DB::table('lms_courses')->where('instructor_id',$instructorId)->where('class_id',$classId)->pluck('subject_id') as $sid) {
                $class = $classesByInstructor->firstWhere('id',$classId);
                if ($class) $classes->push(['subject_id'=>$sid,'class_id'=>$class->id,'label'=>$class->name ?: $class->code]);
            }
        }
        $classes = $classes->unique(fn ($x) => $x['subject_id'].':'.$x['class_id'])->values();
        if (!$isInstructor) $classes = ClassModel::active()->get()->map(fn ($c) => ['subject_id'=>null,'class_id'=>$c->id,'label'=>$c->name ?: $c->code]);
        $classes = $classes->groupBy('class_id')->map(function ($rows) {
            $first = $rows->first();
            return ['class_id' => $first['class_id'], 'subject_ids' => $rows->pluck('subject_id')->filter()->unique()->values()->all(), 'label' => $first['label']];
        })->values();
        $classSpecializations = ClassModel::whereIn('id', $classes->pluck('class_id')->all())->pluck('specialization_id', 'id');
        $classes = $classes->map(fn ($item) => $item + ['specialization_id' => $classSpecializations[$item['class_id']] ?? null])->values();
        $specializations = \Modules\Specialization\Models\Specialization::query()
            ->whereIn('id', $subjects->pluck('specialization_id')->filter()->unique()->all())
            ->orderBy('name')->get(['id','code','name']);
        $lessons = LmsLesson::query()->with('course:id,subject_id,class_id')->whereHas('course', function ($q) use ($subjectIds, $classesByInstructor) {
            $q->whereIn('subject_id', $subjectIds->all())->when($classesByInstructor->isNotEmpty(), fn ($x) => $x->whereIn('class_id', $classesByInstructor->pluck('id')->all()));
        })->orderBy('sort_order')->get(['id','lms_course_id','title']);
        $lessonOptions = $lessons->map(fn ($lesson) => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'subject_id' => $lesson->course?->subject_id,
            'class_id' => $lesson->course?->class_id,
        ])->values()->all();
        $defaultAcademicYear = AcademicYear::query()->where('is_current', true)->where('is_active', true)->first()
            ?: AcademicYear::query()->where('is_active', true)->orderByDesc('start_year')->orderByDesc('id')->first();
        $availableClassIds = $classes->pluck('class_id')->filter()->unique()->values();
        $availableSubjectIds = $subjects->pluck('id')->filter()->unique()->values();
        $assignedPairs = $classes->flatMap(fn ($class) => collect($class['subject_ids'])->map(fn ($subjectId) => $class['class_id'].':'.$subjectId))->all();
        $curriculumOptions = LmsCourse::query()
            ->with(['academicYear:id,code','subject:id,semester,specialization_id'])
            ->whereNotNull('class_id')
            ->whereNotNull('subject_id')
            ->when($availableClassIds->isNotEmpty(), fn ($q) => $q->whereIn('class_id', $availableClassIds->all()))
            ->when($availableSubjectIds->isNotEmpty(), fn ($q) => $q->whereIn('subject_id', $availableSubjectIds->all()))
            ->get(['id','class_id','subject_id','academic_year_id','term'])
            ->map(fn ($course) => [
            'class_id' => (int) $course->class_id,
            'subject_id' => (int) $course->subject_id,
            'specialization_id' => (int) ($course->subject?->specialization_id ?: 0),
            'academic_year' => $course->academicYear?->code ?: $defaultAcademicYear?->code,
            'semester' => $course->term ?: $course->subject?->semester,
        ])
            ->filter(fn ($item) => ! $isInstructor || in_array($item['class_id'].':'.$item['subject_id'], $assignedPairs, true))
            ->filter(fn ($item) => $item['academic_year'] && $item['semester'])
            ->unique(fn ($item) => $item['class_id'].':'.$item['subject_id'].':'.$item['academic_year'].':'.$item['semester'])
            ->values()->all();
        $academicYears = AcademicYear::query()->where('is_active', true)->orderByDesc('start_year')->orderByDesc('id')->get(['id','code','name']);
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::query()->orderByDesc('start_year')->orderByDesc('id')->get(['id','code','name']);
        }
        return view('essay-exam::create', compact('subjects','classes','specializations','lessons','lessonOptions','curriculumOptions','academicYears'));
    }

    private function curriculumMetadata(int $classId, int $subjectId, ?string $academicYear = null, ?string $semester = null): ?array
    {
        $courses = LmsCourse::query()->with(['academicYear:id,code','subject:id,semester'])
            ->where('class_id', $classId)->where('subject_id', $subjectId)
            ->latest('id')->get();

        $defaultAcademicYear = AcademicYear::query()->where('is_current', true)->where('is_active', true)->value('code')
            ?: AcademicYear::query()->where('is_active', true)->orderByDesc('start_year')->orderByDesc('id')->value('code');
        $rows = $courses->map(fn ($course) => [
            'academic_year' => $course?->academicYear?->code ?: $defaultAcademicYear,
            'semester' => $course?->term ?: $course?->subject?->semester,
        ])->filter(fn ($item) => $item['academic_year'] && $item['semester'])->values();

        if ($academicYear || $semester) {
            $rows = $rows->filter(fn ($item) => (! $academicYear || $item['academic_year'] === $academicYear)
                && (! $semester || $item['semester'] === $semester))->values();
        }

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->first();
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate(['import_file'=>'nullable|file|extensions:txt,csv,tsv,doc,docx|max:10240','answer_file'=>'nullable|file|extensions:txt,csv,tsv,doc,docx|max:10240','import_mode'=>'nullable|in:question,answer','import_title'=>'nullable|string|max:255','import_subject_id'=>'required|exists:subjects,id','import_lesson_id'=>'nullable|integer|exists:lms_lessons,id','duration_minutes'=>'required|integer|min:1|max:600','academic_year'=>'required|string|max:20','semester'=>'required|string|max:30','difficulty'=>'required|in:Dễ,Vừa,Khó','exam_type'=>'required|in:Tự luận,Tích hợp']);
        $data['import_mode'] = $data['import_mode'] ?? 'question';
        $data['import_specialization_id'] = $request->validate(['import_specialization_id' => 'required|exists:specializations,id'])['import_specialization_id'];
        abort_unless(preg_match('/^(?:semester_[1-7]|summer)$/', (string) $data['semester']) === 1, 422, 'Học kỳ phải là Học kỳ 1-7 hoặc Học kỳ hè.');
        if (in_array($data['exam_type'], ['Trắc nghiệm', 'Tích hợp'], true)) {
            abort_unless(! empty($data['import_lesson_id']), 422, 'Import dạng trắc nghiệm/tích hợp bắt buộc phải chọn bài học.');
        }
        $request->validate(['import_class_id'=>'required|exists:classes,id']);
        $subject = Subject::findOrFail($data['import_subject_id']);
        $class = ClassModel::findOrFail($request->integer('import_class_id'));
        $curriculum = $this->curriculumMetadata((int) $class->id, (int) $subject->id, $data['academic_year'], $data['semester']);
        abort_unless($curriculum, 422, 'Lớp/môn đã chọn chưa được mở trong năm học và học kỳ này.');
        $data['academic_year'] = $curriculum['academic_year'];
        $data['semester'] = $curriculum['semester'];
        abort_unless((int) $subject->specialization_id === (int) $data['import_specialization_id'], 422, 'Môn học không thuộc ngành đã chọn.');
        abort_unless((int) $class->specialization_id === (int) $data['import_specialization_id'], 422, 'Lớp không thuộc ngành đã chọn.');
        if (! empty($data['import_lesson_id'])) {
            abort_unless(LmsLesson::whereKey($data['import_lesson_id'])->whereHas('course', fn ($q) => $q->where('subject_id', $data['import_subject_id'])->where('class_id', $request->integer('import_class_id')))->exists(), 422, 'Bài học không thuộc môn/lớp đã chọn.');
        }
        $request->validate(['import_files'=>'nullable|array','import_files.*'=>'file|extensions:txt,csv,tsv,doc,docx|max:10240']);
        $user = $request->user();
        if ($request->hasFile('import_files')) {
            $request->files->set('import_file', $request->file('import_files')[0]);
        }
        $linkedInstructorId = $user?->instructor_id ?: Instructor::query()->where(function ($q) use ($user) {
            $q->where('email', $user?->email)->orWhere('name', $user?->name);
        })->value('id');
        if (($user?->user_type === 'instructor' || $user?->hasRole('instructor')) && !$user->hasRole('super-admin')) {
            abort_unless($linkedInstructorId, 403, 'Tài khoản chưa liên kết hồ sơ giảng viên.');
            $hasSchedule = ScheduleDetail::where('instructor_id',$linkedInstructorId)->where('subject_id',$data['import_subject_id'])->whereHas('trainingSchedule', fn ($q) => $q->where('class_id',$request->integer('import_class_id')))->exists();
            $hasTeachingAssignment = DB::table('teaching_assignment')->where('instructor_id',$linkedInstructorId)->where('subject_id',$data['import_subject_id'])->exists() && ClassModel::where('id',$request->integer('import_class_id'))->where('instructor_id',$linkedInstructorId)->exists();
             abort_unless($hasSchedule || $hasTeachingAssignment, 403, 'Bạn chưa được phân công môn/lớp này.');
        }
        if ($data['import_mode'] === 'answer') {
            $data['import_code'] = 'DA-'.strtoupper((string) Str::ulid());
            abort_unless($data['exam_type'] === 'Tích hợp', 422, 'Chỉ dạng đề tích hợp mới được import đáp án riêng.');
            abort_unless($request->hasFile('answer_file'), 422, 'Hãy chọn file đáp án.');
            $answers = $this->parseAnswerRows($this->readImportText($request->file('answer_file')));
            abort_unless($answers, 422, 'Không nhận diện được đáp án trong file.');
            $set = DB::transaction(function () use ($answers, $data, $user, $request) {
                $code = trim($data['import_code']);
                $baseCode = $code;
                $version = 2;
                while (IntegratedAnswerSet::where('code', $code)->exists()) $code = $baseCode.'-B'.$version++;
                $set = IntegratedAnswerSet::create(['code'=>$code,'title'=>$data['import_title'] ?? 'Đáp án đề tích hợp','subject_id'=>$data['import_subject_id'],'status'=>'DRAFT','created_by_user_id'=>$user->id,'created_by_username'=>$user->email,'created_by_display_name'=>$user->name]);
                foreach ($answers as $key => $answer) {
                    [$paper, $question] = array_map('intval', explode(':', $key, 2));
                    $set->items()->create(['paper_number'=>$paper ?: 1,'question_number'=>$question,'answer'=>$this->ensureAnswerHasPoint($answer, 1),'points'=>1]);
                }
                $this->logIntegratedAnswer($set, 'IMPORT', null, 'DRAFT', $user, 'Import từ '.$request->file('answer_file')->getClientOriginalName());
                return $set;
            });
            return redirect()->route('essay-exams.integrated-answers.show', $set)->with('success', 'Đã import file đáp án vào bộ '.$set->code.'.');
        }
        abort_unless($request->hasFile('import_file') || $request->hasFile('import_files'), 422, 'Hãy chọn file câu hỏi.');
        if ($request->hasFile('import_files')) {
            return $this->importMultipleFiles($request, $data, $user);
        }
        $importDocument = $request->hasFile('import_file') ? $this->storeImportDocumentAsPdf($request->file('import_file'), (string) Str::ulid()) : [];
        $text = $request->hasFile('import_file') ? $this->readImportText($data['import_file']) : '';
        $rows = $this->parseImportRows($text);
        $questionTypes = collect($rows)->pluck('question_type')->filter()->unique();
        if ($questionTypes->contains('multiple_choice') && $questionTypes->contains('essay')) {
            $data['exam_type'] = 'Tích hợp';
        }
        abort_unless($rows, 422, 'Không nhận diện được bảng câu hỏi/đáp án. Hãy dùng mẫu TSV: Đề số | Câu hỏi | Nội dung | Đáp án | Điểm.');
        $data['import_class_id'] = $request->integer('import_class_id');
        // Một lần import = một bộ đề; mỗi đề số được phân biệt bằng paper_number.
        $created = DB::transaction(function () use ($rows, $data, $user, $importDocument, $class) {
            $exam = EssayExam::create(['code'=>$this->nextExamCode(),'title'=>$this->examTitle((int) $data['import_subject_id'], (int) $class->id),'subject_id'=>$data['import_subject_id'],'class_id'=>$class->id,'duration_minutes'=>$data['duration_minutes'],'academic_year'=>$data['academic_year'],'semester'=>$data['semester'],'difficulty'=>$data['difficulty'],'exam_type'=>$data['exam_type'],'created_by_user_id'=>$user->id,'created_by_username'=>$user->email,'created_by_display_name'=>$user->name,'note'=>'Import bộ đề'] + $importDocument);
            $exam->update(['class_id' => $data['import_class_id']]);
            foreach (collect($rows)->groupBy('paper') as $paper => $questions) {
                foreach ($questions as $i => $q) $exam->questions()->create(['lms_lesson_id'=>$data['import_lesson_id'] ?? null,'paper_number'=>(int)$paper,'question_number'=>$i+1,'question_type'=>$q['question_type'] ?? 'essay','content'=>$q['content'],'options'=>$q['options'] ?? null,'answer'=>$q['answer'],'points'=>$q['points']]);
            }
            if (in_array($data['exam_type'], ['Trắc nghiệm','Tích hợp'], true)) $this->syncLmsMultipleChoice($rows, $data, $user);
            $this->log($exam,'IMPORT',null,'DRAFT',$user);
            return $exam;
        });
        return redirect()->route('essay-exams.show',$created)->with('success','Đã import bộ đề gồm '.collect($rows)->pluck('paper')->unique()->count().' đề số và '.count($rows).' câu hỏi.');
    }

    public function previewImport(Request $request): View
    {
        $request->validate(['import_specialization_id' => 'required|exists:specializations,id']);
        $data = $request->validate(['import_file'=>'required|file|extensions:txt,csv,tsv,doc,docx|max:10240','import_title'=>'nullable|string|max:255','import_subject_id'=>'required|exists:subjects,id','import_lesson_id'=>'nullable|integer|exists:lms_lessons,id','import_class_id'=>'required|exists:classes,id','duration_minutes'=>'required|integer|min:1|max:600','academic_year'=>'required|string|max:20','semester'=>'required|string|max:30','difficulty'=>'required|in:Dễ,Vừa,Khó','exam_type'=>'required|in:Tự luận,Tích hợp']);
        abort_unless($data['exam_type'] === 'Tự luận' || ! empty($data['import_lesson_id']), 422, 'Import dạng trắc nghiệm/tích hợp bắt buộc phải chọn bài học.');
        $data['import_specialization_id'] = $request->integer('import_specialization_id');
        abort_unless(preg_match('/^(?:semester_[1-7]|summer)$/', (string) $data['semester']) === 1, 422, 'Học kỳ phải là Học kỳ 1-7 hoặc Học kỳ hè.');
        $curriculum = $this->curriculumMetadata((int) $data['import_class_id'], (int) $data['import_subject_id'], $data['academic_year'], $data['semester']);
        abort_unless($curriculum, 422, 'Lớp/môn đã chọn chưa được mở trong năm học và học kỳ này.');
        $data['academic_year'] = $curriculum['academic_year'];
        $data['semester'] = $curriculum['semester'];
        $importDocument = $this->storeImportDocumentAsPdf($data['import_file'], (string) Str::ulid());
        $data = array_merge($data, $importDocument);
        $request->session()->put('essay_exam_import_preview', [
            'user_id' => $request->user()->id,
            'source_document_path' => $importDocument['source_document_path'],
            'source_pdf_path' => $importDocument['source_pdf_path'],
        ]);
        $rows = $this->parseImportRows($this->readImportText($data['import_file']));
        abort_unless($rows, 422, 'Không nhận diện được câu hỏi trong file.');
        $papers = collect($rows)->groupBy('paper')->map(fn ($qs,$paper) => ['paper'=>(int)$paper,'questions'=>$qs->count(),'points'=>$qs->sum('points')])->values();
        return view('essay-exam::import-preview', compact('data','papers','rows'));
    }

    public function integratedAnswers(Request $request): View
    {
        $query = IntegratedAnswerSet::with(['subject','items']);
        $user = $request->user();
        if (! $user->can('essay-exams.index')) {
            $query->where('created_by_user_id', $user->id);
        } elseif ($user->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department'])) {
            $unitId = $user->unit_id ?: $user->instructor?->unit_id;
            $query->whereHas('creator', fn ($creator) => $creator->where('unit_id', $unitId ?: 0));
        }
        $sets = $query->latest()->paginate(20);
        return view('essay-exam::integrated-answers.index', compact('sets'));
    }

    public function integratedAnswerShow(Request $request, IntegratedAnswerSet $answerSet): View
    {
        abort_unless($this->canViewAnswerSet($request->user(), $answerSet), 403);
        $answerSet->load(['subject','items','logs']);
        $canReview = $this->canReviewAnswerSetStage($request->user(), $answerSet, $answerSet->status);
        return view('essay-exam::integrated-answers.show', compact('answerSet', 'canReview'));
    }

    private function canViewAnswerSet(User $user, IntegratedAnswerSet $answerSet): bool
    {
        if ((int) $answerSet->created_by_user_id === (int) $user->id) return true;
        if (! $user->can('essay-exams.index')) return false;
        if (! $user->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department'])) return true;
        $unitId = $user->unit_id ?: $user->instructor?->unit_id;

        return $unitId && (int) $answerSet->creator?->unit_id === (int) $unitId;
    }

    private function canReviewAnswerSetStage(User $user, IntegratedAnswerSet $answerSet, string $stage): bool
    {
        if (! in_array($stage, ['PENDING_DEPT', 'PENDING_EXAM_OFFICE', 'PENDING_BGH'], true)) return false;
        if ($user->hasAnyRole(['super-admin', 'system-manager', 'manager'])) return true;
        return match ($stage) {
            'PENDING_DEPT' => $user->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department']) && $this->canViewAnswerSet($user, $answerSet),
            'PENDING_EXAM_OFFICE' => $user->hasAnyRole(['training-office-manager', 'exam-manager', 'exam-office', 'testing-office']),
            'PENDING_BGH' => $user->hasAnyRole(['bgh', 'board-of-management', 'ban giám hiệu']),
        };
    }

    public function integratedAnswerSubmit(Request $request, IntegratedAnswerSet $answerSet): RedirectResponse
    {
        abort_unless((int) $answerSet->created_by_user_id === (int) $request->user()->id || $request->user()->hasRole('super-admin'), 403);
        abort_unless(in_array($answerSet->status, ['DRAFT','RETURNED'], true), 422, 'Bộ đáp án không còn ở trạng thái có thể gửi duyệt.');
        $from = $answerSet->status;
        $answerSet->update(['status'=>'PENDING_DEPT','return_note'=>null]);
        $this->logIntegratedAnswer($answerSet, 'SUBMIT', $from, 'PENDING_DEPT', $request->user());
        return back()->with('success', 'Đã gửi bộ đáp án đến khoa duyệt.');
    }

    public function integratedAnswerApprove(Request $request, IntegratedAnswerSet $answerSet): RedirectResponse
    {
        $stage = $request->input('stage', 'PENDING_DEPT');
        abort_unless($answerSet->status === $stage, 422, 'Trạng thái bộ đáp án đã thay đổi.');
        $user = $request->user();
        abort_unless($this->canReviewAnswerSetStage($user, $answerSet, $stage), 403, 'Bạn không có quyền duyệt bộ đáp án ở cấp này.');
        $next = match ($stage) { 'PENDING_DEPT'=>'PENDING_EXAM_OFFICE', 'PENDING_EXAM_OFFICE'=>'PENDING_BGH', 'PENDING_BGH'=>'APPROVED', default=>null };
        abort_unless($next, 422, 'Cấp duyệt không hợp lệ.');
        $answerSet->update(['status'=>$next,'approved_by_user_id'=>$next === 'APPROVED' ? $user->id : null,'approved_at'=>$next === 'APPROVED' ? now() : null]);
        $this->logIntegratedAnswer($answerSet, 'APPROVE', $stage, $next, $user);
        return back()->with('success', 'Đã chuyển bộ đáp án sang trạng thái '.$next.'.');
    }

    public function integratedAnswerReturn(Request $request, IntegratedAnswerSet $answerSet): RedirectResponse
    {
        abort_unless(in_array($answerSet->status, ['PENDING_DEPT','PENDING_EXAM_OFFICE','PENDING_BGH'], true), 422, 'Bộ đáp án không ở trạng thái có thể trả lại.');
        abort_unless($this->canReviewAnswerSetStage($request->user(), $answerSet, $answerSet->status), 403);
        $data = $request->validate(['return_note'=>'required|string|max:2000']);
        $from = $answerSet->status;
        $answerSet->update(['status'=>'RETURNED','return_note'=>$data['return_note']]);
        $this->logIntegratedAnswer($answerSet, 'RETURN', $from, 'RETURNED', $request->user(), $data['return_note']);
        return back()->with('success', 'Đã trả lại bộ đáp án.');
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $data = $request->validate(['rows_json'=>'required|string','import_title'=>'nullable|string|max:255','import_subject_id'=>'required|exists:subjects,id','import_lesson_id'=>'nullable|integer|exists:lms_lessons,id','import_class_id'=>'required|exists:classes,id','duration_minutes'=>'required|integer|min:1|max:600','academic_year'=>'required|string|max:20','semester'=>'required|string|max:30','difficulty'=>'required|in:Dễ,Vừa,Khó','exam_type'=>'required|in:Tự luận,Tích hợp','source_document_path'=>'nullable|string|max:255','source_pdf_path'=>'nullable|string|max:255','source_original_name'=>'nullable|string|max:255']);
        $preview = $request->session()->get('essay_exam_import_preview');
        abort_unless(is_array($preview)
            && (int) ($preview['user_id'] ?? 0) === (int) $request->user()->id
            && ($preview['source_document_path'] ?? null) === ($data['source_document_path'] ?? null)
            && ($preview['source_pdf_path'] ?? null) === ($data['source_pdf_path'] ?? null), 422, 'Phiên xem trước import không còn hợp lệ.');
        $rows = json_decode($data['rows_json'], true);
        $data['import_specialization_id'] = $request->validate(['import_specialization_id' => 'required|exists:specializations,id'])['import_specialization_id'];
        abort_unless(preg_match('/^(?:semester_[1-7]|summer)$/', (string) $data['semester']) === 1, 422, 'Học kỳ phải là Học kỳ 1-7 hoặc Học kỳ hè.');
        $subject = Subject::findOrFail($data['import_subject_id']);
        $class = ClassModel::findOrFail($data['import_class_id']);
        abort_unless((int) $subject->specialization_id === (int) $data['import_specialization_id'], 422, 'Môn học không thuộc ngành đã chọn.');
        abort_unless((int) $class->specialization_id === (int) $data['import_specialization_id'], 422, 'Lớp không thuộc ngành đã chọn.');
        $curriculum = $this->curriculumMetadata((int) $data['import_class_id'], (int) $data['import_subject_id'], $data['academic_year'], $data['semester']);
        abort_unless($curriculum, 422, 'Lớp/môn đã chọn chưa được mở trong năm học và học kỳ này.');
        $data['academic_year'] = $curriculum['academic_year'];
        $data['semester'] = $curriculum['semester'];
        abort_unless(is_array($rows) && count($rows), 422, 'Dữ liệu xem trước không hợp lệ.');
        abort_unless($data['exam_type'] === 'Tự luận' || ! empty($data['import_lesson_id']), 422, 'Import dạng trắc nghiệm/tích hợp bắt buộc phải chọn bài học.');
        $user = $request->user();
        $linkedInstructorId = $user?->instructor_id ?: Instructor::query()->where(function ($q) use ($user) { $q->where('email', $user?->email)->orWhere('name', $user?->name); })->value('id');
        if (($user?->user_type === 'instructor' || $user?->hasRole('instructor')) && !$user->hasRole('super-admin')) {
            $hasSchedule = $linkedInstructorId && ScheduleDetail::where('instructor_id',$linkedInstructorId)->where('subject_id',$data['import_subject_id'])->whereHas('trainingSchedule', fn ($q) => $q->where('class_id',$data['import_class_id']))->exists();
            $hasTeachingAssignment = $linkedInstructorId && DB::table('teaching_assignment')->where('instructor_id',$linkedInstructorId)->where('subject_id',$data['import_subject_id'])->exists() && ClassModel::where('id',$data['import_class_id'])->where('instructor_id',$linkedInstructorId)->exists();
            $hasLmsCourse = $linkedInstructorId && DB::table('lms_courses')->where('instructor_id',$linkedInstructorId)->where('class_id',$data['import_class_id'])->where('subject_id',$data['import_subject_id'])->exists();
             abort_unless($hasSchedule || $hasTeachingAssignment || $hasLmsCourse, 403, 'Bạn chưa được phân công môn/lớp này.');
        }
        $created = DB::transaction(function () use ($rows, $data, $user) {
            $exam = EssayExam::create(['code'=>$this->nextExamCode(),'title'=>$this->examTitle((int) $data['import_subject_id'], (int) $data['import_class_id']),'subject_id'=>$data['import_subject_id'],'class_id'=>$data['import_class_id'],'duration_minutes'=>$data['duration_minutes'],'academic_year'=>$data['academic_year'],'semester'=>$data['semester'],'difficulty'=>$data['difficulty'],'exam_type'=>$data['exam_type'],'created_by_user_id'=>$user->id,'created_by_username'=>$user->email,'created_by_display_name'=>$user->name,'note'=>'Import bộ đề','source_document_path'=>$data['source_document_path'] ?? null,'source_pdf_path'=>$data['source_pdf_path'] ?? null,'source_original_name'=>$data['source_original_name'] ?? null]);
             foreach (collect($rows)->groupBy('paper') as $paper => $questions) foreach ($questions as $i => $q) $exam->questions()->create(['lms_lesson_id'=>$data['import_lesson_id'] ?? null,'paper_number'=>(int)$paper,'question_number'=>$i+1,'question_type'=>$q['question_type'] ?? 'essay','content'=>$q['content'],'options'=>$q['options'] ?? null,'answer'=>$q['answer'] ?? '','points'=>$q['points'] ?? 1]);
             if (in_array($data['exam_type'], ['Trắc nghiệm','Tích hợp'], true)) $this->syncLmsMultipleChoice($rows, $data, $user);
            $this->log($exam,'IMPORT',null,'DRAFT',$user); return $exam;
        });
        $request->session()->forget('essay_exam_import_preview');
        return redirect()->route('essay-exams.show',$created)->with('success','Đã xác nhận import bộ đề.');
    }

    /** Đẩy câu trắc nghiệm từ đề import sang NHCH LMS theo môn/bài/lớp. */
    private function syncLmsMultipleChoice(array $rows, array $data, $user): void
    {
        $course = LmsCourse::query()->where('subject_id', $data['import_subject_id'])
            ->where('class_id', $data['import_class_id'])->first();
        if (! $course) return;
        $bank = LmsQuestionBank::firstOrCreate(
            ['lms_course_id' => $course->id],
            ['title' => 'Ngân hàng trắc nghiệm - '.($course->subject?->name ?: $data['import_subject_id']), 'description' => 'Tự động tạo từ Import đề', 'created_by' => $user->id]
        );
        $order = (int) $bank->questions()->max('sort_order');
        foreach (array_values(array_filter($rows, fn ($row) => ($row['question_type'] ?? '') === 'multiple_choice')) as $row) {
            $options = array_values($row['options'] ?? []);
            if (count($options) < 2) continue;
            $answer = strtoupper(trim((string) ($row['answer'] ?? '')));
            if (preg_match('/^[A-D]$/', $answer)) $answer = ord($answer) - ord('A');
            if ($answer === false || $answer === null || $answer === '') $answer = '0';
            $bank->questions()->create([
                'lms_lesson_id' => $data['import_lesson_id'] ?? null,
                'type' => 'mcq', 'stem' => $row['content'], 'options' => $options,
                'correct_answer' => (string) $answer, 'points' => $row['points'] ?? 1, 'sort_order' => ++$order,
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['subject_id'=>'required|exists:subjects,id','class_id'=>'required|exists:classes,id','duration_minutes'=>'required|integer|min:1|max:600','academic_year'=>'required|string|max:20','semester'=>'required|string|max:30','difficulty'=>'required|in:Dễ,Vừa,Khó','exam_type'=>'required|in:Tự luận,Tích hợp','note'=>'nullable|string','questions'=>'required|array|min:1','questions.*.content'=>'required|string','questions.*.answer'=>'nullable|string','questions.*.points'=>'required|numeric|min:0']);
        $user = $request->user();
        $class = ClassModel::findOrFail($data['class_id']);
        abort_unless(preg_match('/^(?:semester_[1-7]|summer)$/', (string) $data['semester']) === 1, 422, 'Học kỳ phải là Học kỳ 1-7 hoặc Học kỳ hè.');
        $curriculum = $this->curriculumMetadata((int) $class->id, (int) $data['subject_id'], $data['academic_year'], $data['semester']);
        abort_unless($curriculum, 422, 'Lớp/môn đã chọn chưa được mở trong năm học và học kỳ này.');
        $data['academic_year'] = $curriculum['academic_year'];
        $data['semester'] = $curriculum['semester'];
        $instructorId = $user?->instructor_id ?: Instructor::query()->where(function ($q) use ($user) {
            $q->where('email', $user?->email)->orWhere('name', $user?->name);
        })->value('id');
        if (($user?->user_type === 'instructor' || $user?->hasRole('instructor')) && !$user->hasRole('super-admin')) {
            $hasSchedule = $instructorId && ScheduleDetail::where('instructor_id', $instructorId)->where('subject_id', $data['subject_id'])->whereHas('trainingSchedule', fn ($q) => $q->where('class_id', $class->id))->exists();
            $hasTeachingAssignment = $instructorId && DB::table('teaching_assignment')->where('instructor_id', $instructorId)->where('subject_id', $data['subject_id'])->exists() && $class->instructor_id === $instructorId;
            $hasLmsCourse = $instructorId && DB::table('lms_courses')->where('instructor_id',$instructorId)->where('class_id',$class->id)->where('subject_id',$data['subject_id'])->exists();
            abort_unless($hasSchedule || $hasTeachingAssignment || $hasLmsCourse, 403, 'Bạn chưa được phân công môn/lớp này.');
        }
        $exam = DB::transaction(function () use ($data, $user) {
            $exam = EssayExam::create(['code'=>$this->nextExamCode(),'title'=>$this->examTitle((int) $data['subject_id'], (int) $data['class_id']),'subject_id'=>$data['subject_id'],'class_id'=>$data['class_id'],'duration_minutes'=>$data['duration_minutes'],'academic_year'=>$data['academic_year'],'semester'=>$data['semester'],'difficulty'=>$data['difficulty'],'exam_type'=>$data['exam_type'],'note'=>$data['note'] ?? null,'created_by_user_id'=>$user->id,'created_by_username'=>$user->email,'created_by_display_name'=>$user->name]);
            foreach ($data['questions'] as $number => $question) $exam->questions()->create(['question_number'=>$number+1,'question_type'=>$question['type'] ?? 'essay','content'=>$question['content'],'options'=>$question['options'] ?? null,'answer'=>$question['answer'] ?? null,'points'=>$question['points']]);
            $this->log($exam, 'CREATE', null, 'DRAFT', $user);
            return $exam;
        });
        return redirect()->route('essay-exams.show', $exam)->with('success','Đã tạo đề thi tự luận.');
    }

    public function show(Request $request, EssayExam $essayExam): View
    {
        $user = $request->user();
        $ownsExam = (int) $essayExam->created_by_user_id === (int) $user->id;
        $canViewAll = $user->can('essay-exams.index');
        if ($user->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department'])) {
            $unitId = $user->unit_id ?: $user->instructor?->unit_id;
            $canViewAll = $canViewAll && $unitId && (int) $essayExam->creator?->unit_id === (int) $unitId;
        }
        abort_unless($ownsExam || $canViewAll, 403);

        $exam = $essayExam->load(['subject','questions','logs']);
        if ($request->filled('paper')) $exam->setRelation('questions', $exam->questions->where('paper_number',(int)$request->paper)->values());
        if ($exam->locked) $exam->setRelation('questions', collect());
        return view('essay-exam::show', ['exam'=>$exam]);
    }

    public function sourcePdf(Request $request, EssayExam $essayExam)
    {
        $user = $request->user();
        $ownsExam = (int) $essayExam->created_by_user_id === (int) $user->id;
        $canReview = $this->canReviewExamStage($user, $essayExam, $essayExam->status);
        $isArchiveManager = $user->hasAnyRole(['super-admin', 'system-manager', 'manager', 'exam-manager', 'exam-office', 'testing-office']);
        abort_unless($essayExam->status === 'APPROVED' ? $isArchiveManager : ($ownsExam || $canReview || $isArchiveManager), 403);
        abort_unless($essayExam->source_pdf_path, 404);
        $disk = Storage::disk('local')->exists($essayExam->source_pdf_path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($essayExam->source_pdf_path), 404);

        return response()->file(Storage::disk($disk)->path($essayExam->source_pdf_path), ['Content-Type' => 'application/pdf']);
    }

    public function submit(Request $request, EssayExam $essayExam): RedirectResponse
    {
        abort_unless($essayExam->created_by_user_id === $request->user()->id || $request->user()->hasRole('super-admin'), 403, 'Bạn chỉ được gửi duyệt đề do mình soạn.');
        abort_if(! in_array($essayExam->status, ['DRAFT','RETURNED'], true), 422, 'Đề không thể gửi ở trạng thái hiện tại.');
        $essayExam->questions()->whereIn('paper_status',['DRAFT','RETURNED'])->update(['paper_status'=>'PENDING_DEPT']);
        $this->transition($essayExam, 'PENDING_DEPT', 'SUBMIT', $request->user());
        return back()->with('success','Đề đã được gửi chờ duyệt khoa.');
    }

    public function approve(Request $request, EssayExam $essayExam): RedirectResponse
    {
        $stage = (string) $request->input('stage', $essayExam->status);
        $paperNumbers = $request->input('paper_numbers', []);
        $available = $essayExam->questions()->select('paper_number')->distinct()->pluck('paper_number')->map(fn($v)=>(string)$v)->all();
        $paperNumbers = $essayExam->questions()->where('paper_status',$stage)->whereIn('paper_number', array_map('intval',(array)$paperNumbers))->pluck('paper_number')->unique()->map('strval')->all();
        abort_unless($paperNumbers, 422, 'Hãy chọn ít nhất một đề số để duyệt.');
        $next = match ($stage) { 'PENDING_DEPT' => 'PENDING_EXAM_OFFICE', 'PENDING_EXAM_OFFICE' => 'PENDING_BGH', 'PENDING_BGH' => 'APPROVED', default => null };
        abort_unless($next, 422, 'Đề không ở bước chờ duyệt.');
        $user = $request->user();
        abort_unless($this->canReviewExamStage($user, $essayExam, $stage), 403, 'Tài khoản không thuộc cấp duyệt hoặc đơn vị của bước này.');
        $willComplete = $essayExam->questions()
            ->where('paper_status', $stage)
            ->whereNotIn('paper_number', $paperNumbers)
            ->doesntExist();
        $signature = null;
        if ($stage === 'PENDING_BGH' && $willComplete) {
            $signature = $request->filled('signature_data')
                ? $this->captureDirectApprovalSignature((string) $request->input('signature_data'))
                : $this->approvalDigitalSignature($user);
        }
        $allApproved = DB::transaction(function () use ($essayExam, $stage, $paperNumbers, $next, $request, $signature): bool {
            $updated = $essayExam->questions()->where('paper_status', $stage)->whereIn('paper_number', $paperNumbers)->update(['paper_status' => $next]);
            abort_unless($updated > 0, 422, 'Đề đã được xử lý bởi tài khoản khác.');
            $allApproved = $essayExam->questions()->where('paper_status', '!=', 'APPROVED')->doesntExist();
            if ($allApproved) {
                $this->transition($essayExam, $next, 'APPROVE', $request->user(), 'Đề số: '.implode(', ', $paperNumbers));
                $this->notifyLowerReviewLevel($essayExam, $stage, 'đã duyệt', $request->user());
                if ($next === 'APPROVED') {
                    $essayExam->update([
                        'approved_by_user_id' => $request->user()->id,
                        'approved_at' => now(),
                        'locked' => true,
                        'approval_qr' => $essayExam->approval_qr ?: 'QR-EXAM-'.strtoupper(substr(hash('sha256',$essayExam->id.'|'.$essayExam->code.'|'.microtime(true)),0,24)),
                    ]);
                    if ($stage === 'PENDING_BGH' && $signature) {
                        $this->createApprovalDocument($essayExam->fresh(), $request->user(), $signature);
                    }
                }
            } else {
                $this->log($essayExam, 'APPROVE_PAPERS', $essayExam->status, $essayExam->status, $request->user(), 'Đề số: '.implode(', ', $paperNumbers));
            }

            return $allApproved;
        });
        if ($allApproved && $next === 'APPROVED') {
            $this->archiveImportedSources($essayExam, EssayExamApprovalDocument::where('essay_exam_id', $essayExam->id)->first());
        }
        return back()->with('success', 'Đã ghi nhận duyệt đề số: '.implode(', ', $paperNumbers).($allApproved ? ' — bộ đề đã chuyển bước.' : ' — các đề còn lại vẫn chờ duyệt.'));
    }

    private function approvalDigitalSignature(?User $user): array
    {
        abort_unless($user, 403);
        $signature = DigitalSignature::query()
            ->active()
            ->forUser((int) $user->id)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        abort_unless($signature && $signature->imageUrl(), 422, 'Tài khoản chưa có chữ ký số. Vui lòng cập nhật chữ ký số trên dashboard trước khi duyệt/in bản ký.');

        return [
            'method' => 'digital',
            'path' => $signature->image_path,
            'display_name' => $signature->display_name,
            'role_line1' => $signature->role_line1,
            'role_line2' => $signature->role_line2,
        ];
    }

    private function captureDirectApprovalSignature(string $signatureData): array
    {
        if (! preg_match('/^data:image\/png;base64,(.+)$/s', $signatureData, $match)) {
            abort(422, 'Chữ ký trực tiếp phải là ảnh PNG hợp lệ.');
        }
        $binary = base64_decode($match[1], true);
        abort_unless($binary !== false && strlen($binary) > 100, 422, 'Chữ ký trực tiếp chưa hợp lệ.');

        $path = 'essay-exam/signatures/'.now()->format('Y/m').'/direct-signature-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(5)).'.png';
        Storage::disk('local')->put($path, $binary);

        $user = auth()->user();

        return [
            'method' => 'direct',
            'path' => $path,
            'display_name' => $this->approvalSignerName($user),
            'role_line1' => 'HIỆU TRƯỞNG',
            'role_line2' => $this->approvalSignerRank($user),
        ];
    }

    private function createApprovalDocument(EssayExam $exam, User $user, array $signature): EssayExamApprovalDocument
    {
        $exam->loadMissing(['subject', 'class', 'questions']);
        $document = EssayExamApprovalDocument::firstOrNew(['essay_exam_id' => $exam->id]);
        $document->fill([
            'decision_code' => $document->decision_code ?: 'QD-'.now()->format('YmdHis').'-'.str_pad((string) $exam->id, 5, '0', STR_PAD_LEFT),
            'title' => $exam->title,
            'class_id' => $exam->class_id,
            'class_name' => $exam->class?->name,
            'subject_id' => $exam->subject_id,
            'subject_name' => $exam->subject?->name,
            'approved_by_user_id' => $user->id,
            'approver_name' => $user->name ?: $user->email,
            'approved_at' => now(),
            'signature_method' => $signature['method'],
            'signature_path' => $signature['path'],
            'status' => 'SENT_TO_EXAM_OFFICE',
            'sent_to_exam_office_at' => now(),
            'sent_to_exam_office_by_user_id' => $user->id,
        ]);
        $document->save();
        $document->load(['exam.subject', 'exam.class', 'exam.questions', 'subject', 'class', 'approver']);
        $html = view('essay-exam::approval-document', [
            'document' => $document,
            'signatureUrl' => $this->signatureDataUrl($signature['path']),
        ])->render();
        $path = 'essay-exam/approval-documents/'.$document->decision_code.'.html';
        Storage::disk('local')->put($path, $html);
        $printPath = $this->createSignedExamPdf($exam, $user, $signature) ?: $this->examApprovalPrintablePath($exam);
        $document->update(['document_path' => $printPath ?: $path]);

        $recipientIds = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['exam-manager', 'exam-office', 'testing-office']))->pluck('id');
        if ($recipientIds->isNotEmpty()) {
            SystemNotifier::deliver(
                userIds: $recipientIds,
                actor: $user,
                module: 'essay-exams',
                action: 'approval-document',
                title: 'Có văn bản phê duyệt đề thi mới',
                message: "Văn bản {$document->decision_code} của bộ đề {$exam->code} đã được BGH ký và chuyển xuống Ban Khảo thí.",
                url: route('essay-exams.approval-documents.show', $document, false),
                type: SystemNotifier::TYPE_SYSTEM_CHANGE,
                meta: ['essay_exam_id' => $exam->id, 'approval_document_id' => $document->id, 'status' => $document->status],
            );
        }
        return $document;
    }

    private function signatureDataUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (Storage::disk('local')->exists($path)) {
            return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
        }
        if (Storage::disk('public')->exists($path)) {
            return 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($path));
        }
        foreach ([public_path($path), public_path('images/'.$path), $path] as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return 'data:image/png;base64,'.base64_encode(file_get_contents($candidate));
            }
        }
        return null;
    }

    private function createSignedExamPdf(EssayExam $exam, User $user, array $signature): ?string
    {
        $sourcePath = $this->examApprovalPrintablePath($exam);
        if (! $sourcePath || ! class_exists(\Mpdf\Mpdf::class)) {
            return null;
        }

        $sourceDisk = Storage::disk('local')->exists($sourcePath) ? 'local' : 'public';
        $sourceAbsolutePath = Storage::disk($sourceDisk)->path($sourcePath);
        $signatureAbsolutePath = $this->signatureAbsolutePath($signature['path'] ?? null);
        if (! is_file($sourceAbsolutePath) || ! $signatureAbsolutePath) {
            return null;
        }

        $signedPath = 'essay-exam/signed-pdfs/'.now()->format('Y/m').'/signed-'.$exam->id.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.pdf';
        Storage::disk('local')->makeDirectory(dirname($signedPath));

        try {
            Storage::disk('local')->makeDirectory('mpdf-temp');
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'tempDir' => Storage::disk('local')->path('mpdf-temp'),
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'default_font' => 'dejavusans',
            ]);
            $mpdf->SetDisplayMode('fullpage');
            $pageCount = $mpdf->setSourceFile($sourceAbsolutePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $template = $mpdf->importPage($pageNo);
                $size = $mpdf->getTemplateSize($template);
                $mpdf->AddPageByArray([
                    'orientation' => $size['orientation'] ?? (($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P'),
                    'sheet-size' => [$size['width'], $size['height']],
                    'margin-left' => 0,
                    'margin-right' => 0,
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                ]);
                $mpdf->useTemplate($template, 0, 0, $size['width'], $size['height']);

                if ($pageNo === $pageCount) {
                    $this->writePrincipalSignatureBlock($mpdf, (float) $size['width'], (float) $size['height'], $signatureAbsolutePath, $user, $signature);
                }
            }

            $mpdf->Output(Storage::disk('local')->path($signedPath), \Mpdf\Output\Destination::FILE);
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }

        return $signedPath;
    }

    private function writePrincipalSignatureBlock(\Mpdf\Mpdf $mpdf, float $pageWidth, float $pageHeight, string $signaturePath, User $user, array $signature): void
    {
        $blockWidth = min(62.0, max(50.0, $pageWidth * 0.29));
        $x = max(12.0, $pageWidth - $blockWidth - 18.0);
        $y = max(12.0, $pageHeight - 43.0);

        $mpdf->SetFont('dejavusans', 'B', 11);
        $mpdf->SetXY($x, $y);
        $mpdf->Cell($blockWidth, 6, 'HIỆU TRƯỞNG', 0, 1, 'C');
        $mpdf->Image($signaturePath, $x + 14, $y + 7, $blockWidth - 28, 16, '', '', true, true);
        $mpdf->SetFont('dejavusans', 'B', 10);
        $mpdf->SetXY($x, $y + 28);
        $mpdf->Cell($blockWidth, 6, $this->approvalSignerBottomLine($user, $signature), 0, 1, 'C');
    }

    private function signatureAbsolutePath(?string $path): ?string
    {
        if (! $path) return null;
        foreach ([Storage::disk('local')->path($path), Storage::disk('public')->path($path), public_path($path), public_path('images/'.$path), $path] as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function approvalSignerBottomLine(User $user, array $signature): string
    {
        $rank = trim((string) ($signature['role_line2'] ?? '')) ?: $this->approvalSignerRank($user);
        $name = trim((string) ($signature['display_name'] ?? '')) ?: $this->approvalSignerName($user);
        return trim($rank.' '.$name) ?: 'Hiệu trưởng';
    }

    private function approvalSignerRank(?User $user): string
    {
        if (! $user) return '';
        $user->loadMissing(['militaryRank', 'position']);
        return trim((string) ($user->militaryRank?->abbreviation ?: $user->militaryRank?->name ?: $user->position?->name ?: ''));
    }

    private function approvalSignerName(?User $user): string
    {
        return trim((string) ($user?->name ?: $user?->email ?: ''));
    }

        /* legacy stray calls removed */

    public function returnExam(Request $request, EssayExam $essayExam): RedirectResponse
    {
        $data = $request->validate(['return_note'=>'required|string|max:2000']);
        $user = $request->user();
        $stage = $request->input('stage') ?: ($user?->hasAnyRole(['training-office-manager','exam-manager','exam-office','testing-office']) ? 'PENDING_EXAM_OFFICE' : ($user?->hasAnyRole(['bgh','board-of-management','ban giám hiệu']) ? 'PENDING_BGH' : 'PENDING_DEPT'));
        abort_unless($this->canReviewExamStage($user, $essayExam, $stage), 403, 'Tài khoản không thuộc cấp duyệt hoặc đơn vị của bước này.');
        abort_unless($essayExam->questions()->where('paper_status', $stage)->exists(), 422, 'Đề không còn chờ ở cấp duyệt này.');
        $essayExam->questions()->where('paper_status',$stage)->update(['paper_status'=>'RETURNED']);
        $this->transition($essayExam, 'RETURNED', 'RETURN', $request->user(), $data['return_note']);
        $this->notifyLowerReviewLevel($essayExam, $stage, 'đã trả lại', $user);
        $essayExam->update(['return_note'=>$data['return_note'],'locked'=>false]);
        return back()->with('success','Đã trả đề về người soạn.');
    }

    private function transition(EssayExam $exam, string $status, string $action, $user, ?string $note = null): void
    {
        $from = $exam->status;
        $exam->update(['status' => $status]);
        $this->log($exam, $action, $from, $status, $user, $note);
        $this->notifyApprovalStage($exam->fresh(), $status, $user);
    }

    private function canReviewExamStage(User $user, EssayExam $exam, string $stage): bool
    {
        if (! in_array($stage, ['PENDING_DEPT', 'PENDING_EXAM_OFFICE', 'PENDING_BGH'], true)) return false;
        if ($user->hasAnyRole(['super-admin', 'system-manager', 'manager'])) return true;

        return match ($stage) {
            'PENDING_DEPT' => $user->hasAnyRole(['faculty-manager', 'department-head', 'head-of-department'])
                && ($unitId = $user->unit_id ?: $user->instructor?->unit_id)
                && (int) $exam->creator?->unit_id === (int) $unitId,
            'PENDING_EXAM_OFFICE' => $user->hasAnyRole(['training-office-manager', 'exam-manager', 'exam-office', 'testing-office']),
            'PENDING_BGH' => $user->hasAnyRole(['bgh', 'board-of-management', 'ban giám hiệu']),
            default => false,
        };
    }

    private function notifyLowerReviewLevel(EssayExam $exam, string $reviewedStage, string $decision, User $actor): void
    {
        $lowerStage = match ($reviewedStage) {
            'PENDING_EXAM_OFFICE' => 'PENDING_EXAM_OFFICE',
            'PENDING_BGH' => 'PENDING_BGH',
            default => null,
        };
        if (! $lowerStage) return;

        $recipientId = $exam->logs()->where('to_status', $lowerStage)->whereNotNull('actor_user_id')->value('actor_user_id');
        if (! $recipientId || (int) $recipientId === (int) $actor->id) return;

        SystemNotifier::deliver(
            userIds: [$recipientId],
            actor: $actor,
            module: 'essay-exams',
            action: 'review-outcome',
            title: 'Kết quả duyệt đề thi',
            message: "Đề {$exam->code} {$decision} ở cấp trên.",
            url: route('essay-exams.show', $exam, false),
            type: SystemNotifier::TYPE_SYSTEM_CHANGE,
            meta: ['essay_exam_id' => $exam->id, 'stage' => $reviewedStage],
        );
    }

    private function notifyApprovalStage(EssayExam $exam, string $status, $actor): void
    {
        $roles = match ($status) {
            'PENDING_DEPT' => ['faculty-manager', 'department-head', 'head-of-department'],
            'PENDING_EXAM_OFFICE' => ['training-office-manager', 'exam-manager', 'exam-office', 'testing-office'],
            'PENDING_BGH' => ['bgh', 'board-of-management', 'ban giám hiệu'],
            default => [],
        };

        $recipientIds = $roles
            ? User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
                ->when($status === 'PENDING_DEPT', fn ($query) => $query->where('unit_id', $exam->creator?->unit_id ?: 0))
                ->pluck('id')
            : collect();

        $title = match ($status) {
            'PENDING_DEPT' => 'Có đề thi chờ khoa duyệt',
            'PENDING_EXAM_OFFICE' => 'Có đề thi chờ khảo thí duyệt',
            'PENDING_BGH' => 'Có đề thi chờ BGH duyệt',
            default => null,
        };
        $approvalUrl = in_array($status, ['PENDING_DEPT', 'PENDING_EXAM_OFFICE', 'PENDING_BGH'], true)
            ? route('essay-exams.approval', ['stage' => $status], false)
            : null;

        if ($title && $recipientIds->isNotEmpty()) {
            SystemNotifier::deliver(
                userIds: $recipientIds,
                actor: $actor,
                module: 'essay-exams',
                action: 'approval',
                title: $title,
                message: "Đề {$exam->code} — {$exam->title} đang chờ xử lý.",
                url: $approvalUrl,
                type: SystemNotifier::TYPE_SYSTEM_CHANGE,
                meta: ['essay_exam_id' => $exam->id, 'status' => $status],
            );
        }

        if (in_array($status, ['APPROVED', 'RETURNED'], true) && $exam->created_by_user_id) {
            SystemNotifier::deliver(
                userIds: [$exam->created_by_user_id],
                actor: $actor,
                module: 'essay-exams',
                action: strtolower($status),
                title: $status === 'APPROVED' ? 'Đề thi đã được duyệt' : 'Đề thi đã bị trả lại',
                message: "Đề {$exam->code} — {$exam->title} đã chuyển sang trạng thái mới.",
                url: route('essay-exams.show', $exam, false),
                type: SystemNotifier::TYPE_SYSTEM_CHANGE,
                meta: ['essay_exam_id' => $exam->id, 'status' => $status],
            );
        }
    }
    private function recalculateExamStatus(EssayExam $exam, $user, string $action, ?string $note = null): void
    {
        $statuses = $exam->questions()->pluck('paper_status')->unique()->all();
        $status = count($statuses) && count(array_diff($statuses, ['APPROVED'])) === 0 ? 'APPROVED' : (in_array('PENDING_BGH',$statuses,true) ? 'PENDING_BGH' : (in_array('PENDING_EXAM_OFFICE',$statuses,true) ? 'PENDING_EXAM_OFFICE' : (in_array('PENDING_DEPT',$statuses,true) ? 'PENDING_DEPT' : 'RETURNED')));
        if ($exam->status !== $status) $this->transition($exam, $status, $action, $user, $note); else $this->log($exam, $action, $exam->status, $exam->status, $user, $note);
    }
    private function log(EssayExam $exam, string $action, ?string $from, ?string $to, $user, ?string $note = null): void { EssayExamWorkflowLog::create(['essay_exam_id'=>$exam->id,'action'=>$action,'from_status'=>$from,'to_status'=>$to,'note'=>$note,'actor_user_id'=>$user?->id,'actor_username'=>$user?->email,'actor_display_name'=>$user?->name]); }
    private function logIntegratedAnswer(IntegratedAnswerSet $set, string $action, ?string $from, ?string $to, $user, ?string $note = null): void { IntegratedAnswerWorkflowLog::create(['answer_set_id'=>$set->id,'action'=>$action,'from_status'=>$from,'to_status'=>$to,'note'=>$note,'actor_user_id'=>$user?->id,'actor_username'=>$user?->email,'actor_display_name'=>$user?->name]); }

    private function storeImportDocumentAsPdf($file, string $code): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $safeCode = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $code) ?: 'de-thi', '-');
        $safeName = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file', '-');
        $directory = 'essay-exam/imports/'.now()->format('Y/m');
        $baseName = $safeCode.'-'.$safeName.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3));
        $sourcePath = $file->storeAs($directory, $baseName.'.'.$extension, 'local');
        $result = [
            'source_document_path' => $sourcePath,
            'source_pdf_path' => null,
            'source_original_name' => $originalName,
        ];

        $pdfPath = $directory.'/'.$baseName.'.pdf';
        if (in_array($extension, ['txt', 'csv', 'tsv'], true)) {
            Storage::disk('local')->makeDirectory('mpdf-temp');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'tempDir' => Storage::disk('local')->path('mpdf-temp')]);
            $text = htmlspecialchars($this->readImportText($file), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mpdf->WriteHTML('<pre style="font-family:dejavusans;font-size:10pt;white-space:pre-wrap">'.$text.'</pre>');
            $mpdf->Output(Storage::disk('local')->path($pdfPath), \Mpdf\Output\Destination::FILE);
            $result['source_pdf_path'] = $pdfPath;

            return $result;
        }

        abort_unless(in_array($extension, ['doc', 'docx'], true), 422, 'Định dạng file đề không được hỗ trợ.');
        try {
            app(DocumentConverterInterface::class)->convert(
                Storage::disk('local')->path($sourcePath),
                'pdf',
                Storage::disk('local')->path($pdfPath),
            );
        } catch (\Throwable $exception) {
            \Log::error('Essay exam import PDF conversion failed', [
                'source' => $sourcePath,
                'pdf' => $pdfPath,
                'error' => $exception->getMessage(),
            ]);
            abort(500, 'Không chuyển được file Word sang PDF để lưu bản in duyệt đề: '.$exception->getMessage());
        }
        abort_unless(Storage::disk('local')->exists($pdfPath), 500, 'Không tạo được file PDF từ file Word đã import.');
        $result['source_pdf_path'] = $pdfPath;

        return $result;
    }

    private function examApprovalPrintablePath(EssayExam $exam): ?string
    {
        return $exam->source_pdf_path && (Storage::disk('local')->exists($exam->source_pdf_path) || Storage::disk('public')->exists($exam->source_pdf_path))
            ? $exam->source_pdf_path
            : null;
    }

    private function archiveImportedSources(EssayExam $exam, ?EssayExamApprovalDocument $document): void
    {
        $updates = [];
        foreach (['source_document_path', 'source_pdf_path'] as $field) {
            $path = $exam->{$field};
            if (! $path || ! str_starts_with($path, 'essay-exam/imports/')) continue;

            $archivePath = 'essay-exam/archive/'.$exam->id.'/'.basename($path);
            Storage::disk('local')->makeDirectory(dirname($archivePath));
            if (Storage::disk('local')->exists($path)) {
                $moved = Storage::disk('local')->move($path, $archivePath);
            } elseif (Storage::disk('public')->exists($path)) {
                $stream = Storage::disk('public')->readStream($path);
                $moved = $stream && Storage::disk('local')->writeStream($archivePath, $stream);
                if (is_resource($stream)) fclose($stream);
            } else {
                continue;
            }
            if (! $moved) {
                report(new \RuntimeException('Could not archive essay exam source '.$path));
                continue;
            }

            Storage::disk('public')->delete($path);
            $updates[$field] = $archivePath;
            if ($document?->document_path === $path) $document->update(['document_path' => $archivePath]);
        }
        if ($updates) $exam->update($updates);
    }

    private function importMultipleFiles(Request $request, array $data, $user): RedirectResponse
    {
        $files = array_values((array) $request->file('import_files', []));
        $created = [];
        foreach ($files as $file) {
            $importDocument = $this->storeImportDocumentAsPdf($file, (string) Str::ulid());
            $fileText = $this->readImportText($file);
            $rows = $this->parseImportRows($fileText);
            abort_unless($rows, 422, 'Không nhận diện được câu hỏi trong file '.$file->getClientOriginalName().'.');
            $types = collect($rows)->pluck('question_type')->filter()->unique();
            $fileData = $data;
            if ($types->contains('multiple_choice') && $types->contains('essay')) $fileData['exam_type'] = 'Tích hợp';
            $fileData['import_class_id'] = $request->integer('import_class_id');
            $created[] = DB::transaction(function () use ($rows, $fileData, $user, $file, $importDocument) {
                $exam = EssayExam::create(['code'=>$this->nextExamCode(),'title'=>$this->examTitle((int) $fileData['import_subject_id'], (int) $fileData['import_class_id']),'subject_id'=>$fileData['import_subject_id'],'class_id'=>$fileData['import_class_id'],'duration_minutes'=>$fileData['duration_minutes'],'academic_year'=>$fileData['academic_year'],'semester'=>$fileData['semester'],'difficulty'=>$fileData['difficulty'],'exam_type'=>$fileData['exam_type'],'created_by_user_id'=>$user->id,'created_by_username'=>$user->email,'created_by_display_name'=>$user->name,'note'=>'Import nhiều file: '.$file->getClientOriginalName()] + $importDocument);
                foreach (collect($rows)->groupBy('paper') as $paper => $questions) foreach ($questions as $i => $q) $exam->questions()->create(['paper_number'=>(int)$paper,'question_number'=>$i+1,'question_type'=>$q['question_type'] ?? 'essay','content'=>$q['content'],'options'=>$q['options'] ?? null,'answer'=>$q['answer'] ?? '','points'=>$q['points'] ?? 1]);
                $this->log($exam,'IMPORT',null,'DRAFT',$user);
                return $exam;
            });
        }
        abort_unless($created, 422, 'Không có file nào được import.');
        return redirect()->route('essay-exams.show', $created[0])->with('success', 'Đã import '.count($created).' file thành '.count($created).' đề riêng.');
    }

    public function readImportText($file): string
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        // Hai định dạng Word đi theo hai luồng độc lập:
        // DOCX đọc XML trong gói ZIP; DOC cũ mới đi qua bộ giải mã RTF/MsDoc.
        if ($extension === 'docx') return $this->readDocxImportText($path);
        if ($extension === 'doc') return $this->readLegacyDocImportText($path);

        $raw = file_get_contents($path) ?: '';
        $encoding = mb_detect_encoding($raw, ['UTF-8', 'UTF-16', 'Windows-1252', 'ISO-8859-1'], true) ?: 'UTF-8';
        return $this->ensureUtf8Text(mb_convert_encoding($raw, 'UTF-8', $encoding));
    }

    private function readDocxImportText(string $path): string
    {
        $xml = false;
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: false;
                $zip->close();
            }
        }

        if (!is_string($xml) || $xml === '') {
            // Fallback cho máy chưa bật ext-zip: DOCX vẫn được giải nén bằng
            // PowerShell có sẵn, tuyệt đối không chuyển sang bộ giải mã RTF.
            $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'essay-docx-'.bin2hex(random_bytes(6));
            @mkdir($tempDir, 0777, true);
            $zipPath = $tempDir.'.zip';
            @copy($path, $zipPath);
            $psPath = str_replace("'", "''", $zipPath);
            $psDir = str_replace("'", "''", $tempDir);
            $command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command '.escapeshellarg("Expand-Archive -LiteralPath '$psPath' -DestinationPath '$psDir' -Force");
            exec($command, $unusedOutput, $status);
            $xml = $status === 0 ? @file_get_contents($tempDir.DIRECTORY_SEPARATOR.'word'.DIRECTORY_SEPARATOR.'document.xml') : false;
            $this->removeImportDirectory($tempDir);
            @unlink($zipPath);
        }

        abort_unless(is_string($xml) && $xml !== '', 422, 'Không đọc được nội dung file Word .docx. File phải là DOCX hợp lệ.');
        $xml = preg_replace('/<w:tab\s*\/?\s*>/i', "\t", $xml);
        $xml = preg_replace('/<w:br\s*\/?\s*>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:(p|tr)>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:tc>/i', "\t", $xml);
        return $this->ensureUtf8Text(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function readLegacyDocImportText(string $path): string
    {
        $raw = file_get_contents($path) ?: '';
        if (str_starts_with(ltrim($raw), '{\\rtf')) return $this->ensureUtf8Text($this->decodeRtfTextStable($raw));
        try {
            $reader = new \PhpOffice\PhpWord\Reader\MsDoc();
            $word = $reader->load($path);
            $extract = function ($element) use (&$extract): string {
                if (method_exists($element, 'getText')) return (string) $element->getText();
                if (method_exists($element, 'getRows')) return implode("\n", array_map(fn ($row) => implode("\t", array_map(fn ($cell) => $extract($cell), $row->getCells())), $element->getRows()));
                if (method_exists($element, 'getElements')) return implode('', array_map(fn ($child) => $extract($child), $element->getElements()));
                return '';
            };
            $text = '';
            foreach ($word->getSections() as $section) foreach ($section->getElements() as $element) $text .= $extract($element)."\n";
            if (trim($text) !== '') return $this->ensureUtf8Text($text);
        } catch (\Throwable $e) {
            abort(422, 'Không đọc được file Word .doc. Hãy dùng đúng file mẫu Word được cung cấp.');
        }
        return '';
    }

    private function ensureUtf8Text(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) return $text;
        $source = mb_detect_encoding($text, ['UTF-16LE', 'UTF-16BE', 'Windows-1252', 'ISO-8859-1'], true);
        return $source ? mb_convert_encoding($text, 'UTF-8', $source) : $text;
    }

    private function removeImportDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        @rmdir($directory);
    }

    /** Chuẩn hoá tiếng Việt để nhận diện tiêu đề trong docx mà không làm thay đổi nội dung câu hỏi. */
    private function decodeRtfTextStable(string $raw): string
    {
        $skipDestinations = ['fonttbl'=>true,'colortbl'=>true,'stylesheet'=>true,'info'=>true,'generator'=>true,'header'=>true,'footer'=>true,'pict'=>true,'object'=>true,'shp'=>true,'shpinst'=>true,'themedata'=>true,'datastore'=>true,'xmlnstbl'=>true];
        $stack = [['skip'=>false,'uc'=>1,'fallback'=>0,'star'=>false]];
        $out = '';
        $length = strlen($raw);
        $appendByte = static function (string $byte): string {
            $converted = @iconv('CP1258', 'UTF-8//IGNORE', $byte);
            return is_string($converted) && $converted !== '' ? $converted : $byte;
        };
        for ($i = 0; $i < $length;) {
            $ch = $raw[$i];
            if ($ch === '{') {
                $state = $stack[count($stack) - 1];
                $state['star'] = false;
                $stack[] = $state;
                $i++;
                continue;
            }
            if ($ch === '}') {
                if (count($stack) > 1) array_pop($stack);
                $i++;
                continue;
            }
            $stateIndex = count($stack) - 1;
            if ($ch === '\\') {
                $next = $raw[$i + 1] ?? '';
                if ($next === "'") {
                    $hex = substr($raw, $i + 2, 2);
                    if (($stack[$stateIndex]['fallback'] ?? 0) > 0) $stack[$stateIndex]['fallback']--;
                    elseif (ctype_xdigit($hex)) $out .= $appendByte(chr(hexdec($hex)));
                    $i += 4;
                    continue;
                }
                if (in_array($next, ['\\','{','}'], true)) {
                    if (! $stack[$stateIndex]['skip'] && ($stack[$stateIndex]['fallback'] ?? 0) === 0) $out .= $next;
                    $i += 2;
                    continue;
                }
                if ($next === '*') {
                    $stack[$stateIndex]['star'] = true;
                    $i += 2;
                    continue;
                }
                if (in_array($next, ['~','_','-'], true)) {
                    if (! $stack[$stateIndex]['skip'] && ($stack[$stateIndex]['fallback'] ?? 0) === 0) $out .= $next === '~' ? ' ' : ($next === '_' ? '‑' : '');
                    $i += 2;
                    continue;
                }
                if (preg_match('/\\\\([a-zA-Z]+)(-?\d*) ?/A', substr($raw, $i), $control)) {
                    $word = strtolower($control[1]);
                    $parameter = $control[2] === '' ? null : (int) $control[2];
                    $tokenLength = strlen($control[0]);
                    if ($stack[$stateIndex]['star'] && array_key_exists($word, $skipDestinations)) $stack[$stateIndex]['skip'] = true;
                    $stack[$stateIndex]['star'] = false;
                    if ($word === 'uc' && $parameter !== null) $stack[$stateIndex]['uc'] = max(0, $parameter);
                    elseif ($word === 'u' && $parameter !== null) {
                        if (! $stack[$stateIndex]['skip']) {
                            $code = $parameter < 0 ? $parameter + 65536 : $parameter;
                            $out .= mb_convert_encoding(pack('n', $code), 'UTF-8', 'UTF-16BE');
                        }
                        $stack[$stateIndex]['fallback'] = $stack[$stateIndex]['uc'];
                        // Một số file Word khai báo \uc0 nhưng vẫn chèn ký tự
                        // fallback '?' hoặc \'3f sau \uN. Bỏ đúng ký tự thay
                        // thế này để không làm mất chữ kế tiếp.
                        $afterUnicode = substr($raw, $i + $tokenLength, 5);
                        if ($stack[$stateIndex]['fallback'] === 0 && ($afterUnicode[0] ?? '') === '?') {
                            $stack[$stateIndex]['fallback'] = 1;
                        } elseif ($stack[$stateIndex]['fallback'] === 0 && str_starts_with($afterUnicode, "\\'3f")) {
                            $stack[$stateIndex]['fallback'] = 1;
                        }
                    } elseif ($word === 'bin' && $parameter !== null) {
                        $i += $tokenLength + $parameter;
                        continue;
                    } elseif (! $stack[$stateIndex]['skip'] && ($stack[$stateIndex]['fallback'] ?? 0) === 0) {
                        if (in_array($word, ['par','line','sect','row','page'], true)) $out .= "\n";
                        elseif ($word === 'tab') $out .= "\t";
                    }
                    $i += $tokenLength;
                    continue;
                }
                $i += 2;
                continue;
            }
            if (($stack[$stateIndex]['fallback'] ?? 0) > 0) {
                $stack[$stateIndex]['fallback']--;
                $i++;
                continue;
            }
            if (! $stack[$stateIndex]['skip'] && $ch !== "\r" && $ch !== "\n") $out .= $ch;
            $i++;
        }
        $out = preg_replace('/[ \t]+\n/', "\n", $out) ?: $out;
        $out = str_replace(['\\~', '\\_', '\\-'], [' ', '-', ''], $out);
        $out = strtr($out, [
            'đế?n' => 'đến', 'Đế?n' => 'Đến', 'hợ?p' => 'hợp',
            'tŕnh' => 'trình', 'quá tŕnh' => 'quá trình',
            'đă học' => 'đã học', 'hăy' => 'hãy', 'T́m' => 'Tìm', 'pḥng' => 'phòng',
        ]);
        $out = preg_replace('/(?<=\p{L})\?(?=\p{L})/u', '', $out) ?: $out;
        $out = preg_replace('/(?<=\p{L})\?(?=\s+\p{L})/u', '', $out) ?: $out;
        return trim($out);
    }

    private function decodeRtfText(string $raw): string
    {
        // Chỉ bỏ mã điều khiển \bin, không xóa cố định 200 byte phía sau vì
        // ở một số file Word phần đó chứa chính nội dung câu hỏi.
        // RTF \binN được theo sau đúng N byte nhị phân. Chỉ xóa từ khóa
        // \binN sẽ làm dữ liệu ảnh/shape chen vào giữa câu và mất chữ.
        $text = $raw;
        while (preg_match('/\\\\bin(\d+)\s?/', $text, $binMatch, PREG_OFFSET_CAPTURE)) {
            $token = $binMatch[0][0];
            $offset = $binMatch[0][1];
            $binaryStart = $offset + strlen($token);
            $text = substr($text, 0, $offset).substr($text, $binaryStart + (int) $binMatch[1][0]);
        }
        // Giữ khoảng trắng không ngắt dòng và trạng thái chỉ số dưới/trên
        // trong các môn có công thức hóa học, ví dụ CO₂, NH₃, H₂O.
        $text = preg_replace('/\\\\~/', ' ', $text) ?: $text;
        $text = preg_replace('/\\\\sub\b\s*/i', "\x03", $text) ?: $text;
        $text = preg_replace('/\\\\super\b\s*/i', "\x05", $text) ?: $text;
        $text = preg_replace('/\\\\nosupersub\b\s*/i', "\x04", $text) ?: $text;
        $unicode = [];
        $text = preg_replace_callback('/\\\\u(-?\\d+)(?:\\?|\\\\\'[0-9a-fA-F]{2})?/', function (array $match) use (&$unicode): string {
            $code = (int) $match[1];
            if ($code < 0) $code += 65536;
            $unicode[] = mb_convert_encoding(pack('n', $code), 'UTF-8', 'UTF-16BE');
            return "\x01".(count($unicode) - 1)."\x02";
        }, $text) ?: $text;
        $text = preg_replace_callback("/\\\\'([0-9a-fA-F]{2})/", fn (array $m): string => chr(hexdec($m[1])), $text) ?: $text;
        $text = preg_replace('/\\\\(par|line|sect|row)\\b/', "\n", $text) ?: $text;
        $text = preg_replace('/\\\\tab\\b/', "\t", $text) ?: $text;
        $text = preg_replace('/\\\\[a-zA-Z]+-?\\d* ?/', '', $text) ?: $text;
        $text = str_replace(['{', '}'], '', $text);
        // Các file Word của đơn vị khai báo ansicpg1252 nhưng phần tiếng Việt
        // thực tế dùng bảng mã Windows-1258. iconv hỗ trợ CP1258 trên máy này,
        // còn mbstring/PHP 8.4 lại không nhận tên Windows-1258.
        $converted = @iconv('CP1258', 'UTF-8//IGNORE', $text);
        if ($converted === false || $converted === '') {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
        }
        if (is_string($converted) && $converted !== '') $text = $converted;
        foreach ($unicode as $index => $value) $text = str_replace("\x01{$index}\x02", $value, $text);
        $subscript = ['0'=>'₀','1'=>'₁','2'=>'₂','3'=>'₃','4'=>'₄','5'=>'₅','6'=>'₆','7'=>'₇','8'=>'₈','9'=>'₉','+'=>'₊','-'=>'₋'];
        $superscript = ['0'=>'⁰','1'=>'¹','2'=>'²','3'=>'³','4'=>'⁴','5'=>'⁵','6'=>'⁶','7'=>'⁷','8'=>'⁸','9'=>'⁹','+'=>'⁺','-'=>'⁻'];
        $text = preg_replace_callback("/\x03(.*?)\x04/s", function (array $m) use ($subscript, $superscript): string {
            return strtr($m[1], $subscript);
        }, $text) ?: $text;
        $text = preg_replace_callback("/\x05(.*?)\x04/s", function (array $m) use ($superscript): string {
            return strtr($m[1], $superscript);
        }, $text) ?: $text;
        $text = str_replace(["\x03", "\x04", "\x05"], '', $text);
        // Một số ký tự tiếng Việt trong RTF cũ bị ánh xạ thành ŕ; chuẩn hóa
        // về chữ gốc trước khi tách câu, đồng thời bỏ metadata hình/shape rác.
        $text = strtr($text, ['tŕnh' => 'trình', 'ŕ' => 'ri', 'Ŕ' => 'Ri']);
        $text = preg_replace('/^.*(?:shapeType|lTxid|lineColor|d hgt\d+).*$/mi', '', $text) ?: $text;
        foreach (['504b0304', 'd0cf11e0'] as $marker) {
            $binaryPosition = stripos($text, $marker);
            if ($binaryPosition !== false) $text = substr($text, 0, $binaryPosition);
        }
        return trim($text);
    }

    private function repairImportedText(string $text): string
    {
        $score = static fn (string $value): int => preg_match_all('/(?:Ã|Â|Ð|Ñ|á»|Ä|Æ|Å|â|ð)/u', $value) ?: 0;
        $bytes = @iconv('UTF-8', 'Windows-1252//IGNORE', $text);
        $candidate = $bytes === false ? false : @iconv('UTF-8', 'UTF-8//IGNORE', $bytes);
        return is_string($candidate) && $candidate !== '' && $score($candidate) < $score($text) ? $candidate : $text;
    }

    private function importProbe(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
            'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
            'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i','ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
            'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y','đ'=>'d',
        ]);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii !== false ? $ascii : $value;
    }

    /** Parse the official mixed exam template: multiple-choice section followed by essay section. */
    private function parseIntegratedRows(string $text): array
    {
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $text) ?: [], fn ($line) => trim($line) !== ''));
        // Word .DOC cũ có thể ngắt một từ hoặc một lựa chọn thành nhiều dòng
        // (ví dụ "B. P" + "hosphorylase", hoặc "C" + ". Protein"). Ghép
        // các mảnh này trước khi nhận diện câu hỏi và đáp án.
        $joinedLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            $previous = $joinedLines[count($joinedLines) - 1] ?? null;
            $isContinuation = $previous !== null
                && preg_match('/^(?:[A-D]\s*[.)]|cau\s*\d+)/iu', $line) !== 1
                && (preg_match('/^[\p{Ll}]/u', $line) === 1 && preg_match('/[\p{L}]$/u', $previous) === 1
                    || preg_match('/^[.]\s*/u', $line) === 1 && preg_match('/^[A-D]$/iu', $previous) === 1
                    || preg_match('/^[\p{L}\d]/u', $line) === 1 && preg_match('/^[A-D]\s*[.)]?$/iu', $previous) === 1
                    || preg_match('/^\d/', $line) === 1 && preg_match('/[\p{L}]$/u', $previous) === 1);
            if ($isContinuation) $joinedLines[count($joinedLines) - 1] .= $line;
            else $joinedLines[] = $line;
        }
        $lines = $joinedLines;
        // Chuẩn hoá từng dòng riêng; iconv trên toàn bộ RTF có thể dừng sớm
        // khi gặp dữ liệu nhị phân nhúng và làm mất phần tiêu đề phía sau.
        $probeText = implode("\n", array_map(fn ($line) => $this->importProbe($line), $lines));
        $hasPartOne = preg_match('/^\s*(?:i|1)\.\s*/um', $probeText) === 1;
        $hasPartTwo = preg_match('/^\s*(?:ii|2)\.\s*/um', $probeText) === 1;
        $hasStructuredSections = $hasPartOne || $hasPartTwo;
        // Nhận diện theo các dòng "Câu 01", "Câu 02" thay vì phụ thuộc
        // tuyệt đối vào tiêu đề phần I/II bị Word chèn mã _Hlk.
        $hasQuestionHeaders = preg_match('/(?:^|\R)\s*c(?:âu|câu|au)\s*0*\d+/iu', $text) === 1
            || preg_match('/c(?:âu|au)\s*\d+/iu', $text) === 1
            || preg_match('/cau\s*\d+/u', $probeText) === 1;
        if (! $hasStructuredSections && ! $hasQuestionHeaders) {
            return [];
        }

        $rows = [];
        // Nếu không có phần I rõ ràng, vẫn bắt đầu ở phần trắc nghiệm vì
        // các dòng Câu 01... là mốc chuẩn của file đề.
        $section = $hasPartOne ? null : 'multiple_choice';
        $current = null;
        $paper = 1;
        // Một số file tự luận có phần I là danh sách câu hỏi, sau đó mới là
        // phần II chứa đáp án và barem. Chỉ lấy phần II để tránh import trùng.
        $answerOnlyDocument = preg_match('/^\s*ii\.\s*.*dap\s*an/um', $probeText) === 1;
        $startedAnswerSection = ! $answerOnlyDocument;
        $flush = function () use (&$rows, &$current): void {
            if ($current === null) {
                return;
            }
            $current['content'] = trim($current['content']);
            unset($current['_has_explicit_points']);
            if ($current['content'] !== '') {
                $rows[] = $current;
            }
            $current = null;
        };

        foreach ($lines as $rawLine) {
            $line = trim(preg_replace('/Hlk\d+/iu', '', $rawLine) ?: $rawLine);
            $line = ltrim($line, " \t\\*_");
            $probe = $this->importProbe($line);
            if (! $startedAnswerSection) {
                if (preg_match('/^\s*ii\.\s*.*dap\s*an/u', $probe)) {
                    $startedAnswerSection = true;
                    $section = 'essay';
                }
                continue;
            }
            if (preg_match('/^de\s*(?:so\s*)?(\d+)/u', $probe, $paperMatch)) {
                $flush();
                $paper = (int) $paperMatch[1];
                $section = 'essay';
                continue;
            }
            if ($section === 'essay' && preg_match('/^\s*\(?\s*(?:sbd|can bo coi thi|chu nhiem khoa|hieu truong|ma de|normal;heading)/u', $probe)) {
                $flush();
                $section = null;
                continue;
            }
            if (preg_match('/^\s*(?:i|1)\.\s*/u', $probe) || preg_match('/^\s*(?:i|1)\.\s*/iu', $line)) {
                $flush();
                $section = 'multiple_choice';
                continue;
            }
            if (preg_match('/^\s*(?:ii|2)\.\s*/u', $probe) || preg_match('/^\s*(?:ii|2)\.\s*/iu', $line)) {
                $flush();
                $section = 'essay';
                $points = 1.0;
                if (preg_match('/([\d,.]+)\s*diem/u', $probe, $pointMatch)) {
                    $points = (float) str_replace(',', '.', $pointMatch[1]);
                }
                // Mẫu De4 có một câu tự luận dạng đoạn văn, không có dòng
                // "Câu 61"; tạo sẵn câu 61 để gom toàn bộ Phần II vào đó.
                $current = [
                    'paper' => $paper,
                    'question' => 61,
                    'content' => '',
                    'answer' => '',
                    'points' => $points > 0 ? $points : 1,
                    // Điểm ở đây chỉ là mặc định cho câu 61 tổng hợp, không
                    // phải barem thật của câu trong file.
                    '_has_explicit_points' => false,
                    'question_type' => 'essay',
                    'options' => [],
                ];
                continue;
            }
            if (preg_match('/^(?:tra loi trac nghiem|phan tra loi|ma so sinh vien|ho ten|ngay sinh|can bo coi thi|luu y|het|tp\.hcm)/u', $probe)) {
                continue;
            }
            if ($section === null) $section = 'multiple_choice';

            $questionMatch = [];
            $hasQuestion = preg_match('/^\s*cau\s*(\d+)\s*[:.)]?\s*(.*)$/u', $probe, $questionMatch)
                || preg_match('/câu\s*(\d+)\s*[:.)]?\s*(.*)$/iu', $line, $questionMatch)
                || preg_match('/^\s*c(?:âu|au)\s*0*(\d{1,2})\s*[:.)]?\s*(.*)$/iu', $line, $questionMatch);
            if ($hasQuestion) {
                $flush();
                $number = (int) $questionMatch[1];
                $rawContent = preg_replace('/^.*?(?:cau|câu|c[^\d]{0,8})\s*\d+\s*[:.)]?\s*/iu', '', $line) ?: '';
                $points = 1.0;
                $hasExplicitPoints = false;
                if (preg_match('/\(\s*([\d,.]+)\s*diem/u', $probe, $pointMatch)) {
                    $points = (float) str_replace(',', '.', $pointMatch[1]);
                    $hasExplicitPoints = true;
                    $rawContent = preg_replace('/\(.*?\)/u', '', $rawContent) ?: $rawContent;
                }
                $current = [
                    'paper' => $paper,
                    'question' => $number,
                    'content' => trim($rawContent, " \t:-()"),
                    'answer' => '',
                    'points' => $points > 0 ? $points : 1,
                    '_has_explicit_points' => $hasExplicitPoints,
                    'question_type' => $section === 'multiple_choice' ? 'multiple_choice' : 'essay',
                    'options' => [],
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            // Mẫu Word thường đặt đáp án ngay dưới các phương án: "Đáp án: C".
            if (preg_match('/^(?:dap\s*an|answer)\s*[:.]?\s*([A-D])\b/iu', $probe, $answerMatch)) {
                $current['answer'] = strtoupper($answerMatch[1]);
                continue;
            }

            $optionParts = preg_split('/(?=\b[a-d]\s*[.)]\s*)/iu', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $foundOption = false;
            foreach ($optionParts as $part) {
                if (preg_match('/^([a-d])\s*[.)]\s*(.*)$/iu', trim($part), $optionMatch)) {
                    $current['options'][strtoupper($optionMatch[1])] = trim($optionMatch[2]);
                    $foundOption = true;
                }
            }
            if (! $foundOption && $section === 'essay') {
                // Barem trong file Word thường đứng thành một dòng chỉ có số.
                if (preg_match('/^\s*([\d]+(?:[,.]\d+)?)\s*$/u', $probe, $rubricPointMatch)) {
                    $rubricPoint = (float) str_replace(',', '.', $rubricPointMatch[1]);
                    if (! ($current['_has_explicit_points'] ?? false)) {
                        $current['points'] = $rubricPoint > 0 ? $rubricPoint : 1;
                        $current['_has_explicit_points'] = true;
                    } else {
                        $current['answer'] .= ($current['answer'] === '' ? '' : "\n") . '[' . rtrim(rtrim(number_format($rubricPoint, 2, '.', ''), '0'), '.') . ' điểm]';
                    }
                    continue;
                }
                $essayLine = preg_replace('/^\s*(?:\(?\s*[\d,.]*\s*)?(?:điểm|diem)\s*\)?\s*/iu', '', $line) ?: $line;
                if (preg_match('/^\s*diem\s*\)/u', $this->importProbe($essayLine))) {
                    $essayLine = preg_replace('/^\s*[^)]*\)\s*/u', '', $essayLine) ?: $essayLine;
                }
                if (preg_match('/^\s*[-–—]\s*(.+)$/u', $essayLine, $answerLineMatch)) {
                    $current['answer'] .= ($current['answer'] === '' ? '' : "\n") . trim($answerLineMatch[1]);
                } elseif (trim($essayLine) !== '') {
                    $current['content'] .= ($current['content'] === '' ? '' : "\n").$essayLine;
                }
            } elseif (! $foundOption && $section === 'multiple_choice' && ! preg_match('/^(?:dap an|cau\s*\d+)/u', $probe)) {
                $current['content'] .= ($current['content'] === '' ? '' : "\n").$line;
            }
        }
        $flush();

        return $rows;
    }

    public function parseImportRows(string $text): array
    {
        $integratedRows = $this->parseIntegratedRows($text);
        if ($integratedRows !== []) {
            $normalizedRows = $this->normalizeQuestionRows($integratedRows);
            // Với bộ "BỘ CÂU HỎI - ĐÁP ÁN", parser cũ dùng câu 61 làm
            // placeholder. Khi đã tách được nhiều dòng đáp án trong mỗi đề,
            // đánh lại số câu theo thứ tự thực tế.
            $groups = collect($normalizedRows)->groupBy('paper');
            if ($groups->count() > 1 && $groups->every(fn ($items) => $items->count() > 1 && $items->every(fn ($row) => (int) ($row['question'] ?? 0) === 61))) {
                $normalizedRows = collect($normalizedRows)->groupBy('paper')->flatMap(fn ($items) => $items->values()->map(function ($row, $index) {
                    $row['question'] = $index + 1;
                    return $row;
                }))->values()->all();
            }
            return $this->standardizeImportedRows($normalizedRows);
        }
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $text) ?: [], fn($line) => trim($line) !== ''));
        if (!$lines) return [];
        // Mẫu trắc nghiệm Word dạng "Bài 1: 20 câu": câu hỏi không có số,
        // tiếp theo là A-D và dòng ANSWER: A/B/C/D.
        $sampleRows = $this->parseAnswerAfterOptionsRows($lines);
        if ($sampleRows !== []) return $this->standardizeImportedRows($sampleRows);
        // Word/docx thường tách tiêu đề thành các dòng "Câu n (1 điểm)".
        // Chuẩn hoá ASCII chỉ để nhận diện metadata, không thay đổi nội dung lưu câu hỏi.
        $lines = array_values(array_filter($lines, function ($line): bool {
            $probe = $this->importProbe($line);
            return !preg_match('/^(?:mon|thoi gian|bo cau hoi|i\.)\s*:?/u', $probe)
                && !preg_match('/^cau\s*\d+\s*\(\s*[\d,.]+\s*diem\s*\)\s*$/u', $probe);
        }));
        // Dạng văn bản phổ biến: "Câu 1 (3 điểm): Nội dung...".
        // Đưa về dạng "Câu 1: Nội dung" để bộ đọc câu hỏi xử lý, không tạo câu rác.
        $lines = array_map(static function ($line): string {
            return preg_replace_callback('/^(\s*Câu\s*\d+)\s*\(\s*([\d,.]+)\s*điểm\s*\)\s*:\s*/iu', static function ($m): string {
                return $m[1].': __POINTS_'.str_replace(',', '.', $m[2]).'__ ';
            }, trim($line)) ?: trim($line);
        }, $lines);
        // Parser chuyên dụng cho file Word "BỘ CÂU HỎI - ĐÁP ÁN": phần I là câu hỏi,
        // phần II lặp lại Câu n và các dòng gạch đầu dòng là đáp án/thang điểm.
        $wordRows = []; $wordPaper = 1; $wordAnswerMode = false; $wordQuestion = null; $wordLastAnswerLine = false;
        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            $probe = $this->importProbe($line);
            if (preg_match('/^ii\.?\s*(dap an|answer)/', $probe)) { $wordAnswerMode = true; $wordQuestion = null; $wordLastAnswerLine = false; continue; }
            if ($wordAnswerMode && preg_match('/^([\d]+(?:[,.][\d]+)?)\s*$/', $line, $pm)) {
                $score = (float) str_replace(',', '.', $pm[1]);
                if ($wordQuestion !== null) {
                    // Barem có thể nằm sau các dòng đáp án. Luôn ghi vào points;
                    // nếu đã có đáp án thì chỉ giữ thêm nhãn điểm trong phần đáp án.
                    $wordRows[$wordQuestion]['points'] = $score > 0 ? $score : 1;
                    if ($wordLastAnswerLine) {
                        $wordRows[$wordQuestion]['answer'] .= ($wordRows[$wordQuestion]['answer'] ? "\n" : '').'['.$pm[1].' điểm]';
                    }
                }
                $wordLastAnswerLine = false;
                continue;
            }
            if (preg_match('/^(?:de|d)\s*(?:thi\s*)?(?:so|s)\s*(\d+)/', $probe, $m)
                || preg_match('/^đề\s*(?:thi\s*)?(?:số|so)\s*(\d+)/iu', $line, $m)
                || preg_match('/^Äá»\s*(?:thi\s*)?(?:sá»‘|so)\s*(\d+)/iu', $line, $m)) { $wordPaper = (int) end($m); $wordQuestion = null; continue; }
            if (preg_match('/^\s*[^\d\r\n]*(\d+)\s*(?:\([^)]*\))?\s*[.\-:)]\s*(.*)$/u', $line, $m)) {
                $number = (int) $m[1];
                $unmatchedAnswerHeader = false;
                if ($wordAnswerMode) {
                    $previousWordQuestion = $wordQuestion;
                    $previousLastAnswerLine = $wordLastAnswerLine;
                    $wordQuestion = null; $wordLastAnswerLine = false;
                    foreach ($wordRows as $idx => $existing) if ($existing['paper'] === $wordPaper && $existing['question'] === $number) {
                        $wordQuestion = $idx;
                        if (preg_match('/\t\s*([\d,.]+)\s*$/', $m[2], $pm)) $wordRows[$idx]['points'] = (float) str_replace(',', '.', $pm[1]);
                        $inlineAnswer = trim(preg_replace('/\t\s*[\d,.]+\s*$/', '', $m[2]) ?: $m[2]);
                        if ($inlineAnswer !== '') {
                            $wordRows[$idx]['answer'] = trim(($wordRows[$idx]['answer'] ? $wordRows[$idx]['answer']."\n" : '').$inlineAnswer);
                            $wordLastAnswerLine = true;
                        }
                        break;
                    }
                    // Nếu không có câu tương ứng ở phần I thì đây là ý dạng
                    // "1) ..."/"2) ..." của câu hiện tại, không phải câu mới.
                    if ($wordQuestion !== null) continue;
                    $wordQuestion = $previousWordQuestion;
                    $wordLastAnswerLine = $previousLastAnswerLine;
                    $unmatchedAnswerHeader = true;
                }
                if (! $unmatchedAnswerHeader) {
                    $content = trim($m[2]); $points = 1;
                    if (preg_match('/\t\s*([\d,.]+)\s*$/', $content, $pm)) { $points = (float) str_replace(',', '.', $pm[1]); $content = trim(substr($content, 0, -strlen($pm[0]))); }
                    $wordRows[] = ['paper'=>$wordPaper,'question'=>$number,'content'=>$content,'answer'=>'','points'=>$points > 0 ? $points : 1];
                    $wordQuestion = count($wordRows) - 1; $wordLastAnswerLine = false; continue;
                }
            }
            if ($wordAnswerMode && $wordQuestion !== null) {
                $answerText = null;
                if (preg_match('/^[-•]\s*(.+)$/u', $line, $bm)) {
                    $answerText = trim($bm[1]);
                } elseif (preg_match('/^(?:[A-Za-z]|\d+)\s*[.)]\s*(.+)$/u', $line, $bm)
                    || preg_match('/^(?:y|ý)\s*\d+\s*[:.)-]\s*(.+)$/iu', $line, $bm)) {
                    // Nhận cả ý dạng a), 1), Ý 1:; trước đây chỉ nhận gạch đầu dòng.
                    $answerText = trim($bm[1]);
                } elseif ($line !== '') {
                    // Một số file Word không có ký hiệu ở ý đầu tiên.
                    $answerText = $line;
                }
                if ($answerText !== null && $answerText !== '') {
                    $wordRows[$wordQuestion]['answer'] .= ($wordRows[$wordQuestion]['answer'] ? "\n" : '').$answerText;
                    $wordLastAnswerLine = true;
                }
            }
        }
        if (count($wordRows) > 0) return array_map(fn ($row) => $this->normalizeImportedAnswerPointBreaks($row), $wordRows);
        $headerIndex = null; $map = [];
        foreach (array_slice($lines, 0, 8, true) as $i => $line) {
            $cells = preg_split('/\t|\s*\|\s*|\s*;\s*/', trim($line));
            $normalized = array_map(fn($v) => mb_strtolower(trim($v)), $cells);
            foreach ($normalized as $k => $v) {
                if (preg_match('/đề\s*số|de\s*so|^exam/', $v)) $map['paper'] = $k;
                elseif (preg_match('/nội\s*dung|noi\s*dung|content|câu\s*hỏi/', $v)) $map['content'] = $k;
                elseif (preg_match('/đáp\s*án|dap\s*an|answer|hướng\s*dẫn/', $v)) $map['answer'] = $k;
                elseif (preg_match('/điểm|diem|point|score/', $v)) $map['points'] = $k;
            }
            // Chỉ coi là bảng TSV/CSV khi dòng có nhiều cột; tiêu đề tự do
            // như "BỘ CÂU HỎI" không được kích hoạt chế độ bảng.
            if ((isset($map['content']) || isset($map['answer'])) && count($cells) >= 2 && preg_match('/\t|\||;/', $line)) { $headerIndex = $i; break; }
        }
        $rows = [];
        if ($headerIndex !== null) {
            $lastPaper = 1;
            foreach (array_slice($lines, $headerIndex + 1) as $line) {
                $cells = preg_split('/\t|\s*\|\s*|\s*;\s*/', trim($line));
                $paper = isset($map['paper']) ? (int)($cells[$map['paper']] ?? 0) : 0; if ($paper > 0) $lastPaper = $paper;
                $content = trim($cells[$map['content'] ?? -1] ?? ''); $answer = trim($cells[$map['answer'] ?? -1] ?? '');
                if ($content === '' && $answer === '') continue;
                $points = (float)str_replace(',', '.', trim($cells[$map['points'] ?? -1] ?? '1')); $rows[] = ['paper'=>$lastPaper,'content'=>$content ?: 'Câu hỏi import','answer'=>$answer,'points'=>$points > 0 ? $points : 1];
            }
        }
        if ($rows) return $rows;
        $paper = 1; $questionIndex = null; $answerMode = false;
        foreach ($lines as $line) {
            if (preg_match('/đề\s*(?:thi\s*)?(?:số\s*)?(\d+)/iu', $line, $m)) { $paper=(int)$m[1]; $questionIndex=null; $answerMode=false; continue; }
            if (preg_match('/^(?:câu\s*)?(\d+)\s*[.\-:)]\s*(.*)$/iu', trim($line), $m)) { $rows[]=['paper'=>$paper,'question'=>(int)$m[1],'content'=>trim($m[2]),'answer'=>'','points'=>1]; $questionIndex=count($rows)-1; $answerMode=false; continue; }
            if (preg_match('/đáp\s*án|answer/iu', $line)) { $answerMode=true; continue; }
            if ($questionIndex !== null) { if ($answerMode) $rows[$questionIndex]['answer'].=($rows[$questionIndex]['answer']?'\n':'').trim($line); else $rows[$questionIndex]['content'].=($rows[$questionIndex]['content']?'\n':'').trim($line); }
        }
        foreach ($rows as &$row) {
            if (preg_match('/^__POINTS_([\d.]+)__\s*/', $row['content'], $m)) {
                $row['points'] = (float) $m[1];
                $row['content'] = trim(substr($row['content'], strlen($m[0])));
            }
        }
        unset($row);
        return array_values(array_map(fn ($row) => $this->normalizeImportedAnswerPointBreaks($row), $rows));
    }

    private function normalizeImportedAnswerPointBreaks(array $row): array
    {
        $row['answer'] = $this->normalizeAnswerPointBreaks(trim((string) ($row['answer'] ?? '')));
        if ($row['answer'] !== '') {
            $row['answer'] = $this->ensureAnswerHasPoint($row['answer'], (float) ($row['points'] ?? 1));
        }
        return $row;
    }

    private function normalizeAnswerPointBreaks(string $answer): string
    {
        return preg_replace('/\R\s*(\[[^\x5D\r\n]*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\])/iu', ' $1', $answer) ?: $answer;
    }

    private function ensureAnswerHasPoint(string $answer, float $point): string
    {
        $answer = $this->normalizeAnswerPointBreaks(trim($answer));
        if ($answer === '') {
            return $answer;
        }
        $lines = preg_split('/\r\n|\r|\n/', $answer) ?: [];
        $missingIndexes = [];
        $existingPointTotal = 0.0;
        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            if (preg_match('/\[\s*([\d]+(?:[,.][\d]+)?)\s*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\]/iu', $line, $match)) {
                $existingPointTotal += (float) str_replace(',', '.', $match[1]);
            } else {
                $missingIndexes[] = $index;
            }
        }
        if ($missingIndexes === []) {
            return trim(implode("\n", $lines));
        }
        $remainingPoint = $point > $existingPointTotal ? $point - $existingPointTotal : 0.0;
        $pointPerMissingLine = $remainingPoint > 0 ? $remainingPoint / count($missingIndexes) : $point / count($missingIndexes);
        foreach ($missingIndexes as $index) {
            $lines[$index] = rtrim($lines[$index]).' ['.$this->formatImportedPoint($pointPerMissingLine).' điểm]';
        }
        return trim(implode("\n", $lines));
    }

    private function formatImportedPoint(float $point): string
    {
        $formatted = rtrim(rtrim(number_format($point, 2, ',', '.'), '0'), ',');
        return str_contains($formatted, ',') ? $formatted : $formatted.',0';
    }

    private function parseAnswerAfterOptionsRows(array $lines): array
    {
        if (! collect($lines)->contains(fn ($line) => preg_match('/^\s*answer\s*:/iu', trim($line)))) return [];

        $rows = [];
        $question = null;
        $options = [];
        $paper = 1;
        $flush = function () use (&$rows, &$question, &$options, &$paper): void {
            if ($question !== null && count($options) >= 2) {
                $rows[] = [
                    'paper' => $paper,
                    'content' => trim($question),
                    'answer' => '',
                    'options' => $options,
                    'question_type' => 'multiple_choice',
                    'points' => 1,
                ];
            }
            $question = null;
            $options = [];
        };

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') continue;
            if (preg_match('/^b(?:ài|ai)\s*(\d+)\s*:\s*\d+\s*c(?:âu|au)/iu', $line, $paperMatch)) {
                $flush();
                $paper = max(1, (int) $paperMatch[1]);
                continue;
            }

            if (preg_match('/^answer\s*:\s*([A-D])/iu', $line, $answerMatch)) {
                if ($question !== null && count($options) >= 2) {
                    $rows[] = [
                        'paper' => $paper,
                        'content' => trim($question),
                        'answer' => strtoupper($answerMatch[1]),
                        'options' => $options,
                        'question_type' => 'multiple_choice',
                        'points' => 1,
                    ];
                }
                $question = null;
                $options = [];
                continue;
            }

            if (preg_match('/^([A-D])\s*[.)]?\s+(.+)$/u', $line, $optionMatch)) {
                if ($question !== null) $options[strtoupper($optionMatch[1])] = trim($optionMatch[2]);
                continue;
            }

            if ($question === null) {
                $question = $line;
            } elseif ($options === []) {
                $question .= "\n".$line;
            }
        }

        $flush();
        return $rows;
    }

    /**
     * Chuẩn format nội bộ sau khi đã đọc XML và nhận diện cấu trúc Word.
     * Mỗi file được giữ thành một bộ đề riêng; không đổi bảng mã nội dung.
     */
    private function standardizeImportedRows(array $rows): array
    {
        $questionNumber = 1;
        $essayRows = [];
        $standard = [];
        $essayNumbers = [];
        $multiPaper = collect($rows)->pluck('paper')->unique()->count() > 1;
        foreach ($rows as $row) {
            $row['paper'] = (int) ($row['paper'] ?? 1) ?: 1;
            $row['content'] = trim((string) ($row['content'] ?? ''));
            // Tiêu đề phân mục trong Word chỉ dùng để xác định bài, không phải câu hỏi.
            if (preg_match('/^b(?:ài|ai)\s*\d+\s*:\s*\d+\s*c(?:âu|au)\b/iu', $row['content'])) {
                continue;
            }
            $row['content'] = preg_replace('/^\s*b(?:ài|ai)\s*\d+\s*[:.)-]\s*\d+\s*c(?:âu|au)\s*/iu', '', $row['content']) ?: $row['content'];
            $scoreFromContent = null;
            if (preg_match('/\s*([\d]+[,.]\d+)\s*(?:\(\s*[\d]+(?:[,.]\d+)?\s*(?:điểm|diem)\s*\))?\s*$/iu', $row['content'], $scoreMatch)) {
                $scoreFromContent = (float) str_replace(',', '.', $scoreMatch[1]);
            }
            // Không để tổng barem của câu (ví dụ "4,0") dính vào nội dung
            // khi Word xuất điểm ngay sau dòng câu hỏi.
            $row['content'] = preg_replace('/\s*\(\s*[\d]+(?:[,.]\d+)?\s*(?:điểm|diem)\s*\)\s*$/iu', '', $row['content']) ?: $row['content'];
            $row['content'] = preg_replace('/\s*[\d]+[,.]\d+\s*$/u', '', $row['content']) ?: $row['content'];
            $row['content'] = trim($row['content']);
            if (($row['question_type'] ?? '') === 'essay' && $scoreFromContent !== null && $scoreFromContent > 0) {
                $row['points'] = $scoreFromContent;
            }
            $row['points'] = (float) ($row['points'] ?? 1) > 0 ? (float) $row['points'] : 1;
            $row['answer'] = $this->ensureAnswerHasPoint(trim((string) ($row['answer'] ?? '')), $row['points']);
            $row['options'] = is_array($row['options'] ?? null) ? $row['options'] : [];
            $row['options'] = array_intersect_key($row['options'], array_flip(['A', 'B', 'C', 'D']));
            if (($row['question_type'] ?? '') === 'essay') {
                $paper = $row['paper'];
                $row['question'] = $multiPaper
                    ? ($essayNumbers[$paper] = ($essayNumbers[$paper] ?? 0) + 1)
                    : 61;
                $row['question_type'] = 'essay';
                $essayRows[] = $row;
                continue;
            }
            $row['question'] = $questionNumber++;
            $row['question_type'] = 'multiple_choice';
            $standard[] = $row;
        }
        foreach ($essayRows as $row) $standard[] = $row;
        return $standard;
    }

    private function normalizeQuestionRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (($row['question_type'] ?? '') !== 'multiple_choice') continue;
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($row['content'] ?? '')) ?: []), fn ($line) => $line !== ''));
            if (count($lines) >= 2 && count($row['options'] ?? []) > 0) {
                $row['content'] = array_shift($lines);
                $unlabelled = [];
                foreach ($lines as $line) {
                    if (preg_match('/^([A-D])(?:\s*[.)]\s*|\s+)(.*)$/iu', $line, $match)) $row['options'][strtoupper($match[1])] = trim($match[2]);
                    else $unlabelled[] = $line;
                }
                foreach (['A','B','C','D'] as $key) if (!isset($row['options'][$key]) && $unlabelled) $row['options'][$key] = array_shift($unlabelled);
                continue;
            }
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($row['content'] ?? '')) ?: []), fn ($line) => $line !== ''));
            if (count($lines) >= 5) {
                $row['content'] = array_shift($lines);
                foreach (array_slice($lines, 0, 4) as $index => $option) $row['options'][chr(65 + $index)] = preg_replace('/^[A-D][.)]\s*/iu', '', $option) ?: $option;
                continue;
            }
            if (preg_match('/^(.*?\bchính)\s*((?:[2-5]\s*nhóm\s*){2,})$/iu', trim((string) ($row['content'] ?? '')), $match)) {
                $row['content'] = trim($match[1]);
                preg_match_all('/([2-5])\s*nhóm/iu', $match[2], $numbers);
                foreach ($numbers[1] ?? [] as $index => $number) $row['options'][chr(65 + $index)] = $number.' nhóm';
            }
        }
        unset($row);
        return $rows;
    }

    private function parseAnswerRows(string $text): array
    {
        $result = [];
        $text = str_replace(["\x07", "\x0b"], "\t", $text);
        $paper = 1;
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^(?:đề|de)\s*(?:thi\s*)?(?:số|so)?\s*(\d+)/iu', $line, $paperMatch)) {
                $paper = max(1, (int) $paperMatch[1]);
                continue;
            }
            if (preg_match('/^(?:đáp\s*án\s*)?(?:câu\s*)?0*(\d+)\s*(?:(?:[:.\-\t|;])\s*|\s+)([A-D])(?:\s|\t|\||;|$)/iu', $line, $m)) {
                $result[$paper.':'.(int)$m[1]] = strtoupper($m[2]);
            } elseif (preg_match('/^đáp\s*án\s*(?:câu\s*)?0*(\d+)\s*[:.\-]\s*(.*)$/iu', $line, $m)) {
                $result[$paper.':'.(int)$m[1]] = trim($m[2]);
            }
        }
        return $result;
    }
}
