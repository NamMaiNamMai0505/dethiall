<?php

namespace Modules\Lms\Controllers\Learn;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\StudentSchedule\Services\StudentScheduleService;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class ScheduleController extends Controller
{
    public function __construct(protected StudentScheduleService $service)
    {
        $this->middleware(['auth', 'permission:lms.index']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isStudent()) {
            // GV / admin: đưa sang lịch dạy hoặc hub
            if ($user && method_exists($user, 'isInstructor') && $user->isInstructor()) {
                return redirect()->route('instructor-schedule.index');
            }

            return redirect()->route('lms.learn.home')
                ->with('warning', 'Lịch học LMS dành cho học viên. Quản trị xem lịch tại Dashboard.');
        }

        $classId = $user->class_id;
        $month = $request->filled('m')
            ? Carbon::parse($request->get('m').'-01')->startOfMonth()
            : now()->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        if (! $classId) {
            return view('lms::learn.schedule', [
                'student' => $user,
                'month' => $month,
                'eventsByDate' => [],
                'stats' => null,
                'noClass' => true,
                'noSchedule' => false,
                'activeTrainingSchedule' => null,
            ]);
        }

        $today = now()->toDateString();
        $active = TrainingSchedule::query()
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if (! $active) {
            return view('lms::learn.schedule', [
                'student' => $user,
                'month' => $month,
                'eventsByDate' => [],
                'stats' => null,
                'noClass' => false,
                'noSchedule' => true,
                'activeTrainingSchedule' => null,
            ]);
        }

        $schedules = ScheduleDetail::query()
            ->with(['subject', 'classroom.building', 'instructor'])
            ->where('training_schedule_id', $active->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        $eventsByDate = [];
        foreach ($schedules as $row) {
            $key = $row->date instanceof CarbonInterface
                ? $row->date->format('Y-m-d')
                : (string) $row->date;
            $eventsByDate[$key][] = [
                'id' => $row->id,
                'period' => $row->period,
                'lesson_type' => $row->lesson_type,
                'subject' => $row->subject->name ?? '—',
                'subject_color' => $row->subject->color ?? '#0d9488',
                'room' => $row->classroom->name ?? '—',
                'building' => $row->classroom->building->name ?? null,
                'instructor' => $row->instructor->name ?? null,
                'is_exam' => in_array($row->lesson_type, ['final_exam', 'exam'], true),
            ];
        }

        $stats = $this->service->calculateWeekStatistics($schedules);

        return view('lms::learn.schedule', [
            'student' => $user,
            'month' => $month,
            'eventsByDate' => $eventsByDate,
            'stats' => $stats,
            'noClass' => false,
            'noSchedule' => false,
            'activeTrainingSchedule' => $active,
        ]);
    }
}
