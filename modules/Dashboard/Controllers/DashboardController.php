<?php

namespace Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Class\Models\ClassModel;
use Modules\Dashboard\Services\AccountStatisticsService;
use Modules\Dashboard\Services\ClassStatisticsService;
use Modules\Dashboard\Services\InstructorStatisticsService;
use Modules\Dashboard\Services\LmsStatisticsService;
use Modules\Dashboard\Support\DashboardScope;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\Unit\Models\Unit;

class DashboardController extends Controller
{
    protected $classStatService;

    protected $instructorStatService;

    protected LmsStatisticsService $lmsStatService;

    public function __construct(
        ClassStatisticsService $classStatService,
        InstructorStatisticsService $instructorStatService,
        LmsStatisticsService $lmsStatService,
        protected AccountStatisticsService $accountStatService
    ) {
        $this->classStatService = $classStatService;
        $this->instructorStatService = $instructorStatService;
        $this->lmsStatService = $lmsStatService;
    }

    public function index(Request $request)
    {
        // Học viên không dùng dashboard — chỉ LMS
        $user = $request->user();
        if ($user && method_exists($user, 'isStudent') && $user->isStudent()) {
            return redirect()->route('lms.learn.home');
        }

        $user->loadMissing(['unit', 'instructor.unit', 'position', 'militaryRank']);
        $dashboard_scope = DashboardScope::resolve($user);
        $today = now()->format('Y-m-d');

        // Tab 1 + snapshot đầu trang luôn tuân theo phạm vi tài khoản.
        $overview = $this->getOverviewData($today, $dashboard_scope);
        $account_stats = $this->accountStatService->build($dashboard_scope);

        // Tab 2: Thống kê ngành đào tạo/lớp (mặc định không filter)
        // Lấy ngày mặc định từ schedule details
        $defaultDates = $this->classStatService->getDateRange(scope: $dashboard_scope);
        $specializationQuery = Specialization::active()->orderBy('name');
        if (! $dashboard_scope['is_global']) {
            $specializationQuery->whereIn(
                'id',
                TrainingSchedule::query()
                    ->select('specialization_id')
                    ->whereNotNull('specialization_id')
                    ->whereHas('scheduleDetails', fn ($query) => DashboardScope::applyToScheduleQuery(
                        $query,
                        $dashboard_scope
                    ))
            );
        }

        $stat_class = [
            'specializations' => $specializationQuery->get(),
            'classes' => [], // Load via AJAX khi chọn ngành đào tạo
            'data' => null, // Chưa có dữ liệu
            'filters' => [
                'specialization_id' => null,
                'class_code' => null,
                'start_date' => $defaultDates['start_date'], // Ngày bắt đầu sớm nhất
                'end_date' => $today, // Ngày hiện tại
            ],
        ];

        // Tab 3: Thống kê khoa/giảng viên (mặc định không filter)
        $unitQuery = Unit::query()->orderBy('name');
        if (! $dashboard_scope['is_global']) {
            $unitQuery->whereIn('id', $dashboard_scope['unit_ids']);
        }
        $instructorQuery = Instructor::query()->active()->orderBy('name');
        DashboardScope::applyToInstructorQuery($instructorQuery, $dashboard_scope);
        $instructorDates = $this->instructorStatService->getDateRange(
            $dashboard_scope['unit_id'],
            $dashboard_scope['instructor_id'],
            $dashboard_scope
        );

        $stat_instructor = [
            'units' => $unitQuery->get(),
            'instructors' => $dashboard_scope['is_global'] ? collect() : $instructorQuery->get(),
            'profile' => $dashboard_scope['type'] === DashboardScope::TYPE_INSTRUCTOR
                ? $user->instructor
                : null,
            'data' => null, // Chưa có dữ liệu
            'filters' => [
                'unit_id' => $dashboard_scope['unit_id'],
                'instructor_id' => $dashboard_scope['instructor_id'],
                'start_date' => $instructorDates['start_date'],
                'end_date' => $today, // Ngày hiện tại
            ],
        ];

        // Widget LMS cũng giới hạn theo chính GV hoặc đơn vị của tài khoản.
        $lms_stats = $this->lmsStatService->overview(
            $dashboard_scope['unit_id'],
            $dashboard_scope['instructor_id'],
            $dashboard_scope['is_global'] ? null : $dashboard_scope['unit_ids']
        );

        $dashboard_identity = [
            'name' => $user->name,
            'role' => $user->position?->name
                ?: ($user->getRoleNames()->first() ?: 'Tài khoản hệ thống'),
            'rank' => $user->militaryRank?->name,
            'unit' => $user->unit?->name ?? $user->instructor?->unit?->name,
        ];

        return view('dashboard::index', compact(
            'overview',
            'stat_class',
            'stat_instructor',
            'lms_stats',
            'account_stats',
            'dashboard_scope',
            'dashboard_identity'
        ));
    }

    /**
     * AJAX: Lấy thống kê ngành đào tạo/lớp
     */
    public function getClassStatistics(Request $request)
    {
        $validated = $request->validate([
            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'class_code' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $scope = DashboardScope::resolve($request->user());

        // Nếu không có start_date, lấy từ schedule details
        $startDate = $validated['start_date'] ?? null;
        if (! $startDate) {
            $dateRange = $this->classStatService->getDateRange(
                $validated['specialization_id'] ?? null,
                $validated['class_code'] ?? null,
                $scope
            );
            $startDate = $dateRange['start_date'];
        }

        $data = $this->classStatService->getStatistics(
            $validated['specialization_id'] ?? null,
            $validated['class_code'] ?? null,
            $startDate,
            $validated['end_date'] ?? null,
            $scope
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * AJAX: Lấy danh sách lớp theo ngành đào tạo
     */
    public function getClassesBySpecialization(Request $request)
    {
        $validated = $request->validate([
            'specialization_id' => ['required', 'integer', 'exists:specializations,id'],
        ]);
        $scope = DashboardScope::resolve($request->user());

        $classesQuery = ClassModel::query()
            ->where('specialization_id', $validated['specialization_id']);

        if (! $scope['is_global']) {
            $trainingSchedules = TrainingSchedule::query()
                ->where('specialization_id', $validated['specialization_id'])
                ->whereHas('scheduleDetails', fn ($query) => DashboardScope::applyToScheduleQuery($query, $scope))
                ->get(['class_id', 'class_code']);
            $classIds = $trainingSchedules->pluck('class_id')->filter()->map(fn ($id) => (int) $id)->all();
            $classCodes = $trainingSchedules->pluck('class_code')->filter()->map(fn ($code) => (string) $code)->all();

            $classesQuery->where(function ($query) use ($classIds, $classCodes) {
                $query->whereIn('id', $classIds !== [] ? $classIds : [-1])
                    ->orWhereIn('code', $classCodes !== [] ? $classCodes : ['__none__']);
            });
        }

        $classes = $classesQuery
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'data' => $classes,
        ]);
    }

    /**
     * AJAX: Lấy thống kê khoa/giảng viên
     */
    public function getInstructorStatistics(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $scope = DashboardScope::resolve($request->user());

        // Nếu không có start_date, lấy từ schedule details
        $startDate = $validated['start_date'] ?? null;
        if (! $startDate) {
            $dateRange = $this->instructorStatService->getDateRange(
                $validated['unit_id'] ?? null,
                $validated['instructor_id'] ?? null,
                $scope
            );
            $startDate = $dateRange['start_date'];
        }

        $data = $this->instructorStatService->getStatistics(
            $validated['unit_id'] ?? null,
            $validated['instructor_id'] ?? null,
            $startDate,
            $validated['end_date'] ?? null,
            $scope
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * AJAX: Lấy danh sách giảng viên theo khoa
     */
    public function getInstructorsByUnit(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
        ]);
        $scope = DashboardScope::resolve($request->user());
        $instructorQuery = Instructor::query()
            ->where('unit_id', $validated['unit_id']);
        DashboardScope::applyToInstructorQuery($instructorQuery, $scope);

        $instructors = $instructorQuery
            ->active()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'data' => $instructors,
        ]);
    }

    /**
     * Lấy dữ liệu tổng quan hôm nay (giữ nguyên)
     */
    public function getOverviewData($today, ?array $scope = null)
    {
        $detailsQuery = ScheduleDetail::with(['subject', 'instructor', 'classroom', 'trainingSchedule'])
            ->where('date', $today)
            ->whereHas('trainingSchedule', function ($q) {
                $q->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            });
        if ($scope !== null) {
            DashboardScope::applyToScheduleQuery($detailsQuery, $scope);
        }
        $detailsToday = $detailsQuery->get();

        // Số lớp đang học hôm nay
        $classIds = $detailsToday->pluck('trainingSchedule.class_code')->unique()->filter();
        $classCount = $classIds->count();

        // Số môn từng loại tiết
        $subjectPractice = $detailsToday->where('lesson_type', 'practice')->pluck('subject_id')->unique()->count();
        $subjectTheory = $detailsToday->where('lesson_type', 'theory')->pluck('subject_id')->unique()->count();
        $subjectSelf = $detailsToday->where('lesson_type', 'self_study')->pluck('subject_id')->unique()->count();
        $subjectExam = $detailsToday->where('lesson_type', 'final_exam')->pluck('subject_id')->unique()->count();

        // % tiến độ học tập
        $totalLessonsToday = $detailsToday->count();
        $totalLessonsPlanned = Subject::whereIn('id', $detailsToday->pluck('subject_id')->unique())
            ->get()
            ->sum(function ($subject) {
                return $subject->theory_hours + $subject->practice_hours + $subject->self_study_hours;
            });
        $progressPercent = $totalLessonsPlanned > 0 ? round(($totalLessonsToday / $totalLessonsPlanned) * 100, 1) : 0;
        $progressDetail = "{$totalLessonsToday}/{$totalLessonsPlanned}";

        // Tổng số giáo viên giảng dạy hôm nay
        $instructorCount = $detailsToday->pluck('instructor_id')->unique()->filter()->count();

        // Tổng số phòng học đang sử dụng hôm nay
        $roomCount = $detailsToday->pluck('classroom_id')->unique()->filter()->count();

        // Pie chart: cơ cấu tiết học hôm nay (ĐẢM BẢO LUÔN CÓ GIÁ TRỊ MẶC ĐỊNH)
        $pieChartData = [
            'labels' => ['Lý thuyết', 'Thực hành', 'Tự học', 'Thi'],
            'data' => [
                $detailsToday->where('lesson_type', 'theory')->count(),
                $detailsToday->where('lesson_type', 'practice')->count(),
                $detailsToday->where('lesson_type', 'self_study')->count(),
                $detailsToday->where('lesson_type', 'final_exam')->count(),
            ],
        ];

        // Bar chart: số lớp/môn/giáo viên theo loại tiết (ĐẢM BẢO LUÔN CÓ GIÁ TRỊ MẶC ĐỊNH)
        $barChartData = [
            'labels' => ['Lý thuyết', 'Thực hành', 'Tự học', 'Thi'],
            'classes' => [
                $detailsToday->where('lesson_type', 'theory')->pluck('trainingSchedule.class_code')->unique()->count(),
                $detailsToday->where('lesson_type', 'practice')->pluck('trainingSchedule.class_code')->unique()->count(),
                $detailsToday->where('lesson_type', 'self_study')->pluck('trainingSchedule.class_code')->unique()->count(),
                $detailsToday->where('lesson_type', 'final_exam')->pluck('trainingSchedule.class_code')->unique()->count(),
            ],
            'subjects' => [
                $detailsToday->where('lesson_type', 'theory')->pluck('subject_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'practice')->pluck('subject_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'self_study')->pluck('subject_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'final_exam')->pluck('subject_id')->unique()->count(),
            ],
            'instructors' => [
                $detailsToday->where('lesson_type', 'theory')->pluck('instructor_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'practice')->pluck('instructor_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'self_study')->pluck('instructor_id')->unique()->count(),
                $detailsToday->where('lesson_type', 'final_exam')->pluck('instructor_id')->unique()->count(),
            ],
        ];

        // Bảng danh sách lớp/môn/giáo viên/phòng học - Group by unique combination
        $grouped = $detailsToday->groupBy(function ($detail) {
            return sprintf(
                '%s|%s|%s|%s',
                optional($detail->trainingSchedule)->class_code ?? 'N/A',
                optional($detail->subject)->id ?? 'N/A',
                optional($detail->instructor)->id ?? 'N/A',
                optional($detail->classroom)->id ?? 'N/A'
            );
        });

        $tableRows = $grouped->map(function ($details) {
            // Collect all periods
            $periods = $details->pluck('period')->sort()->values()->toArray();

            // Determine session type
            $hasMorning = collect($periods)->filter(fn ($p) => $p >= 1 && $p <= 5)->isNotEmpty();
            $hasAfternoon = collect($periods)->filter(fn ($p) => $p >= 6 && $p <= 9)->isNotEmpty();

            if ($hasMorning && $hasAfternoon) {
                $session = 'Cả ngày';
            } elseif ($hasMorning) {
                $session = 'Sáng';
            } else {
                $session = 'Chiều';
            }

            $firstDetail = $details->first();

            return [
                'class' => optional($firstDetail->trainingSchedule->classModel)->code ?? optional($firstDetail->trainingSchedule)->name ?? 'N/A',
                'subject' => optional($firstDetail->subject)->name ?? 'N/A',
                'instructor' => optional($firstDetail->instructor)->name ?? 'N/A',
                'room' => optional($firstDetail->classroom)->name ?? 'N/A',
                'session' => $session,
                'periods' => implode(', ', $periods),
            ];
        })->values()->toArray();

        // Tổng quan hôm nay
        $overview = [
            'class_count' => $classCount,
            'subject_practice' => $subjectPractice,
            'subject_theory' => $subjectTheory,
            'subject_self' => $subjectSelf,
            'subject_exam' => $subjectExam,
            'progress_percent' => $progressPercent.'%',
            'progress_detail' => $progressDetail.' tiết',
            'instructor_count' => $instructorCount,
            'room_count' => $roomCount,
            'pie_chart_data' => $pieChartData,
            'bar_chart_data' => $barChartData,
            'table_rows' => $tableRows,
        ];

        return $overview;
    }
}
