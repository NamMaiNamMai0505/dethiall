<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Requests\StoreExternalActivityRecordRequest;
use Modules\StandardHours\Requests\UpdateExternalActivityRecordRequest;
use Modules\StandardHours\Services\ExternalActivityService;
use Modules\StandardHours\Support\InstructorScope;

/**
 * Hoạt động ngoài HĐCM — bản sao native trong shell LMS, chỉ tự phục vụ GV
 * (không có duyệt/từ chối — reviewer-only ở admin shell). Không tính vào
 * giờ chuẩn, chỉ theo dõi riêng.
 * Không đụng modules/StandardHours — chỉ tái sử dụng Service/Request qua DI.
 */
class ExternalActivityController extends Controller
{
    public function __construct(
        private readonly ExternalActivityService $externalActivityService
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return view('lms::teach.standard-hours.external-activities.index', array_merge(
            ['records' => $this->externalActivityService->paginate($request->all())],
            $this->externalActivityService->filterOptions()
        ));
    }

    public function create()
    {
        return view(
            'lms::teach.standard-hours.external-activities.create',
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
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.external-activities.show', $record)
            ->with('success', 'Đã lưu hoạt động ngoài HĐCM.');
    }

    public function show(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        $externalActivity->load(['instructor.unit', 'creator', 'updater', 'approver']);

        return view('lms::teach.standard-hours.external-activities.show', [
            'record' => $externalActivity,
        ]);
    }

    public function edit(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);

        if (! $externalActivity->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('lms.teach.standard-hours.external-activities.show', $externalActivity)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        return view('lms::teach.standard-hours.external-activities.edit', array_merge(
            ['record' => $externalActivity],
            $this->externalActivityService->formOptions()
        ));
    }

    public function update(UpdateExternalActivityRecordRequest $request, ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);
        InstructorScope::ensureOwnsRecord((int) $request->validated('instructor_id'));

        try {
            $this->externalActivityService->update(
                $externalActivity,
                $request->safe()->except('evidence'),
                $request->file('evidence')
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.external-activities.show', $externalActivity)
            ->with('success', 'Đã cập nhật hoạt động ngoài HĐCM.');
    }

    public function destroy(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);

        try {
            $this->externalActivityService->delete($externalActivity);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.external-activities.index')
            ->with('success', 'Đã xóa kê khai.');
    }

    public function submit(ExternalActivityRecord $externalActivity)
    {
        InstructorScope::ensureOwnsRecord($externalActivity->instructor_id);

        try {
            $this->externalActivityService->submit($externalActivity);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi hoạt động để duyệt.');
    }
}
