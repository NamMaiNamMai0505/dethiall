<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use App\Support\ApprovalAgency;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Requests\StoreExternalActivityRecordRequest;
use Modules\StandardHours\Requests\UpdateExternalActivityRecordRequest;
use Modules\StandardHours\Services\ExternalActivityService;
use Modules\StandardHours\Support\InstructorScope;

class ExternalActivityController extends StandardHoursBaseController
{
    protected bool $allowInstructorAccess = true;

    public function __construct(
        private readonly ExternalActivityService $externalActivityService
    ) {
        parent::__construct();

        // Nộp hồ sơ = quyền ghi trên chính ứng dụng này, không phải quyền tổng.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))->only(['submit']);
        $this->middleware('permission:standard-hours.external-activities.approve')->only(['approve', 'reject']);
    }

    public function index(Request $request)
    {
        return view('standardhours::external-activities.index', array_merge(
            [
                'records' => $this->externalActivityService->paginate($request->all()),
            ],
            $this->externalActivityService->filterOptions()
        ));
    }

    public function create()
    {
        return view(
            'standardhours::external-activities.create',
            $this->externalActivityService->formOptions()
        );
    }

    public function store(StoreExternalActivityRecordRequest $request)
    {
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $record = $this->externalActivityService->create(
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('standard-hours.external-activities.show', $record)
            ->with('success', 'Đã lưu hoạt động ngoài HĐCM.');
    }

    public function show(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        $externalActivity->load(['instructor.unit', 'creator', 'updater', 'approver']);

        return view('standardhours::external-activities.show', [
            'record' => $externalActivity,
        ]);
    }

    public function edit(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);

        if (! $externalActivity->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('standard-hours.external-activities.show', $externalActivity)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        return view('standardhours::external-activities.edit', array_merge(
            ['record' => $externalActivity],
            $this->externalActivityService->formOptions()
        ));
    }

    public function update(
        UpdateExternalActivityRecordRequest $request,
        ExternalActivityRecord $externalActivity
    ) {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $this->externalActivityService->update(
                $externalActivity,
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('standard-hours.external-activities.show', $externalActivity)
            ->with('success', 'Đã cập nhật hoạt động ngoài HĐCM.');
    }

    public function destroy(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        try {
            $this->externalActivityService->delete($externalActivity);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('standard-hours.external-activities.index')
            ->with('success', 'Đã xóa kê khai.');
    }

    public function submit(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        try {
            $this->externalActivityService->submit($externalActivity);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã gửi hoạt động để duyệt.');
    }

    public function approve(Request $request, ExternalActivityRecord $externalActivity)
    {
        $this->ensureReviewer($externalActivity);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        try {
            $this->externalActivityService->approve($externalActivity, $data['review_note'] ?? null);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã duyệt hoạt động ngoài HĐCM.');
    }

    public function reject(Request $request, ExternalActivityRecord $externalActivity)
    {
        $this->ensureReviewer($externalActivity);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        try {
            $this->externalActivityService->reject($externalActivity, $data['review_note']);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã từ chối kê khai và gửi phản hồi.');
    }

    private function ensureReviewer(ExternalActivityRecord $record): void
    {
        ApprovalAgency::ensureCanReviewProfessionalActivities(auth()->user());
        InstructorScope::ensureOwnsRecord($record->instructor_id);
    }
}
