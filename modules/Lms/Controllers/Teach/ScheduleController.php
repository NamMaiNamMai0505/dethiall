<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Modules\Class\Models\ClassModel;
use Modules\InstructorSchedule\Services\InstructorScheduleService;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Support\LmsAccess;
use Modules\ScheduleDetail\Models\ScheduleDetail;

/**
 * Lịch dạy trong portal LMS GV (không qua dashboard).
 */
class ScheduleController extends Controller
{
    public function __construct(protected InstructorScheduleService $service)
    {
        $this->middleware(['auth', 'permission:instructor-schedule.index']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (! LmsAccess::isInstructorUser($user) && ! LmsAccess::usesAdminShell($user)) {
            abort(403, 'Chỉ giảng viên xem lịch dạy tại đây.');
        }

        $instructorId = $user->instructor_id;
        if (! $instructorId && ! LmsAccess::usesAdminShell($user)) {
            return view('lms::teach.schedule', [
                'month' => now()->startOfMonth(),
                'eventsByDate' => [],
                'stats' => null,
                'instructor' => null,
                'noInstructor' => true,
            ]);
        }

        $month = $request->filled('m')
            ? Carbon::parse($request->get('m').'-01')->startOfMonth()
            : now()->startOfMonth();
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $query = ScheduleDetail::query()
            ->with(['subject', 'classroom.building', 'trainingSchedule.classModel'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('period');

        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        $schedules = $query->get();

        // Map subject+class → LMS course (deep-link vào phòng dạy)
        // Sprint 8 G10: 1 môn nhiều lớp — ưu tiên khớp class_id; không mơ hồ fallback 1 course
        $courseMap = [];
        $coursesBySubject = [];
        if ($instructorId) {
            $lmsCourses = LmsCourse::query()
                ->where('instructor_id', $instructorId)
                ->with('classModel:id,name,code')
                ->get(['id', 'title', 'subject_id', 'class_id']);
            foreach ($lmsCourses as $c) {
                $k = (int) $c->subject_id.'|'.(int) ($c->class_id ?? 0);
                $courseMap[$k] = $c;
                $coursesBySubject[(int) $c->subject_id][] = $c;
            }
        }

        $eventsByDate = [];
        foreach ($schedules as $row) {
            $key = $row->date instanceof CarbonInterface
                ? $row->date->format('Y-m-d')
                : (string) $row->date;
            $classId = (int) ($row->trainingSchedule->class_id
                ?? $row->trainingSchedule->classModel->id
                ?? 0);
            // Fallback class_code → class id nếu relation thiếu class_id
            if ($classId === 0 && ! empty($row->trainingSchedule?->class_code)) {
                $classId = (int) (ClassModel::query()
                    ->where('code', $row->trainingSchedule->class_code)
                    ->value('id') ?? 0);
            }
            $mapKey = (int) $row->subject_id.'|'.$classId;
            $lms = $courseMap[$mapKey] ?? null;
            $lmsAlternatives = [];

            // Fallback: match subject only if single course
            if (! $lms && $instructorId) {
                $bySubject = collect($coursesBySubject[(int) $row->subject_id] ?? []);
                if ($bySubject->count() === 1) {
                    $lms = $bySubject->first();
                } elseif ($bySubject->count() > 1) {
                    // G10: nhiều lớp cùng môn — đưa danh sách chọn, không đoán bừa
                    $lmsAlternatives = $bySubject->map(fn ($c) => [
                        'id' => $c->id,
                        'title' => $c->title,
                        'class' => $c->classModel->name ?? $c->classModel->code ?? ('#'.$c->class_id),
                        'url' => route('lms.learn.courses.show', $c).'?mode=teach',
                    ])->values()->all();
                }
            }

            $eventsByDate[$key][] = [
                'period' => $row->period,
                'subject' => $row->subject->name ?? '—',
                'room' => $row->classroom->name ?? '—',
                'building' => $row->classroom->building->name ?? null,
                'class' => $row->trainingSchedule->classModel->name
                    ?? $row->trainingSchedule->class_code
                    ?? null,
                'lesson_type' => $row->lesson_type,
                'is_exam' => in_array($row->lesson_type, ['final_exam', 'exam'], true),
                'lms_course_id' => $lms?->id,
                'lms_course_title' => $lms?->title,
                'lms_url' => $lms
                    ? route('lms.learn.courses.show', $lms).'?mode=teach'
                    : null,
                'lms_alternatives' => $lmsAlternatives,
            ];
        }

        $stats = $this->service->calculateWeekStatistics($schedules);

        return view('lms::teach.schedule', [
            'month' => $month,
            'eventsByDate' => $eventsByDate,
            'stats' => $stats,
            'instructor' => $user->instructor,
            'noInstructor' => false,
        ]);
    }
}
