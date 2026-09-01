<?php

namespace Modules\TrainingSchedule\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\ManagerUnitScope;
use App\Support\TrainingDept;
use App\Support\TrainingScheduleAccess;
use App\Support\WordExportTemplate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Specialization\Models\Specialization;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\TrainingSchedule\Requests\CreateTrainingScheduleRequest;
use Modules\TrainingSchedule\Requests\UpdateTrainingScheduleRequest;
use Modules\TrainingSchedule\Services\TrainingExportService;
use Modules\TrainingSchedule\Services\TrainingScheduleService;
use PhpOffice\PhpWord\SimpleType\Jc;

class TrainingScheduleController extends ModuleBaseController
{
    protected $trainingScheduleService;

    public function __construct(TrainingScheduleService $trainingScheduleService)
    {
        parent::__construct();
        $this->trainingScheduleService = $trainingScheduleService;

        $this->middleware(function ($request, $next) {
            TrainingScheduleAccess::ensureValidRoleConfiguration();

            return $next($request);
        });

        $this->middleware('permission:training-schedules.index')->only([
            'calendar',
            'getClasses',
            'getAllScheduleDetails',
            'getFilteredTrainingSchedules',
            'export',
            'exportScheduleDetails',
            'exportTrainingPlan',
            'exportFacultyPlan',
        ]);
        $this->middleware('permission:training-schedules.show')->only(['getSubjectHourUsage']);
        $this->middleware('permission:training-schedules.edit')->only(['toggleStatus']);
        $this->middleware('permission:schedule-details.create|schedule-details.edit')->only(['navigateScheduleDetail']);
        $this->middleware('permission:schedule-details.create')->only(['createScheduleDetail', 'storeScheduleDetail']);
        $this->middleware('permission:schedule-details.edit')->only([
            'editScheduleDetail',
            'updateScheduleDetail',
            'destroyScheduleDetail',
        ]);
    }

    /**
     * Display a listing of training schedules
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        // Apply filters using service and paginate
        $schedules = $this->trainingScheduleService->getFilteredSchedules($request, true)
            ->paginate(15)->withQueryString();

        // Get filter options and add classes
        $filterOptions = $this->trainingScheduleService->getFilterOptions();
        $filterOptions['classes'] = ClassModel::active()
            ->select('id', 'name', 'code', 'specialization_id')
            ->when($request->filled('specialization_id'), function ($query) use ($request) {
                $query->where('specialization_id', $request->specialization_id);
            })
            ->orderBy('name')
            ->get();
        $filterOptions['classrooms'] = Classroom::active()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('training-schedule::index', compact('schedules') + $filterOptions);
    }

    /**
     * Show the form for creating a new training schedule
     */
    public function create()
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();

        return view('training-schedule::create', $this->getFormData());
    }

    /**
     * Store a newly created training schedule
     */
    public function store(CreateTrainingScheduleRequest $request)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();

        try {
            $data = $request->validated();

            // Sinh code tự động nếu chưa có
            if (empty($data['code'])) {
                $data['code'] = $this->trainingScheduleService->generateUniqueCode($data['name']);
            }

            // If class_code is provided, look up and set class_id
            if (! empty($data['class_code'])) {
                $class = ClassModel::where('code', $data['class_code'])->first();
                if ($class) {
                    $data['class_id'] = $class->id;
                }
            }

            // Xử lý dữ liệu periods (nhiều tiết/ngày)
            /* $periods = $request->input('periods', []);
            $data['weekly_schedule'] = $this->trainingScheduleService->buildWeeklyScheduleFromPeriods(
                $data['start_date'],
                $data['end_date'],
                $periods
            ); */

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $schedule = TrainingSchedule::create($data);

            return redirect()
                ->route('training-schedules.show', $schedule->id)
                ->with('success', 'Lịch đào tạo đã được tạo thành công!');
        } catch (\Throwable $e) {
            // Log error for debugging (server-side only)
            \Log::error('Failed to create training schedule', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            // Show user-friendly error message (no stack trace)
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo lịch đào tạo. Vui lòng kiểm tra lại thông tin và thử lại.');
        }
    }

    /**
     * Display the specified training schedule
     */
    public function show(Request $request, TrainingSchedule $trainingSchedule)
    {
        // Permission already checked by middleware
        $trainingSchedule->load([
            'specialization',
            'creator',
            'updater',
            'classModel',
        ]);

        // === Pagination logic: Load only 4 weeks at a time ===
        $weeksPerPage = 4;
        $page = max(1, (int) $request->get('page', 1));

        // Calculate the start date for the current page
        $scheduleStart = $trainingSchedule->start_date->copy()->startOfWeek(CarbonInterface::MONDAY);
        $scheduleEnd = $trainingSchedule->end_date->copy()->endOfWeek(CarbonInterface::SUNDAY);

        // Calculate page range start date
        $pageStartDate = $scheduleStart->copy()->addWeeks(($page - 1) * $weeksPerPage);

        // If the current date is within the schedule range, find which page it belongs to
        if ($page === 1 && $request->get('page') === null) {
            $today = Carbon::today();
            if ($today->between($trainingSchedule->start_date, $trainingSchedule->end_date)) {
                // Find which page contains today's date
                $weeksFromStart = $scheduleStart->diffInWeeks($today->copy()->startOfWeek(CarbonInterface::MONDAY));
                $page = max(1, (int) floor($weeksFromStart / $weeksPerPage) + 1);
                $pageStartDate = $scheduleStart->copy()->addWeeks(($page - 1) * $weeksPerPage);
            }
        }

        // Calculate page range end date (4 weeks from start)
        $pageEndDate = $pageStartDate->copy()->addWeeks($weeksPerPage)->subDay()->endOfWeek(CarbonInterface::SUNDAY);

        // Ensure we don't exceed the schedule end date
        if ($pageEndDate->gt($scheduleEnd)) {
            $pageEndDate = $scheduleEnd->copy();
        }

        // Load only schedule details for the current 4-week range
        $scheduleDetails = $trainingSchedule->scheduleDetails()
            ->with(['subject', 'instructor', 'classroom'])
            ->whereBetween('date', [$pageStartDate->toDateString(), $pageEndDate->toDateString()])
            ->get();

        // === Build weeks array for the current page ===
        $weeks = [];
        $current = $pageStartDate->copy();

        while ($current->lte($pageEndDate) && count($weeks) < $weeksPerPage) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek(CarbonInterface::SUNDAY);

            // Ensure we don't exceed schedule end
            if ($weekEnd->gt($scheduleEnd)) {
                $weekEnd = $scheduleEnd->copy();
            }

            // Lấy schedule details thuộc tuần này
            $detailsInWeek = $scheduleDetails->filter(function ($detail) use ($weekStart, $weekEnd) {
                return Carbon::parse($detail->date)->between($weekStart, $weekEnd);
            });

            $weeks[] = [
                'start' => $weekStart->toDateString(),
                'end' => $weekEnd->toDateString(),
                'days' => collect(range(0, 6))->map(function ($i) use ($weekStart, $detailsInWeek) {
                    $day = $weekStart->copy()->addDays($i);

                    return [
                        'date' => $day,
                        'weekday' => $day->locale('vi')->isoFormat('dddd'), // Thứ bằng tiếng Việt
                        'details' => $detailsInWeek->filter(function ($d) use ($day) {
                            return Carbon::parse($d->date)->isSameDay($day);
                        }),
                    ];
                }),
            ];

            $current->addWeek();
        }

        // Calculate pagination info
        $totalWeeks = $scheduleStart->diffInWeeks($scheduleEnd) + 1;
        $totalPages = max(1, (int) ceil($totalWeeks / $weeksPerPage));
        $hasPrevious = $page > 1;
        $hasNext = $page < $totalPages;

        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_previous' => $hasPrevious,
            'has_next' => $hasNext,
            'page_start_date' => $pageStartDate->format('d/m/Y'),
            'page_end_date' => $pageEndDate->format('d/m/Y'),
        ];

        return view(
            'training-schedule::show',
            compact('trainingSchedule', 'weeks', 'pagination') + $this->scheduleRoleContext()
        );
    }

    /**
     * Show the form for editing the specified training schedule
     */
    public function edit(TrainingSchedule $trainingSchedule)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();

        return view('training-schedule::edit', compact('trainingSchedule') + $this->getFormData());
    }

    /**
     * Update the specified training schedule
     */
    public function update(UpdateTrainingScheduleRequest $request, TrainingSchedule $trainingSchedule)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();
        try {
            $data = $request->validated();

            // Sinh code tự động nếu chưa có
            if (empty($data['code'])) {
                $data['code'] = $this->trainingScheduleService->generateUniqueCode($data['name']);
            }

            // If class_code is provided, look up and set class_id
            if (! empty($data['class_code'])) {
                $class = ClassModel::where('code', $data['class_code'])->first();
                if ($class) {
                    $data['class_id'] = $class->id;
                }
            }

            $data['updated_by'] = Auth::id();

            $request->validate([
                // ... rules
                'start_date' => $trainingSchedule->hasScheduleDetails() ? 'required|date|same:old_start_date' : 'required|date', // Ví dụ: Nếu có details, chỉ cho same date cũ
                'end_date' => $trainingSchedule->hasScheduleDetails() ? 'required|date|same:old_end_date' : 'required|date',
            ]);

            $trainingSchedule->update($data);

            return redirect()
                ->route('training-schedules.show', $trainingSchedule->id)
                ->with('success', 'Lịch đào tạo đã được cập nhật thành công!');
        } catch (\Throwable $e) {
            // Log error for debugging (server-side only)
            \Log::error('Failed to update training schedule', [
                'training_schedule_id' => $trainingSchedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            // Show user-friendly error message (no stack trace)
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật lịch đào tạo. Vui lòng kiểm tra lại thông tin và thử lại.');
        }
    }

    /**
     * Remove the specified training schedule
     */
    public function destroy(TrainingSchedule $trainingSchedule)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();
        $trainingSchedule->scheduleDetails()->delete();
        $trainingSchedule->delete();

        return redirect()
            ->route('training-schedules.index')
            ->with('success', 'Lịch đào tạo đã được xóa thành công!');
    }

    /**
     * Restore the specified training schedule
     */
    public function restore($id)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();
        $schedule = TrainingSchedule::withTrashed()->findOrFail($id);
        $schedule->restore();

        return redirect()
            ->route('training-schedules.show', $schedule->id)
            ->with('success', 'Lịch đào tạo đã được khôi phục thành công!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(TrainingSchedule $trainingSchedule)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();
        $trainingSchedule->update([
            'is_active' => ! $trainingSchedule->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $trainingSchedule->is_active ? 'kích hoạt' : 'tạm dừng';

        return redirect()
            ->back()
            ->with('success', "Lịch đào tạo đã được {$status} thành công!");
    }

    /**
     * Export training schedules to CSV
     */
    public function export(Request $request)
    {
        // Permission already checked by middleware
        $schedules = $this->trainingScheduleService->getFilteredSchedules($request);

        $filename = 'training_schedules_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($schedules) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Mã',
                'Tên lịch đào tạo',
                'Ngành đào tạo',
                'Lớp học',
                'Năm học',
                'Học kỳ',
                'Ngày bắt đầu',
                'Ngày kết thúc',
                'Số tuần',
                'Tình trạng',
                'Người tạo',
                'Ngày tạo',
            ]);

            // CSV data
            foreach ($schedules as $schedule) {
                fputcsv($file, [
                    $schedule->code,
                    $schedule->name,
                    $schedule->specialization->name ?? 'N/A',
                    $schedule->class_id ?? 'N/A',
                    $schedule->academic_year,
                    $schedule->semester_text,
                    $schedule->start_date ? $schedule->start_date->format('d/m/Y') : '',
                    $schedule->end_date ? $schedule->end_date->format('d/m/Y') : '',
                    $schedule->weeks_count,
                    $schedule->is_active ? 'Hoạt động' : 'Tạm dừng',
                    $schedule->creator->name ?? 'N/A',
                    $schedule->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get calendar view of training schedules
     */
    public function calendar(Request $request)
    {
        // Permission already checked by middleware
        try {
            // Get filtered schedules
            $schedules = $this->trainingScheduleService->getFilteredSchedules($request);

            // Transform to calendar events
            $events = $this->trainingScheduleService->transformToCalendarEvents($schedules, $request);

            // Prepare filter info for debugging
            $filterInfo = [
                'total_schedules' => $schedules->count(),
                'filtered_events' => $events->count(),
                'applied_filters' => [
                    'month' => $request->get('month'),
                    'year' => $request->get('year', 2024),
                    'specialization_id' => $request->get('specialization_id'),
                    'academic_year' => $request->get('academic_year'),
                    'semester' => $request->get('semester'),
                ],
            ];

            // Lấy danh sách tất cả môn học, giảng viên, phòng học
            $subjectsQuery = Subject::query()->select('id', 'name', 'code', 'is_special_activity');
            TrainingDept::applySubjectFacultyScope($subjectsQuery);
            $subjects = $subjectsQuery->get();
            $instructorsQuery = Instructor::query()->select('id', 'name', 'unit_id');
            ManagerUnitScope::applyToInstructorQuery($instructorsQuery);
            $instructors = $instructorsQuery->get();
            $classrooms = Classroom::select('id', 'name')->get();

            // Lấy danh sách lớp và ngành đào tạo cho filter
            $classes = ClassModel::active()
                ->select('id', 'name', 'code', 'specialization_id')
                ->orderBy('name')
                ->get();
            $specializations = Specialization::active()
                ->with('trainingSystem:id,name')
                ->select('id', 'name', 'major_code', 'level', 'training_form', 'training_system_id')
                ->orderBy('name')
                ->get();

            // Lấy filter options khác (năm học, học kỳ...)
            $filterOptions = $this->trainingScheduleService->getFilterOptions();

            // Default view_type (có thể lấy từ request hoặc mặc định)
            $defaultViewType = $request->get('view_type', 'class_day');
            $currentDate = now()->format('Y-m-d');

            // lấy danh sách tất cả lịch đào tạo
            $trainingSchedules = TrainingSchedule::with('specialization')
                ->active()
                ->select('id', 'name', 'code', 'specialization_id', 'class_code', 'start_date', 'end_date')
                ->orderBy('name')
                ->get();

            $calendarOverview = $this->trainingScheduleService->getCalendarOverviewStatistics();

            return view(
                'training-schedule::calendar',
                compact(
                    'filterInfo',
                    'subjects',
                    'instructors',
                    'classrooms',
                    'classes',
                    'specializations',
                    'filterOptions',
                    'defaultViewType',
                    'events',
                    'currentDate',
                    'trainingSchedules',
                    'calendarOverview',
                ) + $this->scheduleRoleContext()
            );
        } catch (\Exception $e) {
            return redirect()->route('training-schedules.index')
                ->with('error', 'Có lỗi xảy ra khi tải calendar: '.$e->getMessage());
        }
    }

    /**
     * Get users for create/edit forms
     */
    private function getFormData()
    {
        return [
            'instructors' => Instructor::select('id', 'name', 'email')->orderBy('name')->get(),
            'specializations' => Specialization::active()
                ->with('trainingSystem:id,name')
                ->select('id', 'name', 'major_code', 'level', 'training_form', 'training_system_id')
                ->orderBy('name')
                ->get(),
            // Load all classes to show in dropdown
            'classes' => ClassModel::active()
                ->select('code', 'id', 'name', 'specialization_id')
                ->orderBy('name')
                ->get(),
            // Load all subjects to show in dropdown
            'subjects' => Subject::active()
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get(),
            // Load all classrooms to show in dropdown
            'classrooms' => Classroom::active()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Get classes by specialization for AJAX
     */
    public function getClasses(Request $request)
    {
        // Permission already checked by middleware
        // Support single specialization_id and array specialization_id[]
        $specializationIds = $request->input('specialization_id');
        if (! is_array($specializationIds)) {
            $specializationIds = $specializationIds !== null && $specializationIds !== ''
                ? [$specializationIds]
                : [];
        }
        $specializationIds = array_values(array_filter($specializationIds, function ($id) {
            return $id !== null && $id !== '';
        }));

        // Không chọn ngành → trả tất cả lớp; có chọn → lọc theo ngành
        $classes = ClassModel::active()
            ->when(! empty($specializationIds), function ($q) use ($specializationIds) {
                $q->whereIn('specialization_id', $specializationIds);
            })
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($classes);
    }

    /**
     * Smart navigate to create or edit mode based on whether date has existing data
     */
    public function navigateScheduleDetail(TrainingSchedule $trainingSchedule, Request $request)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        $date = $this->ensureDateWithinSchedule($trainingSchedule, (string) $request->get('date'));

        // Check if date has existing schedule details
        $hasData = $trainingSchedule->scheduleDetails()
            ->whereDate('date', $date)
            ->exists();

        if ($hasData) {
            return redirect()->route('training-schedules.schedule-details.edit', [
                'trainingSchedule' => $trainingSchedule,
                'date' => $date,
            ]);
        }

        if (TrainingDept::isFacultyManager()) {
            return redirect()
                ->route('training-schedules.show', $trainingSchedule)
                ->with('warning', 'Phòng Đào tạo chưa xếp khung môn cho ngày này; Khoa chưa thể phân công bài học/GV.');
        }

        return redirect()->route('training-schedules.schedule-details.create', [
            'trainingSchedule' => $trainingSchedule,
            'date' => $date,
        ]);
    }

    public function createScheduleDetail(TrainingSchedule $trainingSchedule, Request $request)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        abort_if(
            TrainingDept::isFacultyManager(),
            403,
            'Khoa chỉ được phân công trên ngày đã có khung lịch của Phòng Đào tạo.'
        );
        $date = $this->ensureDateWithinSchedule($trainingSchedule, (string) $request->get('date'));
        $scheduleDetails = collect(); // trống cho create

        // Use shared method to get subjects with availability
        $subjects = $this->getSubjectsWithAvailability($trainingSchedule);

        // Toàn bộ GV — dùng cho loại buổi Thi/kiểm tra (GV khảo thí: không lọc theo môn)
        $allInstructors = $this->getAllActiveInstructorsForExam();

        return view('training-schedule::schedule-detail-form', array_merge([
            'trainingSchedule' => $trainingSchedule,
            'date' => $date,
            'scheduleDetails' => $scheduleDetails,
            'mode' => 'create',
            'subjects' => $subjects,
            'allInstructors' => $allInstructors,
        ], $this->scheduleRoleContext()));
    }

    public function editScheduleDetail(TrainingSchedule $trainingSchedule, $date)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        $date = $this->ensureDateWithinSchedule($trainingSchedule, (string) $date);

        $scheduleDetails = $trainingSchedule->scheduleDetails()
            ->with(['subjectLesson', 'subject'])
            ->whereDate('date', $date)
            ->get();

        // Use edit method - shows ALL subjects including fully scheduled ones
        $subjects = $this->getSubjectsForEdit($trainingSchedule);

        // Khoa: bổ sung môn khoa khác đã xếp trong ngày (chỉ để hiển thị khoá, không sửa)
        if (TrainingDept::isFacultyManager()) {
            $ownedIds = collect(TrainingDept::facultySubjectIds() ?? []);
            $dayIds = $scheduleDetails->pluck('subject_id')->filter()->unique()->values();
            $missing = $dayIds->diff($subjects->pluck('id'));
            if ($missing->isNotEmpty()) {
                $extra = Subject::with([
                    'lessons' => fn ($q) => $q->whereNull('parent_id')->with('children')->orderBy('sort_order'),
                    'instructors' => fn ($q) => $q->select('instructors.id', 'instructors.name', 'instructors.code', 'instructors.unit_id'),
                ])->whereIn('id', $missing)->get();
                foreach ($extra as $s) {
                    $s->setAttribute('faculty_read_only', true);
                    $s->availability = $s->availability ?? [];
                }
                $subjects = $subjects->concat($extra)->values();
            }
            // Đánh dấu môn thuộc khoa
            $subjects = $subjects->map(function ($s) use ($ownedIds) {
                $s->setAttribute('faculty_owned', $ownedIds->contains((int) $s->id));
                if (! $s->getAttribute('faculty_read_only')) {
                    $s->setAttribute('faculty_read_only', ! $ownedIds->contains((int) $s->id));
                }

                return $s;
            });
        }

        $allInstructors = $this->getAllActiveInstructorsForExam();

        return view('training-schedule::schedule-detail-form', array_merge([
            'trainingSchedule' => $trainingSchedule,
            'date' => $date,
            'scheduleDetails' => $scheduleDetails,
            'mode' => 'edit',
            'subjects' => $subjects,
            'allInstructors' => $allInstructors,
            // Buổi đã học xong + hết ân hạn: chỉ Super Admin còn sửa được.
            'dateLocked' => TrainingScheduleAccess::isScheduleDetailDateLocked($date)
                && ! auth()->user()?->isSuperAdmin(),
        ], $this->scheduleRoleContext()));
    }

    /**
     * Ngữ cảnh phân quyền form: PDOT (khung lịch) vs Khoa (bài/GV).
     */
    protected function scheduleRoleContext(): array
    {
        $user = auth()->user();
        $isFaculty = TrainingDept::isFacultyManager($user);

        $standardHoursScheduleCategories = collect();
        if (Schema::hasTable('conversion_categories')) {
            $standardHoursScheduleCategories = ConversionCategory::query()
                ->active()
                ->where('entry_source', 'schedule')
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                    'conversion_method',
                    'coefficient',
                    'fixed_hours',
                    'unit',
                ]);
        }

        return [
            'isTrainingOffice' => TrainingDept::isTrainingOffice($user),
            'isFacultyManager' => $isFaculty,
            'canManageSkeleton' => TrainingDept::canManageScheduleSkeleton($user),
            'isFullScheduleAccess' => TrainingScheduleAccess::scope($user) === TrainingScheduleAccess::SCOPE_SYSTEM,
            'canAssignFaculty' => TrainingDept::canAssignFacultySchedule($user)
                || TrainingDept::isTrainingOffice($user) // PDOT xem/điều phối
                || ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()),
            'scopeLabel' => TrainingDept::scopeLabel($user),
            // Chỉ truyền unit khoa khi đúng là manager khoa (lọc GV)
            'facultyUnitId' => $isFaculty ? $user?->unit_id : null,
            'facultyUnitCode' => $isFaculty ? TrainingDept::facultyUnitCode($user) : null,
            'facultySubjectIds' => $isFaculty ? (TrainingDept::facultySubjectIds($user) ?? []) : null,
            'standardHoursScheduleCategories' => $standardHoursScheduleCategories,
        ];
    }

    /**
     * Danh sách GV dùng cho tiết Thi/kiểm tra (khảo thí): mọi GV đang hoạt động, không lọc môn.
     * Khoa: chỉ GV trong unit khoa.
     */
    protected function getAllActiveInstructorsForExam()
    {
        $query = Instructor::query()
            ->where(function ($q) {
                $q->where('status', Instructor::STATUS_ACTIVE)
                    ->orWhere('status', 'active')
                    ->orWhere('status', 1);
            })
            ->orderBy('name');

        $user = auth()->user();
        if (TrainingDept::isFacultyManager($user) && $user->unit_id) {
            $query->where('unit_id', $user->unit_id);
        }

        return $query->get(['id', 'name', 'code', 'unit_id']);
    }

    public function storeScheduleDetail(Request $request, TrainingSchedule $trainingSchedule)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        abort_if(
            TrainingDept::isFacultyManager(),
            403,
            'Khoa không được tự tạo khung tiết; hãy chỉnh sửa ngày đã được Phòng Đào tạo phân chia.'
        );
        $details = $this->validatedScheduleDetailPayload($request, $trainingSchedule);
        $errors = []; // Mảng để thu thập tất cả lỗi

        $isPdot = TrainingDept::canManageScheduleSkeleton();
        $isFaculty = TrainingDept::isFacultyManager();
        $isFullAccess = TrainingScheduleAccess::scope() === TrainingScheduleAccess::SCOPE_SYSTEM;
        // PDOT: cho phép khung chỉ có môn + loại (chưa GV/phòng)
        // Khoa: cần môn (đã có) + có thể gán bài/GV/phòng
        $validDetails = collect($details)->filter(function ($d) use ($isPdot, $isFaculty) {
            if (empty($d['subject_id']) || empty($d['lesson_type'])) {
                return false;
            }
            if ($isPdot && ! $isFaculty) {
                return true; // khung lịch
            }

            // Khoa / full: ưu tiên có GV hoặc bài học
            return ! empty($d['instructor_id'])
                || ! empty($d['subject_lesson_id'])
                || ! empty($d['classroom_id']);
        });

        if ($validDetails->isEmpty()) {
            return back()->withErrors([
                'general' => 'Phải có ít nhất một tiết gồm môn học và loại tiết.',
            ])->withInput();
        }
        $typeMap = [
            'theory' => 'Lý thuyết',
            'practice' => 'Thực hành',
            'self_study' => 'Tự học',
            'final_exam' => 'Thi/kiểm tra',
        ];
        $allowedStandardHoursCategoryIds = Schema::hasTable('conversion_categories')
            ? ConversionCategory::query()
                ->where('entry_source', 'schedule')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
            : collect();

        $facultyUnitIdStore = $isFaculty ? (auth()->user()?->unit_id) : null;
        $facultySubjectIdsStore = $isFaculty ? (TrainingDept::facultySubjectIds() ?? []) : null;

        foreach ($validDetails as $detail) {
            $period = $detail['period']; // Lấy period từ input

            $subjectId = $detail['subject_id'];
            $lessonType = $detail['lesson_type'];
            $standardHoursCategoryId = $this->nullableId(
                $detail['standard_hours_conversion_category_id'] ?? null
            );
            if (
                $standardHoursCategoryId
                && ! $allowedStandardHoursCategoryIds->contains($standardHoursCategoryId)
            ) {
                $errors["details.{$period}.standard_hours_conversion_category_id"]
                    = 'Danh mục quy đổi giờ chuẩn không hợp lệ.';

                continue;
            }

            // Khoa chỉ được thao tác môn có mã …Kn khớp unit khoa
            if ($isFaculty && is_array($facultySubjectIdsStore) && ! in_array((int) $subjectId, $facultySubjectIdsStore, true)) {
                $code = TrainingDept::facultyUnitCode() ?? 'Kx';
                $errors["details.{$period}"] = "Chỉ được phân bổ môn thuộc khoa {$code} (mã môn kết thúc …{$code}).";

                continue;
            }

            if (! empty($detail['subject_lesson_id'])) {
                $lessonOk = SubjectLesson::query()
                    ->where('id', $detail['subject_lesson_id'])
                    ->where('subject_id', $subjectId)
                    ->exists();
                if (! $lessonOk) {
                    $errors["details.{$period}"] = 'Bài học không thuộc môn đã chọn cho tiết này.';

                    continue;
                }
            }

            if ($facultyUnitIdStore && ! empty($detail['instructor_id'])) {
                TrainingScheduleAccess::ensureInstructorInScope((int) $detail['instructor_id']);
            }

            // Check unique teacher (bỏ qua nếu PDOT chưa gán GV)
            if (! empty($detail['instructor_id'])) {
                $teacherConflictRecord = ScheduleDetail::busyTeacher(
                    $detail['date'],
                    $period,
                    $detail['instructor_id'],
                    $trainingSchedule->id
                )->first();
                if ($teacherConflictRecord) {
                    $conflictScheduleName = $teacherConflictRecord->trainingSchedule->name ?? 'Không xác định';
                    $formattedDate = Carbon::parse($detail['date'])->format('d/m/Y');
                    $errors["details.{$period}"] = "GV đã có lịch dạy tiết {$period} ngày {$formattedDate} ở lịch '{$conflictScheduleName}'.";

                    continue;
                }
            }
            // Check unique classroom: Tương tự
            if (! empty($detail['classroom_id'])) {
                $classroomConflictRecord = ScheduleDetail::busyClassroom(
                    $detail['date'],
                    $period,
                    $detail['classroom_id'],
                    $trainingSchedule->id
                )->first();
                if ($classroomConflictRecord) {
                    $conflictScheduleName = $classroomConflictRecord->trainingSchedule->name ?? 'Không xác định';
                    $formattedDate = Carbon::parse($detail['date'])->format('d/m/Y');
                    $errors["details.{$period}"] = "Phòng học đã được sử dụng tiết {$period} ngày {$formattedDate} ở lịch '{$conflictScheduleName}'.";

                    continue;
                }
            }

            // NEW: Per-detail hour limit check (approximate: +1 cho detail này)
            if (in_array($lessonType, ['theory', 'practice', 'self_study', 'final_exam'])) {
                $subject = Subject::find($subjectId);
                if ($subject) {
                    // Lấy used từ DB (không exclude vì create mới)
                    $usageQuery = ScheduleDetail::hourUsage($subjectId, $trainingSchedule->id, $lessonType);
                    $used = $usageQuery->first()->usage_count ?? 0;
                    $totalHours = match ($lessonType) {
                        'theory' => $subject->theory_hours ?? 0,
                        'practice' => $subject->practice_hours ?? 0,
                        'self_study' => $subject->self_study_hours ?? 0,
                        'final_exam' => $subject->exam_hours ?? 0,
                        default => 0
                    };
                    if ($totalHours == 0) {
                        $typeText = $typeMap[$lessonType] ?? $lessonType;
                        $errors["details.{$period}"] = "Môn '{$subject->name}' chưa được thiết lập số tiết cho loại tiết '{$typeText}'. Vui lòng kiểm tra lại dữ liệu môn học.";

                        continue;
                    }
                    if (($used + 1) > $totalHours) {
                        $typeText = $typeMap[$lessonType] ?? $lessonType;
                        $errors["details.{$period}"] = "Đã gần đạt giới hạn {$typeText} ({$totalHours} tiết) cho môn '{$subject->name}' trong lịch '{$trainingSchedule->name}'. Kiểm tra lại batch.";

                        continue; // Early warning, nhưng batch check sau sẽ accurate hơn
                    }
                }
            }
        }

        // NEW: Batch hour limit check (sau loop, trước save – accurate cho multiple details cùng subject/type)
        if (empty($errors)) { // Chỉ check nếu không có error khác
            $batchErrors = $this->validateBatchHourLimits($validDetails, $trainingSchedule, null); // null cho create (không date exclude)
            if (! empty($batchErrors)) {
                $errors = array_merge($errors, $batchErrors);
            }
        }
        // Nếu có lỗi (từ loop hoặc batch), return back với tất cả errors
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // Nếu pass hết, lưu (chỉ fields fillable)
        foreach ($validDetails as $detail) {
            $trainingSchedule->scheduleDetails()->create([
                'date' => $detail['date'],
                'period' => $detail['period'],
                'subject_id' => $detail['subject_id'] ?? null,
                'subject_lesson_id' => ($isPdot && ! $isFullAccess)
                    ? null
                    : $this->nullableId($detail['subject_lesson_id'] ?? null),
                'instructor_id' => ($isPdot && ! $isFullAccess)
                    ? null
                    : $this->nullableId($detail['instructor_id'] ?? null),
                'classroom_id' => $this->nullableId($detail['classroom_id'] ?? null),
                'lesson_type' => $detail['lesson_type'] ?? null,
                'standard_hours_conversion_category_id' => $this->nullableId(
                    $detail['standard_hours_conversion_category_id'] ?? null
                ),
            ]);
            $trainingSchedule->updated_by = Auth::id();
            $trainingSchedule->touch();
        }

        // Check if user wants to save and go to next date
        if ($request->input('action') === 'save_and_next') {
            $currentDate = Carbon::parse($details[array_key_first($details)]['date']);
            $nextDate = $currentDate->addDay()->format('Y-m-d');

            if (Carbon::parse($nextDate)->gt(Carbon::parse($trainingSchedule->end_date))) {
                return redirect()->route('training-schedules.show', $trainingSchedule)
                    ->with('success', 'Lịch học đã được thêm thành công. Đây là ngày cuối của lịch đào tạo.');
            }

            // Check if next date has existing schedule details
            $hasNextDateData = $trainingSchedule->scheduleDetails()
                ->whereDate('date', $nextDate)
                ->exists();

            if ($hasNextDateData) {
                // Redirect to edit mode
                return redirect()->route('training-schedules.schedule-details.edit', [
                    'trainingSchedule' => $trainingSchedule,
                    'date' => $nextDate,
                ])->with('success', 'Lịch học đã được thêm thành công.');
            }

            // Ngày tiếp theo chưa có khung: chỉ tài khoản có quyền
            // schedule-details.create mới đi tiếp vào màn Thêm — Khoa không có
            // quyền này (họ chỉ gán bài/GV trên khung PĐT), redirect thẳng vào
            // đó sẽ vỡ 403.
            if (Auth::user()?->can('schedule-details.create')) {
                return redirect()->route('training-schedules.schedule-details.create', [
                    'trainingSchedule' => $trainingSchedule,
                    'date' => $nextDate,
                ])->with('success', 'Lịch học đã được thêm thành công.');
            }

            return redirect()->route('training-schedules.show', $trainingSchedule)
                ->with('success', 'Lịch học đã được thêm thành công.')
                ->with('warning', 'Chưa có khung lịch cho ngày tiếp theo — chờ Phòng Đào tạo xếp môn trước khi Khoa phân công.');
        }

        return redirect()->route('training-schedules.show', $trainingSchedule)
            ->with('success', 'Lịch học đã được thêm thành công.');
    }

    public function updateScheduleDetail(Request $request, TrainingSchedule $trainingSchedule, $date)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        $date = $this->ensureDateWithinSchedule($trainingSchedule, (string) $date);
        TrainingScheduleAccess::ensureScheduleDetailDateEditable($date);
        $details = $this->validatedScheduleDetailPayload($request, $trainingSchedule, $date);
        $errors = []; // Mảng để thu thập tất cả lỗi

        $isPdot = TrainingDept::canManageScheduleSkeleton();
        $isFaculty = TrainingDept::isFacultyManager();
        // PDOT: khung môn + loại (+ phòng tuỳ chọn)
        // Khoa: chỉ tiết môn thuộc khoa (mã …Kn)
        $facultySubjectIds = $isFaculty ? (TrainingDept::facultySubjectIds() ?? []) : null;

        $validDetails = collect($details)->filter(function ($d) use ($isPdot, $isFaculty, $facultySubjectIds) {
            if (empty($d['subject_id']) || empty($d['lesson_type'])) {
                return false;
            }
            if ($isFaculty && is_array($facultySubjectIds)) {
                return in_array((int) $d['subject_id'], $facultySubjectIds, true);
            }
            if ($isPdot && ! $isFaculty) {
                return true;
            }

            return true;
        });

        // Optional: Nếu không có valid details, có thể throw error hoặc cho phép clear (delete all)
        if ($validDetails->isEmpty()) {
            if ($isFaculty) {
                return back()->with('warning', 'Không có tiết môn thuộc khoa của bạn để cập nhật trong ngày này.');
            }

            return back()->withErrors(['general' => 'Phải chọn ít nhất một tiết học (môn + loại tiết).'])->withInput();
        }

        // Validate bài học thuộc đúng môn; GV thuộc unit khoa; môn thuộc khoa
        $facultyUnitId = $isFaculty ? (auth()->user()?->unit_id) : null;
        $allowedStandardHoursCategoryIds = Schema::hasTable('conversion_categories')
            ? ConversionCategory::query()
                ->where('entry_source', 'schedule')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
            : collect();

        foreach ($validDetails as $detail) {
            $period = $detail['period']; // Lấy period từ input
            $standardHoursCategoryId = $this->nullableId(
                $detail['standard_hours_conversion_category_id'] ?? null
            );
            if (
                $standardHoursCategoryId
                && ! $allowedStandardHoursCategoryIds->contains($standardHoursCategoryId)
            ) {
                $errors["details.{$period}.standard_hours_conversion_category_id"]
                    = 'Danh mục quy đổi giờ chuẩn không hợp lệ.';

                continue;
            }

            if (! empty($detail['subject_lesson_id'])) {
                $lessonOk = SubjectLesson::query()
                    ->where('id', $detail['subject_lesson_id'])
                    ->where('subject_id', $detail['subject_id'])
                    ->exists();
                if (! $lessonOk) {
                    $errors["details.{$period}"] = 'Bài học không thuộc môn đã chọn cho tiết này.';

                    continue;
                }
            }

            if ($facultyUnitId && ! empty($detail['instructor_id'])) {
                TrainingScheduleAccess::ensureInstructorInScope((int) $detail['instructor_id']);
            }

            if ($isFaculty) {
                $hasSkeleton = $trainingSchedule->scheduleDetails()
                    ->whereDate('date', $date)
                    ->where('period', $period)
                    ->where('subject_id', $detail['subject_id'])
                    ->exists();
                if (! $hasSkeleton) {
                    $errors["details.{$period}"] = 'Khoa chỉ được phân công trên khung môn/tiết do Phòng Đào tạo đã xếp.';

                    continue;
                }
            }

            // Check unique teacher (bỏ qua nếu chưa gán GV)
            if (! empty($detail['instructor_id'])) {
                $teacherConflictRecord = ScheduleDetail::busyTeacher(
                    $detail['date'] ?? $date,
                    $period,
                    $detail['instructor_id'],
                    $trainingSchedule->id  // Exclude chính schedule đang edit
                )->first();
                if ($teacherConflictRecord) {
                    $conflictScheduleName = $teacherConflictRecord->trainingSchedule->name ?? 'Không xác định';
                    $formattedDate = Carbon::parse($detail['date'] ?? $date)->format('d/m/Y');
                    $errors["details.{$period}"] = "GV đã có lịch dạy tiết {$period} ngày {$formattedDate} ở lịch '{$conflictScheduleName}'.";

                    continue;
                }
            }
            // Check unique classroom
            if (! empty($detail['classroom_id'])) {
                $classroomConflictRecord = ScheduleDetail::busyClassroom(
                    $detail['date'] ?? $date,
                    $period,
                    $detail['classroom_id'],
                    $trainingSchedule->id
                )->first();
                if ($classroomConflictRecord) {
                    $conflictScheduleName = $classroomConflictRecord->trainingSchedule->name ?? 'Không xác định';
                    $formattedDate = Carbon::parse($detail['date'] ?? $date)->format('d/m/Y');
                    $errors["details.{$period}"] = "Phòng học đã được sử dụng tiết {$period} ngày {$formattedDate} ở lịch '{$conflictScheduleName}'.";

                    continue;
                }
            }
        }

        // Batch hour limit check cho update (với excludeDate để không tính trùng data cũ)
        $batchErrors = $this->validateBatchHourLimits($validDetails, $trainingSchedule, $date);
        if (! empty($batchErrors)) {
            $errors = array_merge($errors, $batchErrors);
        }

        // Nếu có lỗi, return back với tất cả errors (không delete gì cả)
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // Nếu pass validation, dùng Transaction để delete + create an toàn
        try {
            DB::transaction(function () use ($trainingSchedule, $date, $validDetails, $isFaculty, $facultySubjectIds) {
                $isSuperUser = TrainingScheduleAccess::scope() === TrainingScheduleAccess::SCOPE_SYSTEM;

                // Khoa: chỉ upsert tiết môn khoa — không đụng tiết khoa khác; giữ khung skeleton
                if ($isFaculty && ! $isSuperUser && is_array($facultySubjectIds)) {
                    $ownedIds = array_map('intval', $facultySubjectIds);
                    $toSave = collect($validDetails)->filter(function ($d) use ($ownedIds) {
                        return in_array((int) ($d['subject_id'] ?? 0), $ownedIds, true);
                    });

                    if ($toSave->isEmpty()) {
                        return;
                    }

                    $existingByPeriod = $trainingSchedule->scheduleDetails()
                        ->whereDate('date', $date)
                        ->whereIn('subject_id', $ownedIds)
                        ->get()
                        ->keyBy(fn ($d) => (int) $d->period);

                    $trainingSchedule->scheduleDetails()
                        ->whereDate('date', $date)
                        ->whereIn('subject_id', $ownedIds)
                        ->delete();

                    foreach ($toSave as $detail) {
                        $period = (int) ($detail['period'] ?? 0);
                        $prev = $existingByPeriod->get($period);

                        // Khoa chỉ thay bài học + GV; toàn bộ skeleton phải lấy từ DB.
                        $subjectId = $prev?->subject_id;
                        $lessonType = $prev?->lesson_type;
                        $classroomId = $prev?->classroom_id;
                        $standardHoursCategoryId = $prev?->standard_hours_conversion_category_id;

                        $trainingSchedule->scheduleDetails()->create([
                            'date' => $detail['date'] ?? $date,
                            'period' => $period,
                            'subject_id' => $subjectId,
                            'subject_lesson_id' => $this->nullableId($detail['subject_lesson_id'] ?? null),
                            'instructor_id' => $this->nullableId($detail['instructor_id'] ?? null),
                            'classroom_id' => $classroomId,
                            'lesson_type' => $lessonType,
                            'standard_hours_conversion_category_id' => $standardHoursCategoryId,
                        ]);
                    }
                    $trainingSchedule->updated_by = Auth::id();
                    $trainingSchedule->touch();

                    return;
                }

                // PDOT: chỉ cập nhật khung môn/loại/phòng — giữ bài + GV Khoa đã gán
                // Super-admin: full theo form
                $existingByPeriod = $trainingSchedule->scheduleDetails()
                    ->whereDate('date', $date)
                    ->get()
                    ->keyBy(fn ($d) => (int) $d->period);

                $trainingSchedule->scheduleDetails()
                    ->whereDate('date', $date)
                    ->delete();

                foreach ($validDetails as $detail) {
                    $period = (int) ($detail['period'] ?? 0);
                    $prev = $existingByPeriod->get($period);
                    $newSubjectId = $detail['subject_id'] ?? null;

                    if ($isSuperUser) {
                        $lessonId = $this->nullableId($detail['subject_lesson_id'] ?? null);
                        $instructorId = $this->nullableId($detail['instructor_id'] ?? null);
                    } else {
                        // PDOT pure: không ghi đè phân công Khoa
                        $lessonId = $prev?->subject_lesson_id;
                        $instructorId = $prev?->instructor_id;
                        if ($prev && (int) $prev->subject_id !== (int) $newSubjectId) {
                            $lessonId = null;
                        }
                    }

                    $trainingSchedule->scheduleDetails()->create([
                        'date' => $detail['date'] ?? $date,
                        'period' => $period,
                        'subject_id' => $newSubjectId,
                        'subject_lesson_id' => $lessonId,
                        'instructor_id' => $instructorId,
                        'classroom_id' => $this->nullableId($detail['classroom_id'] ?? null),
                        'lesson_type' => $detail['lesson_type'] ?? null,
                        'standard_hours_conversion_category_id' => array_key_exists(
                            'standard_hours_conversion_category_id',
                            $detail
                        )
                            ? $this->nullableId($detail['standard_hours_conversion_category_id'])
                            : $prev?->standard_hours_conversion_category_id,
                    ]);
                    $trainingSchedule->updated_by = Auth::id();
                    $trainingSchedule->touch();
                }
            });

            // Check if user wants to save and go to next date
            if ($request->input('action') === 'save_and_next') {
                $currentDate = Carbon::parse($date);
                $nextDate = $currentDate->addDay()->format('Y-m-d');

                if (Carbon::parse($nextDate)->gt(Carbon::parse($trainingSchedule->end_date))) {
                    return redirect()->route('training-schedules.show', $trainingSchedule)
                        ->with('success', 'Lịch học đã được cập nhật thành công. Đây là ngày cuối của lịch đào tạo.');
                }

                // Check if next date has existing schedule details
                $hasNextDateData = $trainingSchedule->scheduleDetails()
                    ->whereDate('date', $nextDate)
                    ->exists();

                if ($hasNextDateData) {
                    // Redirect to edit mode
                    return redirect()->route('training-schedules.schedule-details.edit', [
                        'trainingSchedule' => $trainingSchedule,
                        'date' => $nextDate,
                    ])->with('success', 'Lịch học đã được cập nhật thành công.');
                }

                // Cùng lý do như storeScheduleDetail(): Khoa không có quyền
                // schedule-details.create, chỉ tài khoản đủ quyền mới đi tiếp
                // vào màn Thêm cho ngày chưa có khung.
                if (Auth::user()?->can('schedule-details.create')) {
                    return redirect()->route('training-schedules.schedule-details.create', [
                        'trainingSchedule' => $trainingSchedule,
                        'date' => $nextDate,
                    ])->with('success', 'Lịch học đã được cập nhật thành công.');
                }

                return redirect()->route('training-schedules.show', $trainingSchedule)
                    ->with('success', 'Lịch học đã được cập nhật thành công.')
                    ->with('warning', 'Chưa có khung lịch cho ngày tiếp theo — chờ Phòng Đào tạo xếp môn trước khi Khoa phân công.');
            }

            return redirect()->route('training-schedules.show', $trainingSchedule)
                ->with('success', 'Lịch học đã được cập nhật thành công.');

        } catch (\Exception $e) {
            // Nếu transaction fail (ví dụ: DB error), rollback tự động, return error
            return back()->withErrors(['general' => 'Có lỗi xảy ra khi cập nhật lịch học. Vui lòng thử lại.'])
                ->withInput();
        }
    }

    private function validateBatchHourLimits($validDetails, TrainingSchedule $trainingSchedule, $excludeDate = null)
    {
        $errors = [];
        $typeMap = [
            'theory' => 'lý thuyết',
            'practice' => 'thực hành',
            'self_study' => 'tự học',
            'final_exam' => 'thi/kiểm tra',
        ];

        // Group validDetails by subject_id + lesson_type để tính planned adds
        $plannedAdds = collect($validDetails)->groupBy(function ($detail) {
            return $detail['subject_id'].'|'.$detail['lesson_type'];
        });

        foreach ($plannedAdds as $key => $groupDetails) {
            [$subjectId, $lessonType] = explode('|', $key);
            $plannedCount = $groupDetails->count(); // Số detail cùng subject/type trong batch
            $periods = $groupDetails->pluck('period')->toArray(); // Để map error per period nếu exceed

            if (! in_array($lessonType, ['theory', 'practice', 'self_study', 'final_exam'])) {
                continue; // Skip không cần check
            }

            $subject = Subject::find($subjectId);
            if (! $subject) {
                continue; // Invalid subject, skip hoặc error global
            }

            $totalHours = match ($lessonType) {
                'theory' => $subject->theory_hours ?? 0,
                'practice' => $subject->practice_hours ?? 0,
                'self_study' => $subject->self_study_hours ?? 0,
                'final_exam' => $subject->exam_hours ?? 0,
                default => 0
            };

            if ($totalHours === 0) {
                $typeText = $typeMap[$lessonType] ?? $lessonType;
                $errorMsg = "Môn '{$subject->name}' chưa được thiết lập số tiết cho loại tiết '{$typeText}'. Vui lòng kiểm tra lại dữ liệu môn học.";

                // Add error cho từng period trong group (để match view)
                foreach ($periods as $period) {
                    $errors["details.{$period}"] = $errorMsg;
                }

                continue; // Skip hour limit check vì totalHours = 0
            }

            // Tính projected used
            $usageQuery = ScheduleDetail::hourUsage($subjectId, $trainingSchedule->id, $lessonType);
            $currentTotal = $usageQuery->first()->usage_count ?? 0;

            $toRemove = 0;
            if ($excludeDate) { // Cho update: trừ những sẽ delete (trên date này)
                $toRemove = ScheduleDetail::where('subject_id', $subjectId)
                    ->where('training_schedule_id', $trainingSchedule->id)
                    ->where('lesson_type', $lessonType)
                    ->where('date', $excludeDate)
                    ->count();
            }

            $projectedUsed = ($currentTotal - $toRemove) + $plannedCount;

            if ($projectedUsed > $totalHours) {
                $typeText = $typeMap[$lessonType] ?? $lessonType;
                $exceedBy = $projectedUsed - $totalHours;
                $errorMsg = "Vượt giới hạn {$typeText} ({$totalHours} tiết) cho môn '{$subject->name}' trong lịch '{$trainingSchedule->name}'. Bạn đang thêm {$plannedCount} tiết, vượt {$exceedBy} tiết.";

                // Add error cho từng period trong group (để match view)
                foreach ($periods as $period) {
                    $errors["details.{$period}"] = $errorMsg;
                }
            }
        }

        return $errors;
    }

    /**
     * Xóa toàn bộ ScheduleDetails cho trainingSchedule và date cụ thể.
     */
    public function destroyScheduleDetail(TrainingSchedule $trainingSchedule, $date)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();
        $date = $this->ensureDateWithinSchedule($trainingSchedule, (string) $date);

        try {
            $deleteDate = Carbon::createFromFormat('Y-m-d', $date);
            if (! $deleteDate) {
                return redirect()->back()
                    ->with('error', 'Ngày không hợp lệ (phải là định dạng YYYY-MM-DD).');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ngày không hợp lệ: '.$e->getMessage());
        }

        // Check date trong range schedule
        $startDate = $trainingSchedule->start_date;
        $endDate = $trainingSchedule->end_date;

        if ($deleteDate < $startDate || $deleteDate > $endDate) {
            return redirect()->back()
                ->with('error', 'Ngày '.$deleteDate->format('d/m/Y').' không thuộc khoảng thời gian của lịch đào tạo này ('.$startDate->format('d/m/Y').' đến '.$endDate->format('d/m/Y').').');
        }

        // Check nếu có details để xóa (optional, UX tốt hơn)
        $facultySubjectIds = TrainingDept::isFacultyManager()
            ? (TrainingDept::facultySubjectIds() ?? [])
            : null;
        $detailsQuery = $trainingSchedule->scheduleDetails()->whereDate('date', $date);
        if (is_array($facultySubjectIds)) {
            $detailsQuery->whereIn('subject_id', array_map('intval', $facultySubjectIds));
        }
        $detailsCount = (clone $detailsQuery)->count();

        if ($detailsCount === 0) {
            return redirect()->back()
                ->with('warning', 'Không có lịch học nào để xóa trong ngày '.$deleteDate->format('d/m/Y').'.');
        }

        // Xóa bằng transaction (an toàn, rollback nếu error)
        try {
            DB::transaction(function () use ($trainingSchedule, $date, $facultySubjectIds) {
                $deleteQuery = $trainingSchedule->scheduleDetails()->whereDate('date', $date);
                if (is_array($facultySubjectIds)) {
                    $deleteQuery->whereIn('subject_id', array_map('intval', $facultySubjectIds));
                    $deleted = $deleteQuery->update([
                        'subject_lesson_id' => null,
                        'instructor_id' => null,
                        'updated_at' => now(),
                    ]);
                } else {
                    $deleted = $deleteQuery->delete();
                }
                $trainingSchedule->forceFill(['updated_by' => Auth::id()])->save();

                // Optional: Log số lượng xóa (nếu $deleted > 0)
                \Log::info("Cleared/deleted {$deleted} schedule details for date {$date} in schedule {$trainingSchedule->id}");
            });

            return redirect()->back()
                ->with(
                    'success',
                    (is_array($facultySubjectIds) ? 'Đã xóa phân công thuộc khoa: ' : 'Đã xóa lịch trong ngày: ')
                    .$detailsCount.' tiết, ngày '.$deleteDate->format('d/m/Y').'.'
                );
        } catch (\Exception $e) {
            // Rollback tự động, return error
            \Log::error('Delete ScheduleDetail failed: '.$e->getMessage());  // Log để debug

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa lịch học: '.$e->getMessage());
        }
    }

    /**
     * Get subject hour usage for specific training schedule
     */
    public function getSubjectHourUsage(Request $request, TrainingSchedule $trainingSchedule)
    {
        $subjectId = $request->get('subject_id');
        $lessonType = $request->get('lesson_type');

        if (! $subjectId || ! $lessonType) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        $subject = Subject::find($subjectId);
        if (! $subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }
        TrainingScheduleAccess::ensureSubjectInScope($subject);

        // Get total hours for this lesson type
        $totalHours = match ($lessonType) {
            'theory' => $subject->theory_hours ?? 0,
            'practice' => $subject->practice_hours ?? 0,
            'self_study' => $subject->self_study_hours ?? 0,
            'final_exam' => $subject->exam_hours ?? 0,
            default => 0
        };

        // Get used hours
        $usedHours = ScheduleDetail::hourUsage(
            $subjectId,
            $trainingSchedule->id,
            $lessonType
        )->first()->usage_count ?? 0;

        return response()->json([
            'subject_id' => $subjectId,
            'lesson_type' => $lessonType,
            'total_hours' => $totalHours,
            'used_hours' => $usedHours,
            'remaining_hours' => max(0, $totalHours - $usedHours),
            'percentage' => $totalHours > 0 ? round(($usedHours / $totalHours) * 100, 1) : 0,
            'type_label' => match ($lessonType) {
                'theory' => '(Lý thuyết)',
                'practice' => '(Thực hành)',
                'self_study' => '(Tự học)',
                'final_exam' => '(Thi/kiểm tra)',
                default => 'Chưa xác định'
            },
        ]);
    }

    /**
     * Lấy tất cả schedule detail theo các loại filter
     * - training_schedule_id: Lọc theo lịch đào tạo (có thể kèm khoảng ngày)
     * - specialization_id/date: Lọc theo ngành đào tạo + ngày (có thể kèm class_id)
     */
    public function getAllScheduleDetails(Request $request)
    {
        $service = app(TrainingScheduleService::class);
        $subjectId = $request->get('subject_id');
        $instructorId = $request->get('instructor_id');
        $classroomId = $request->get('classroom_id');
        $dataOnly = $request->boolean('dataOnly', false);

        // 1. Lọc theo lịch đào tạo cụ thể + khoảng ngày (optional)
        if ($request->filled('training_schedule_id')) {
            $trainingScheduleId = $request->get('training_schedule_id');

            // Determine date range
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $start = $request->get('start_date');
                $end = $request->get('end_date');
            } else {
                // Default to current week (Monday to Sunday)
                $start = Carbon::now()->startOfWeek()->toDateString();
                $end = Carbon::now()->endOfWeek()->toDateString();
            }

            $query = ScheduleDetail::with(['subject', 'instructor', 'classroom'])
                ->where('training_schedule_id', $trainingScheduleId)
                ->whereBetween('date', [$start, $end]);

            if ($subjectId) {
                $query->where('subject_id', $subjectId);
            }
            if ($instructorId) {
                $query->where('instructor_id', $instructorId);
            }
            if ($classroomId) {
                $query->where('classroom_id', $classroomId);
            }

            $details = $query->get();
            $periods = $service->buildPeriodsForClassRange($details, $start, $end);

            if ($dataOnly) {
                return response()->json([
                    'view_type' => 'calendar_training_schedule',
                    'periods' => $periods,
                ]);
            } else {
                $html = view('training-schedule::calendar_training_schedule', ['periods' => $periods])->render();

                return response()->json(['html' => $html, 'view_type' => 'calendar_training_schedule']);
            }
        }

        // 2. Removed "Class + Range" mode as per request

        // 3. Lọc theo ngành đào tạo + ngày (có thể kèm lớp cụ thể)
        if ($request->filled('date')) {
            $specId = $request->get('specialization_id');
            $classId = $request->get('class_id'); // Optional: lọc thêm theo lớp
            $date = $request->get('date');

            $query = ScheduleDetail::with(['subject', 'instructor', 'classroom', 'trainingSchedule'])
                ->where('date', $date);

            // Filter theo specialization nếu có
            if ($specId) {
                $trainingScheduleIds = TrainingSchedule::where('specialization_id', $specId)->pluck('id');
                if (count($trainingScheduleIds) > 0) {
                    $query->whereIn('training_schedule_id', $trainingScheduleIds);
                }
            }

            // Filter thêm theo class nếu có (trong spec_day mode)
            if ($classId) {
                $trainingScheduleIds = TrainingSchedule::where('class_code', $classId);
                if ($specId) {
                    $trainingScheduleIds->where('specialization_id', $specId);
                }
                $trainingScheduleIds = $trainingScheduleIds->pluck('id');

                if (count($trainingScheduleIds) > 0) {
                    $query->whereIn('training_schedule_id', $trainingScheduleIds);
                }
            }

            if ($subjectId) {
                $query->where('subject_id', $subjectId);
            }
            if ($instructorId) {
                $query->where('instructor_id', $instructorId);
            }
            if ($classroomId) {
                $query->where('classroom_id', $classroomId);
            }

            $details = $query->get();
            $periods = $service->buildPeriodsForSpecDay($details);

            if ($dataOnly) {
                return response()->json([
                    'view_type' => 'calendar_spec_day',
                    'periods' => $periods,
                ]);
            } else {
                $html = view('training-schedule::calendar_spec_day', ['periods' => $periods])->render();

                return response()->json(['html' => $html, 'view_type' => 'calendar_spec_day']);
            }
        }

        // Nếu không khớp filter nào
        if ($dataOnly) {
            return response()->json([
                'view_type' => 'unknown',
                'periods' => [],
                'message' => 'Thiếu hoặc sai filter!',
            ], 400);
        } else {
            return response()->json([
                'html' => '<div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="bi bi-exclamation-circle text-4xl text-red-600 mb-3"></i>
                <p class="text-red-800">Thiếu hoặc sai thông tin lọc!</p>
            </div>',
                'view_type' => 'unknown',
            ], 400);
        }
    }

    /**
     * Get filtered training schedules for AJAX
     */
    public function getFilteredTrainingSchedules(Request $request)
    {
        $query = TrainingSchedule::with('specialization')
            ->select('id', 'name', 'code', 'specialization_id', 'class_code', 'start_date', 'end_date', 'semester', 'academic_year', 'is_active');

        // Filter by specialization - support both single and array
        if ($request->filled('specialization_id')) {
            $specializationIds = $request->get('specialization_id');
            if (is_array($specializationIds)) {
                // Filter out empty values
                $specializationIds = array_filter($specializationIds, function ($id) {
                    return $id !== '' && $id !== null;
                });
                if (! empty($specializationIds)) {
                    $query->whereIn('specialization_id', $specializationIds);
                }
            } else {
                $query->where('specialization_id', $specializationIds);
            }
        }

        // Filter by class - support both single and array
        if ($request->filled('class_id')) {
            $classIds = $request->get('class_id');
            if (is_array($classIds)) {
                // Filter out empty values
                $classIds = array_filter($classIds, function ($id) {
                    return $id !== '' && $id !== null;
                });
                if (! empty($classIds)) {
                    $query->whereIn('class_id', $classIds);
                }
            } else {
                $query->where('class_id', $classIds);
            }
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Filter by academic year
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by status - support both string and boolean
        if ($request->has('is_active')) {
            $isActive = $request->get('is_active');

            // If empty string, show all (both active and inactive)
            if ($isActive === '' || $isActive === null) {
                // No filter applied - show all training schedules
            } elseif ($isActive === 'active' || $isActive === '1' || $isActive === 1 || $isActive === true) {
                $query->where('is_active', true);
            } elseif ($isActive === 'inactive' || $isActive === '0' || $isActive === 0 || $isActive === false) {
                $query->where('is_active', false);
            }
        }

        $schedules = $query->orderBy('name')->get();

        return response()->json($schedules->map(function ($ts) {
            return [
                'id' => $ts->id,
                'name' => $ts->name,
                'code' => $ts->code,
                'start_date' => $ts->start_date ? $ts->start_date->format('Y-m-d') : null,
                'end_date' => $ts->end_date ? $ts->end_date->format('Y-m-d') : null,
            ];
        }));
    }

    /**
     * Export schedule details → Word (có header/footer chỉnh sửa).
     */
    public function exportScheduleDetails(Request $request)
    {
        $validated = $request->validate([
            'training_schedule_ids' => 'required|array|min:1|max:50',
            'training_schedule_ids.*' => 'exists:training_schedules,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'header_left' => 'nullable|string|max:2000',
            'header_right' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:500',
            'footer_left' => 'nullable|string|max:2000',
            'footer_right' => 'nullable|string|max:2000',
        ]);

        $scheduleData = $this->trainingScheduleService->getScheduleDetailsForExport(
            $validated['training_schedule_ids'],
            $validated['start_date'],
            $validated['end_date']
        );

        $headers = ['Ngày', 'Lớp', 'Tiết 1', 'Tiết 2', 'Tiết 3', 'Tiết 4', 'Tiết 5', 'Tiết 6', 'Tiết 7', 'Tiết 8', 'Tiết 9'];
        $rows = [];
        foreach ($scheduleData as $row) {
            if (is_array($row)) {
                $rows[] = array_values($row);
            } elseif (is_object($row)) {
                $rows[] = array_values((array) $row);
            }
        }

        // Fallback: build rows from flat details if service returns structured differently
        if ($rows === [] && is_iterable($scheduleData)) {
            foreach ($scheduleData as $item) {
                if (is_array($item) && isset($item[0])) {
                    $rows[] = $item;
                }
            }
        }

        $meta = [
            'header_left' => $validated['header_left'] ?? null,
            'header_right' => $validated['header_right'] ?? null,
            'title' => $validated['title'] ?? 'LỊCH HỌC',
            'footer_left' => $validated['footer_left'] ?? '',
            'footer_right' => $validated['footer_right'] ?? '',
        ];

        return WordExportTemplate::download(
            'Lich_hoc_'.date('Ymd_His').'.docx',
            $meta,
            function ($section) use ($headers, $rows, $validated) {
                $section->addText(
                    'Từ '.$validated['start_date'].' đến '.$validated['end_date'],
                    ['italic' => true, 'size' => 10],
                    ['alignment' => Jc::CENTER]
                );
                $section->addTextBreak(1);
                WordExportTemplate::addSimpleTable($section, $headers, $rows);
            },
            true
        );
    }

    /**
     * Xuất lịch huấn luyện (PDOT) — Excel/Word theo mẫu LHL HK2
     * (lưới tuần, gạch chéo, header/footer, 3 chữ ký).
     */
    public function exportTrainingPlan(Request $request)
    {
        TrainingScheduleAccess::ensureCanManageSkeleton();

        $validated = $request->validate([
            'training_schedule_ids' => 'required|array|min:1|max:50',
            'training_schedule_ids.*' => 'exists:training_schedules,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|in:xlsx,docx,excel,word,pdf',
            'header_left' => 'nullable|string|max:2000',
            'header_right' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:500',
            'footer_left' => 'nullable|string|max:2000',
            'footer_right' => 'nullable|string|max:2000',
            'org_left' => 'nullable|string|max:2000',
            'semester_line' => 'nullable|string|max:500',
            'respect_line' => 'nullable|string|max:500',
            'unit_name' => 'nullable|string|max:255',
            'class_size' => 'nullable|string|max:50',
            'groups' => 'nullable|string|max:50',
            'class_leader' => 'nullable|string|max:255',
            'classroom' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'date_line' => 'nullable|string|max:255',
            'signer_nguoi_lam_lich_enabled' => 'nullable',
            'signer_nguoi_lam_lich_id' => 'nullable|integer',
            'signer_nguoi_lam_lich_name' => 'nullable|string|max:255',
            'signer_nguoi_lam_lich_role1' => 'nullable|string|max:255',
            'signer_nguoi_lam_lich_role2' => 'nullable|string|max:255',
            'signer_kt_truong_phong_enabled' => 'nullable',
            'signer_kt_truong_phong_id' => 'nullable|integer',
            'signer_kt_truong_phong_name' => 'nullable|string|max:255',
            'signer_kt_truong_phong_role1' => 'nullable|string|max:255',
            'signer_kt_truong_phong_role2' => 'nullable|string|max:255',
            'signer_kt_hieu_truong_enabled' => 'nullable',
            'signer_kt_hieu_truong_id' => 'nullable|integer',
            'signer_kt_hieu_truong_name' => 'nullable|string|max:255',
            'signer_kt_hieu_truong_role1' => 'nullable|string|max:255',
            'signer_kt_hieu_truong_role2' => 'nullable|string|max:255',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        if ($format === 'excel') {
            $format = 'xlsx';
        }
        if ($format === 'word') {
            $format = 'docx';
        }

        $meta = collect($validated)->except([
            'training_schedule_ids', 'start_date', 'end_date', 'format',
        ])->all();

        try {
            return app(TrainingExportService::class)
                ->exportTrainingCalendar(
                    $validated['training_schedule_ids'],
                    $validated['start_date'],
                    $validated['end_date'],
                    $meta,
                    $format,
                );
        } catch (\Throwable $exception) {
            if ($format !== 'pdf') {
                throw $exception;
            }

            report($exception);

            return back()->with('error', 'Không thể xuất LHL PDF: '.$exception->getMessage());
        }
    }

    /**
     * Xuất kế hoạch huấn luyện cấp Khoa theo lớp (Word).
     */
    public function exportFacultyPlan(Request $request)
    {
        TrainingScheduleAccess::ensureCanManageScheduleDetails();

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'header_left' => 'nullable|string|max:2000',
            'header_right' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:500',
            'footer_left' => 'nullable|string|max:2000',
            'footer_right' => 'nullable|string|max:2000',
        ]);

        return app(TrainingExportService::class)
            ->exportFacultyPlan(
                (int) $validated['class_id'],
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                [
                    'header_left' => $validated['header_left'] ?? null,
                    'header_right' => $validated['header_right'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'footer_left' => $validated['footer_left'] ?? '',
                    'footer_right' => $validated['footer_right'] ?? '',
                ]
            );
    }

    /**
     * Get subjects with availability data (optimized batch query)
     */
    /**
     * Validate cấu trúc 9 tiết và khóa toàn bộ ngày vào đúng lịch đang thao tác.
     *
     * @return array<int|string, array<string, mixed>>
     */
    private function validatedScheduleDetailPayload(
        Request $request,
        TrainingSchedule $trainingSchedule,
        ?string $expectedDate = null
    ): array {
        $rules = [
            'details' => ['required', 'array', 'min:1', 'max:9'],
            'details.*.period' => ['required', 'integer', 'between:1,9', 'distinct'],
            'details.*.date' => ['required', 'date_format:Y-m-d'],
            'details.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'details.*.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'details.*.instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'details.*.classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'details.*.lesson_type' => ['nullable', 'in:theory,practice,self_study,final_exam'],
            'details.*.standard_hours_conversion_category_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'in:save,save_and_next'],
        ];

        $validated = $request->validate($rules, [
            'details.*.period.distinct' => 'Mỗi tiết chỉ được xuất hiện một lần.',
            'details.*.period.between' => 'Tiết học phải nằm trong khoảng 1–9.',
            'details.*.date.date_format' => 'Ngày học phải có định dạng YYYY-MM-DD.',
        ]);

        $details = $validated['details'];
        $dates = collect($details)
            ->pluck('date')
            ->filter()
            ->unique()
            ->values();

        if ($dates->count() !== 1) {
            throw ValidationException::withMessages([
                'details' => 'Tất cả tiết trong một lần lưu phải thuộc cùng một ngày.',
            ]);
        }

        $payloadDate = $this->ensureDateWithinSchedule($trainingSchedule, (string) $dates->first());
        if ($expectedDate !== null && $payloadDate !== $expectedDate) {
            throw ValidationException::withMessages([
                'details' => 'Ngày trong dữ liệu gửi lên không khớp với ngày đang chỉnh sửa.',
            ]);
        }

        return $details;
    }

    private function ensureDateWithinSchedule(TrainingSchedule $trainingSchedule, string $date): string
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            abort(422, 'Ngày lịch học không hợp lệ.');
        }

        abort_unless($parsed->format('Y-m-d') === $date, 422, 'Ngày lịch học không hợp lệ.');

        $start = Carbon::parse($trainingSchedule->start_date)->startOfDay();
        $end = Carbon::parse($trainingSchedule->end_date)->startOfDay();
        abort_unless(
            $parsed->betweenIncluded($start, $end),
            422,
            'Ngày được chọn nằm ngoài thời gian của lịch đào tạo.'
        );

        return $parsed->format('Y-m-d');
    }

    /**
     * Chuẩn hoá id form (chuỗi rỗng → null) để tránh lỗi FK.
     */
    protected function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Batch usage: số tiết đã xếp cho từng Bài học (subject_lesson_id) theo
     * Loại tiết, trong toàn bộ Lịch đào tạo hiện tại - dùng để ẩn Bài đã
     * hết giờ (Lý thuyết/Thực hành/Thi) khỏi ô "Chọn bài" ở các lượt sau.
     */
    private function getLessonHourUsage(TrainingSchedule $trainingSchedule)
    {
        return ScheduleDetail::where('training_schedule_id', $trainingSchedule->id)
            ->whereNotNull('subject_lesson_id')
            ->select('subject_lesson_id', 'lesson_type', DB::raw('count(*) as count'))
            ->groupBy('subject_lesson_id', 'lesson_type')
            ->get()
            ->groupBy('subject_lesson_id');
    }

    /**
     * Gắn hour_usage (theory/practice/final_exam: total/used/remaining) lên
     * từng Bài học (gốc + con) của một Môn, dựa trên $lessonUsages đã gộp.
     */
    private function attachLessonHourUsage($lessons, $lessonUsages): void
    {
        $typeToColumn = [
            'theory' => 'theory_hours',
            'practice' => 'practice_hours',
            'final_exam' => 'exam_hours',
        ];

        foreach ($lessons as $lesson) {
            $lessonUsage = $lessonUsages->get($lesson->id, collect());
            $hourUsage = [];
            foreach ($typeToColumn as $type => $column) {
                $total = (int) ($lesson->$column ?? 0);
                $used = (int) ($lessonUsage->where('lesson_type', $type)->first()->count ?? 0);
                $hourUsage[$type] = [
                    'total' => $total,
                    'used' => $used,
                    'remaining' => max(0, $total - $used),
                ];
            }
            $lesson->hour_usage = $hourUsage;

            if ($lesson->relationLoaded('children') && $lesson->children->isNotEmpty()) {
                $this->attachLessonHourUsage($lesson->children, $lessonUsages);
            }
        }
    }

    /**
     * Gán quan hệ "instructors" cho từng Subject = danh sách GV đang hoạt
     * động thuộc đúng Khoa phụ trách môn đó (Subject::faculty_code, khớp
     * Unit::faculty_code hoặc Unit::code). Khoa đã được phân công dạy môn
     * thì mọi GV trong khoa đều chọn được, không cần từng GV phải có riêng
     * một bản ghi Phân công giảng dạy (teaching_assignment) cho môn đó nữa.
     *
     * @param  \Illuminate\Support\Collection<int, Subject>  $subjects
     */
    private function attachFacultyInstructors($subjects): void
    {
        // teaching_assignment là nguồn dữ liệu chính cho quan hệ môn - GV.
        // Không suy diễn toàn bộ GV trong khoa từ faculty_code vì sẽ bỏ qua
        // các GV đã được phân công thực tế cho môn.
        $subjects->load(['instructors' => function ($query) {
            $query->where('instructors.status', Instructor::STATUS_ACTIVE)
                ->select('instructors.id', 'instructors.name', 'instructors.code', 'instructors.unit_id');
        }]);

        $subjects->each(function (Subject $subject) {
            $subject->setRelation('instructors', $subject->instructors->values());
        });
    }

    private function getSubjectsWithAvailability(TrainingSchedule $trainingSchedule)
    {
        // 1. Get base subjects (+ bài học + GV phân công, có unit để lọc khoa)
        $subjects = Subject::with([
            'lessons' => fn ($q) => $q->whereNull('parent_id')->with('children')->orderBy('sort_order'),
        ])
            ->active()
            ->forTrainingSchedule(
                (int) $trainingSchedule->specialization_id,
                $trainingSchedule->semester
            );

        // Khoa: chỉ môn …Kn của unit khoa
        TrainingDept::applySubjectFacultyScope($subjects);

        $subjects = $subjects->get();

        // GV chọn được cho môn = GV đang hoạt động thuộc đúng Khoa phụ trách
        // môn đó (Subject::faculty_code) - không còn yêu cầu phải có Phân
        // công giảng dạy (teaching_assignment) riêng cho từng GV/môn nữa.
        $this->attachFacultyInstructors($subjects);

        // 2. Batch get usage for this schedule
        $usages = ScheduleDetail::where('training_schedule_id', $trainingSchedule->id)
            ->select('subject_id', 'lesson_type', DB::raw('count(*) as count'))
            ->groupBy('subject_id', 'lesson_type')
            ->get()
            ->groupBy('subject_id');
        $lessonUsages = $this->getLessonHourUsage($trainingSchedule);
        foreach ($subjects as $subject) {
            $this->attachLessonHourUsage($subject->lessons, $lessonUsages);
        }

        // 3. Filter and attach availability
        $filteredSubjects = $subjects->filter(function ($subject) use ($usages) {
            $subjectUsages = $usages->get($subject->id, collect());
            $availability = [];
            $hasRemaining = false;

            foreach (['theory', 'practice', 'self_study', 'final_exam'] as $type) {
                $totalProp = match ($type) {
                    'theory' => 'theory_hours',
                    'practice' => 'practice_hours',
                    'self_study' => 'self_study_hours',
                    'final_exam' => 'exam_hours',
                    default => null
                };

                $total = $subject->$totalProp ?? 0;
                $used = $subjectUsages->where('lesson_type', $type)->first()->count ?? 0;
                $remaining = max(0, $total - $used);

                $availability[$type] = [
                    'total' => $total,
                    'used' => $used,
                    'remaining' => $remaining,
                ];

                if ($remaining > 0) {
                    $hasRemaining = true;
                }
            }

            $subject->availability = $availability;

            // Keep subject if at least one type has remaining hours
            return $hasRemaining;
        });

        return $filteredSubjects->values(); // Reset keys
    }

    /**
     * Get ALL subjects with availability data for edit mode (no filtering)
     * Shows all subjects including fully scheduled ones with indicator
     */
    private function getSubjectsForEdit(TrainingSchedule $trainingSchedule)
    {
        // 1. Get ALL subjects (no filtering) + bài học + GV
        $subjects = Subject::with([
            'lessons' => fn ($q) => $q->whereNull('parent_id')->with('children')->orderBy('sort_order'),
        ])
            ->active()
            ->forTrainingSchedule(
                (int) $trainingSchedule->specialization_id,
                $trainingSchedule->semester
            );

        // Khoa: chỉ môn …Kn của unit khoa
        TrainingDept::applySubjectFacultyScope($subjects);

        $subjects = $subjects->get();

        // GV chọn được cho môn = GV đang hoạt động thuộc đúng Khoa phụ trách
        // môn đó (Subject::faculty_code) - không còn yêu cầu phải có Phân
        // công giảng dạy (teaching_assignment) riêng cho từng GV/môn nữa.
        $this->attachFacultyInstructors($subjects);

        // 2. Batch get usage for this schedule
        $usages = ScheduleDetail::where('training_schedule_id', $trainingSchedule->id)
            ->select('subject_id', 'lesson_type', DB::raw('count(*) as count'))
            ->groupBy('subject_id', 'lesson_type')
            ->get()
            ->groupBy('subject_id');
        $lessonUsages = $this->getLessonHourUsage($trainingSchedule);
        foreach ($subjects as $subject) {
            $this->attachLessonHourUsage($subject->lessons, $lessonUsages);
        }

        // 3. Attach availability + is_fully_scheduled (NO FILTER)
        $subjects->each(function ($subject) use ($usages) {
            $subjectUsages = $usages->get($subject->id, collect());
            $availability = [];
            $hasRemaining = false;

            foreach (['theory', 'practice', 'self_study', 'final_exam'] as $type) {
                $totalProp = match ($type) {
                    'theory' => 'theory_hours',
                    'practice' => 'practice_hours',
                    'self_study' => 'self_study_hours',
                    'final_exam' => 'exam_hours',
                    default => null
                };

                $total = $subject->$totalProp ?? 0;
                $used = $subjectUsages->where('lesson_type', $type)->first()->count ?? 0;
                $remaining = max(0, $total - $used);

                $availability[$type] = [
                    'total' => $total,
                    'used' => $used,
                    'remaining' => $remaining,
                ];

                if ($remaining > 0) {
                    $hasRemaining = true;
                }
            }

            $subject->availability = $availability;
            $subject->is_fully_scheduled = ! $hasRemaining;
        });

        return $subjects;
    }
}
