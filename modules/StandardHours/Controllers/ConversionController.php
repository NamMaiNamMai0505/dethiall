<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use App\Support\ApprovalAgency;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Requests\StoreConversionRecordRequest;
use Modules\StandardHours\Requests\UpdateConversionRecordRequest;
use Modules\StandardHours\Services\ConversionService;
use Modules\StandardHours\Support\InstructorScope;

class ConversionController extends StandardHoursBaseController
{
    protected bool $allowInstructorAccess = true;

    public function __construct(
        private readonly ConversionService $conversionService
    ) {
        parent::__construct();
        // Nộp hồ sơ = quyền ghi trên chính ứng dụng Kê khai HĐCM.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))->only(['submit']);
        $this->middleware('permission:standard-hours.conversion-records.approve')->only(['approve', 'reject']);
    }

    public function index(Request $request)
    {
        $conversionRecords = $this->conversionService->paginate($request->all());
        $assessmentCounts = $this->conversionService->assessmentCounts($request->all());
        $filterOptions = $this->conversionService->getFilterOptions();

        return view('standardhours::conversion-records.index', array_merge(
            compact('conversionRecords', 'assessmentCounts'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->conversionService->getFilterOptions();

        return view('standardhours::conversion-records.create', $filterOptions);
    }

    public function store(StoreConversionRecordRequest $request)
    {
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $record = $this->conversionService->create(
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.conversion-records.show', $record)
            ->with('success', 'Kê khai hoạt động chuyên môn đã được lưu!');
    }

    public function show(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);
        $conversionRecord->load(['instructor.unit', 'conversionCategory', 'creator', 'updater', 'approver']);

        return view('standardhours::conversion-records.show', compact('conversionRecord'));
    }

    public function edit(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        if (! $conversionRecord->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('standard-hours.conversion-records.show', $conversionRecord)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        $filterOptions = $this->conversionService->getFilterOptions();

        return view('standardhours::conversion-records.edit', array_merge(
            compact('conversionRecord'),
            $filterOptions
        ));
    }

    public function update(UpdateConversionRecordRequest $request, ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $this->conversionService->update(
                $conversionRecord,
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.conversion-records.show', $conversionRecord)
            ->with('success', 'Kê khai đã được cập nhật!');
    }

    public function destroy(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        try {
            $this->conversionService->delete($conversionRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.conversion-records.index')
            ->with('success', 'Kê khai đã được xóa!');
    }

    public function submit(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        try {
            $this->conversionService->submit($conversionRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai đã được gửi thẩm định!');
    }

    public function approve(ConversionRecord $conversionRecord)
    {
        ApprovalAgency::ensureCanReviewProfessionalActivities(auth()->user());
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        try {
            $this->conversionService->approve($conversionRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai đã được thẩm định!');
    }

    public function reject(ConversionRecord $conversionRecord)
    {
        ApprovalAgency::ensureCanReviewProfessionalActivities(auth()->user());
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        try {
            $this->conversionService->reject($conversionRecord);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kê khai đã được chuyển về để bổ sung!');
    }
}
