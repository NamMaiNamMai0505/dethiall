<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use App\Support\ApprovalAgency;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Requests\StoreResearchRecordRequest;
use Modules\StandardHours\Requests\UpdateResearchRecordRequest;
use Modules\StandardHours\Services\ResearchService;
use Modules\StandardHours\Support\InstructorScope;

class ResearchController extends StandardHoursBaseController
{
    protected bool $allowInstructorAccess = true;

    public function __construct(
        private readonly ResearchService $researchService
    ) {
        parent::__construct();
        // Nộp hồ sơ = quyền ghi trên chính ứng dụng Kê khai NCKH.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))->only(['submit']);
        $this->middleware('permission:standard-hours.research-records.approve')->only(['approve', 'reject']);
    }

    public function index(Request $request)
    {
        $researchRecords = $this->researchService->paginate($request->all());
        $assessmentCounts = $this->researchService->assessmentCounts($request->all());
        $filterOptions = $this->researchService->getFilterOptions();

        return view('standardhours::research-records.index', array_merge(
            compact('researchRecords', 'assessmentCounts'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->researchService->getFilterOptions();

        return view('standardhours::research-records.create', $filterOptions);
    }

    public function store(StoreResearchRecordRequest $request)
    {
        $this->ensureCanManageInstructor($request->integer('instructor_id'));

        try {
            $record = $this->researchService->create(
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.research-records.show', $record)
            ->with('success', 'Kê khai NCKH đã được lưu!');
    }

    public function show(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);
        $researchRecord->load(['instructor.unit', 'researchCategory', 'creator', 'updater', 'approver', 'members.instructor.unit']);

        return view('standardhours::research-records.show', compact('researchRecord'));
    }

    public function edit(ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        if (! $researchRecord->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('standard-hours.research-records.show', $researchRecord)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        $filterOptions = $this->researchService->getFilterOptions();

        return view('standardhours::research-records.edit', array_merge(
            compact('researchRecord'),
            $filterOptions
        ));
    }

    public function update(UpdateResearchRecordRequest $request, ResearchRecord $researchRecord)
    {
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);
        $this->ensureCanManageInstructor($request->integer('instructor_id'));

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
            ->route('standard-hours.research-records.show', $researchRecord)
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
            ->route('standard-hours.research-records.index')
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

    public function approve(ResearchRecord $researchRecord)
    {
        ApprovalAgency::ensureCanReviewResearch(auth()->user());
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        try {
            $this->researchService->approve($researchRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai NCKH đã được thẩm định!');
    }

    public function reject(ResearchRecord $researchRecord)
    {
        ApprovalAgency::ensureCanReviewResearch(auth()->user());
        InstructorScope::ensureOwnsRecord($researchRecord->instructor_id, $researchRecord->id);

        try {
            $this->researchService->reject($researchRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai NCKH đã được chuyển về để bổ sung!');
    }

    private function ensureCanManageInstructor(int $instructorId): void
    {
        if ($instructorId <= 0) {
            abort(422, 'Kê khai NCKH phải có người khai.');
        }

        InstructorScope::ensureOwnsRecord($instructorId);
    }
}
