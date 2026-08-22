<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Requests\StoreResearchRecordRequest;
use Modules\StandardHours\Requests\UpdateResearchRecordRequest;
use Modules\StandardHours\Services\ResearchService;
use Modules\StandardHours\Support\InstructorScope;

/**
 * Kê khai NCKH — bản sao native trong shell LMS, chỉ tự phục vụ GV
 * (không có duyệt/từ chối — reviewer-only ở admin shell).
 * Không đụng modules/StandardHours — chỉ tái sử dụng Service/Request qua DI.
 */
class ResearchRecordController extends Controller
{
    public function __construct(
        private readonly ResearchService $researchService
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $researchRecords = $this->researchService->paginate($request->all());
        $assessmentCounts = $this->researchService->assessmentCounts($request->all());
        $filterOptions = $this->researchService->getFilterOptions();

        return view('lms::teach.standard-hours.research-records.index', array_merge(
            compact('researchRecords', 'assessmentCounts'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->researchService->getFilterOptions();

        return view('lms::teach.standard-hours.research-records.create', $filterOptions);
    }

    public function store(StoreResearchRecordRequest $request)
    {
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $record = $this->researchService->create(
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.research-records.show', $record)
            ->with('success', 'Kê khai NCKH đã được lưu!');
    }

    public function show(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);
        $researchRecord->load(['instructor.unit', 'researchCategory', 'creator', 'updater', 'approver', 'members.instructor.unit']);

        return view('lms::teach.standard-hours.research-records.show', compact('researchRecord'));
    }

    public function edit(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        if (! $researchRecord->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('lms.teach.standard-hours.research-records.show', $researchRecord)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        $filterOptions = $this->researchService->getFilterOptions();

        return view('lms::teach.standard-hours.research-records.edit', array_merge(
            compact('researchRecord'),
            $filterOptions
        ));
    }

    public function update(UpdateResearchRecordRequest $request, ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $this->researchService->update(
                $researchRecord,
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.research-records.show', $researchRecord)
            ->with('success', 'Kê khai NCKH đã được cập nhật!');
    }

    public function destroy(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        try {
            $this->researchService->delete($researchRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.research-records.index')
            ->with('success', 'Kê khai NCKH đã được xóa!');
    }

    public function submit(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        try {
            $this->researchService->submit($researchRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai NCKH đã được gửi thẩm định!');
    }
}
