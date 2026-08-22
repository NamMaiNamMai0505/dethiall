<?php

namespace Modules\StudentSchedule\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\StudentSchedule\Exports\StudentScheduleExport;
use Modules\StudentSchedule\Services\StudentScheduleService;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class StudentScheduleController extends Controller
{
    protected StudentScheduleService $service;

    public function __construct(StudentScheduleService $service)
    {
        $this->middleware('auth');
        $this->middleware('permission:student-schedule.index')->only(['index']);
        $this->service = $service;
    }

    public function index(Request $request)
    {
        // Check if user is student
        if (! auth()->user()->isStudent()) {
            abort(403, 'Chỉ học sinh mới có quyền truy cập trang này');
        }

        $user = auth()->user();
        $classId = $user->class_id;

        // If student doesn't have a class assigned
        if (! $classId) {
            return view('studentschedule::index', [
                'calendar' => [],
                'stats' => [
                    'total_subjects' => 0,
                    'theory_hours' => 0,
                    'practice_hours' => 0,
                    'self_study_hours' => 0,
                    'exam_hours' => 0,
                    'total_hours' => 0,
                ],
                'weekRangeLabel' => '',
                'weekOffset' => 0,
                'student' => $user,
                'noClass' => true,
            ]);
        }

        // Get active training schedule for student's class
        $today = now()->toDateString();
        $activeTrainingSchedule = TrainingSchedule::where('class_id', $classId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        // If no active training schedule found
        if (! $activeTrainingSchedule) {
            return view('studentschedule::index', [
                'calendar' => [],
                'stats' => [
                    'total_subjects' => 0,
                    'theory_hours' => 0,
                    'practice_hours' => 0,
                    'self_study_hours' => 0,
                    'exam_hours' => 0,
                    'total_hours' => 0,
                ],
                'weekRangeLabel' => '',
                'weekOffset' => 0,
                'student' => $user,
                'noSchedule' => true,
            ]);
        }

        // Get date range from request (custom range or week-based)
        $dateRange = $this->service->getDateRangeFromRequest($request);
        $weekOffset = $dateRange['offset'] ?? (int) $request->get('week_offset', 0);

        // Query schedules for the date range from the active training schedule
        $schedules = ScheduleDetail::with([
            'subject',
            'classroom.building',
        ])
            ->where('training_schedule_id', $activeTrainingSchedule->id)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('date', 'asc')
            ->orderBy('period', 'asc')
            ->get();

        // Build weekly calendar structure
        $calendar = $this->service->buildWeeklyCalendar($schedules, $dateRange);

        // Calculate statistics for this period
        $stats = $this->service->calculateWeekStatistics($schedules);

        // Get date range label for display
        $dateRangeLabel = $this->service->getDateRangeLabel($dateRange);

        return view('studentschedule::index', [
            'calendar' => $calendar,
            'stats' => $stats,
            'dateRangeLabel' => $dateRangeLabel,
            'weekOffset' => $weekOffset,
            'student' => $user,
            'dateRange' => $dateRange,
            'activeTrainingSchedule' => $activeTrainingSchedule,
        ]);
    }

    public function export(Request $request)
    {
        // Check if user is student
        if (! auth()->user()->isStudent()) {
            abort(403, 'Chỉ học sinh mới có quyền xuất lịch');
        }

        $user = auth()->user();
        $classId = $user->class_id;

        // If student doesn't have a class assigned
        if (! $classId) {
            return back()->with('error', 'Bạn chưa được xếp vào lớp nào');
        }

        // Get active training schedule for student's class
        $today = now()->toDateString();
        $activeTrainingSchedule = TrainingSchedule::where('class_id', $classId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        // If no active training schedule found
        if (! $activeTrainingSchedule) {
            return back()->with('error', 'Không tìm thấy lịch đào tạo đang hoạt động cho lớp của bạn');
        }

        // Validate
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Get export data
        $scheduleData = $this->service->getScheduleDetailsForExport(
            $activeTrainingSchedule->id,
            $validated['start_date'],
            $validated['end_date']
        );

        // Get class name
        $className = $user->class?->name ?? 'N/A';

        // Generate filename
        $filename = 'lich_hoc_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new StudentScheduleExport(
                $scheduleData,
                $validated['start_date'],
                $validated['end_date'],
                $user,
                $className
            ),
            $filename
        );
    }
}
