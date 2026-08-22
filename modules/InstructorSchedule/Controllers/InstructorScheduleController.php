<?php

namespace Modules\InstructorSchedule\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\InstructorSchedule\Exports\InstructorScheduleExport;
use Modules\InstructorSchedule\Services\InstructorScheduleService;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class InstructorScheduleController extends Controller
{
    protected InstructorScheduleService $service;

    public function __construct(InstructorScheduleService $service)
    {
        $this->middleware('auth');
        $this->middleware('permission:instructor-schedule.index')->only(['index']);
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'required_with:date_to', 'date'],
            'date_to' => ['nullable', 'required_with:date_from', 'date', 'after_or_equal:date_from'],
            'week_offset' => ['nullable', 'integer', 'min:-520', 'max:520'],
            'tab' => ['nullable', 'in:schedule,statistics'],
        ], [
            'date_from.required_with' => 'Vui lòng chọn đầy đủ ngày bắt đầu.',
            'date_to.required_with' => 'Vui lòng chọn đầy đủ ngày kết thúc.',
            'date_to.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ]);

        // Check if user is instructor or internal user
        if (! auth()->user()->isInstructor() && !auth()->user()->isInternalUser()) {
            abort(403, 'Chỉ giảng viên mới có quyền truy cập trang này');
        }

        $instructorId = auth()->user()->instructor_id;
        $instructor = auth()->user()->instructor;

        // Get date range from request (custom range or week-based)
        $dateRange = $this->service->getDateRangeFromRequest($request);
        $weekOffset = $dateRange['offset'] ?? (int) $request->get('week_offset', 0);

        // Query schedules for the date range
        $schedules = ScheduleDetail::with([
            'subject',
            'classroom.building',
            'trainingSchedule.classModel',
        ])
            ->where('instructor_id', $instructorId)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('date', 'asc')
            ->orderBy('period', 'asc')
            ->get();

        // Build weekly calendar structure
        $calendar = $this->service->buildWeeklyCalendar($schedules, $dateRange);

        // Calculate statistics for this period
        $stats = $this->service->calculateRangeStatistics($schedules);

        // Get date range label for display
        $dateRangeLabel = $this->service->getDateRangeLabel($dateRange);
        $activeTab = $validated['tab'] ?? 'schedule';
        $isCustomRange = $request->filled('date_from') && $request->filled('date_to');
        $lessonTypes = $this->service->getLessonTypes();

        return view('instructorschedule::index', compact(
            'calendar',
            'stats',
            'dateRangeLabel',
            'weekOffset',
            'instructor',
            'dateRange',
            'activeTab',
            'isCustomRange',
            'lessonTypes'
        ));
    }

    public function export(Request $request)
    {
        // Check if user is instructor
        if (! auth()->user()->isInstructor()) {
            abort(403, 'Chỉ giảng viên mới có quyền xuất lịch');
        }

        // Validate
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $instructorId = auth()->user()->instructor_id;
        $instructor = auth()->user()->instructor;

        // Get export data
        $scheduleData = $this->service->getScheduleDetailsForExport(
            $instructorId,
            $validated['start_date'],
            $validated['end_date']
        );

        // Generate filename
        $filename = 'lich_giang_day_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new InstructorScheduleExport(
                $scheduleData,
                $validated['start_date'],
                $validated['end_date'],
                $instructor
            ),
            $filename
        );
    }
}
