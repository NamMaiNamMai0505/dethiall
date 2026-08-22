<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TrainingDept;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class ProvisioningController extends Controller
{
    public function __construct(protected LmsCourseProvisioningService $provisioning)
    {
        $this->middleware(['auth', 'permission:lms.create']);
    }

    public function index(Request $request)
    {
        $query = $this->scopedSchedules($request)
            ->with(['class:id,name,code', 'scheduleDetails:id,training_schedule_id,subject_id'])
            ->when($request->filled('q'), function (Builder $builder) use ($request): void {
                $keyword = trim((string) $request->input('q'));
                $builder->where(function (Builder $search) use ($keyword): void {
                    $search->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('class_code', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('academic_year'), fn (Builder $builder) => $builder->where('academic_year', $request->input('academic_year')))
            ->when($request->input('status', 'active') === 'active', fn (Builder $builder) => $builder->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn (Builder $builder) => $builder->where('is_active', false));

        $schedules = $query->orderByDesc('start_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $academicYears = TrainingSchedule::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return view('lms::provisioning.index', compact('schedules', 'academicYears'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'schedule_ids' => ['required', 'array', 'min:1', 'max:50'],
            'schedule_ids.*' => ['integer', 'distinct'],
            'sync_content' => ['nullable', 'boolean'],
        ]);

        $requestedIds = collect($data['schedule_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $schedules = $this->scopedSchedules($request)->whereKey($requestedIds)->get();
        abort_unless($schedules->count() === $requestedIds->count(), 403, 'Có lịch đào tạo nằm ngoài phạm vi được giao.');

        $summary = ['created' => 0, 'updated' => 0, 'lessons' => 0, 'attendance' => 0, 'failed' => 0];
        foreach ($schedules as $schedule) {
            try {
                $result = $this->provisioning->provisionFromTrainingSchedule(
                    $schedule,
                    $request->user(),
                    $request->boolean('sync_content', true),
                );
                $summary['created'] += $result['created'];
                $summary['updated'] += $result['updated'];
                $summary['lessons'] += $result['lessons_created'] + $result['lessons_updated'];
                $summary['attendance'] += $result['attendance_created'] + $result['attendance_updated'];
            } catch (\Throwable $exception) {
                $summary['failed']++;
                Log::error('Không thể khởi tạo khóa LMS từ lịch đào tạo.', [
                    'training_schedule_id' => $schedule->id,
                    'user_id' => $request->user()?->id,
                    'exception' => $exception,
                ]);
            }
        }

        $message = "Đồng bộ xong: {$summary['created']} khóa mới, {$summary['updated']} khóa cập nhật, {$summary['lessons']} bài học và {$summary['attendance']} buổi điểm danh.";
        if ($summary['failed'] > 0) {
            return back()->with('warning', $message." Có {$summary['failed']} lịch lỗi; quản trị viên có thể kiểm tra log.");
        }

        return back()->with('success', $message);
    }

    protected function scopedSchedules(Request $request): Builder
    {
        $query = TrainingSchedule::query()->hasDetails();
        if (TrainingDept::isFacultyManager($request->user())) {
            $subjectIds = TrainingDept::facultySubjectIds($request->user()) ?? [];
            $query->whereHas('scheduleDetails', fn (Builder $details) => $details->whereIn('subject_id', $subjectIds));
        }

        return $query;
    }
}
