<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Requests\StoreConversionRecordRequest;
use Modules\StandardHours\Requests\UpdateConversionRecordRequest;
use Modules\StandardHours\Services\ConversionService;
use Modules\StandardHours\Support\InstructorScope;

/**
 * Kê khai hoạt động chuyên môn (HĐCM) — bản sao native trong shell LMS,
 * chỉ tự phục vụ GV (không có duyệt/từ chối — reviewer-only ở admin shell).
 * Không đụng modules/StandardHours — chỉ tái sử dụng Service/Request qua DI.
 */
class ConversionRecordController extends Controller
{
    public function __construct(
        private readonly ConversionService $conversionService
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $conversionRecords = $this->conversionService->paginate($request->all());
        $assessmentCounts = $this->conversionService->assessmentCounts($request->all());
        $filterOptions = $this->conversionService->getFilterOptions();

        return view('lms::teach.standard-hours.conversion-records.index', array_merge(
            compact('conversionRecords', 'assessmentCounts'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->conversionService->getFilterOptions();

        return view('lms::teach.standard-hours.conversion-records.create', $filterOptions);
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
            ->route('lms.teach.standard-hours.conversion-records.show', $record)
            ->with('success', 'Kê khai hoạt động chuyên môn đã được lưu!');
    }

    public function show(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);
        $conversionRecord->load(['instructor.unit', 'conversionCategory', 'creator', 'updater', 'approver']);

        return view('lms::teach.standard-hours.conversion-records.show', compact('conversionRecord'));
    }

    public function edit(ConversionRecord $conversionRecord)
    {
        InstructorScope::ensureOwnsRecord($conversionRecord->instructor_id);

        if (! $conversionRecord->canBeEditedBy(auth()->user())) {
            return redirect()
                ->route('lms.teach.standard-hours.conversion-records.show', $conversionRecord)
                ->with('error', 'Không thể sửa kê khai ở trạng thái hiện tại.');
        }

        $filterOptions = $this->conversionService->getFilterOptions();

        return view('lms::teach.standard-hours.conversion-records.edit', array_merge(
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
            ->route('lms.teach.standard-hours.conversion-records.show', $conversionRecord)
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
            ->route('lms.teach.standard-hours.conversion-records.index')
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
}
