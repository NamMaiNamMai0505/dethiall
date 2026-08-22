<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Demo lịch đầy đủ để test:
 * - Xuất kế hoạch / lịch huấn luyện (admin TrainingSchedule)
 * - Lịch giảng GV portal LMS /lms/gv/schedule
 * - Lịch học HV portal LMS /lms/hoc/schedule
 *
 * Chạy (DB đã có core: class, subject, instructor, classroom, roles):
 *   php artisan db:seed --class=ScheduleDemoSeeder
 *
 * Tài khoản sau khi seed (password: password):
 *   admin@example.com          — super-admin
 *   giangvien@example.com      — GV Nguyễn Văn A (GV-0001)
 *   gv2@example.com            — GV Trần Văn B (GV-0002)
 *   hocvien@example.com        — HV lớp chính (lịch dày)
 *   hv2@example.com            — HV lớp 2
 */
class ScheduleDemoSeeder extends Seeder
{
    public const CODE_PREFIX = 'DEMO-LH-';

    public function run(): void
    {
        $this->command?->info('=== ScheduleDemoSeeder: lịch demo đầy đủ ===');

        $admin = User::query()->where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->update([
                'password' => Hash::make('password'),
                'status' => 1,
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ]);
            if (! $admin->hasRole('super-admin')) {
                try {
                    $admin->assignRole('super-admin');
                } catch (\Throwable $e) {
                }
            }
        }
        $adminId = $admin?->id ?? 1;

        $this->ensurePermissionsAndRoles();

        $classA = ClassModel::query()->orderBy('id')->first();
        $classB = ClassModel::query()->orderBy('id')->skip(1)->first() ?: $classA;
        if (! $classA) {
            $this->command?->error('Thiếu lớp học. Chạy ClassSeeder / seed core trước.');

            return;
        }

        $instA = Instructor::query()->orderBy('id')->first();
        $instB = Instructor::query()->orderBy('id')->skip(1)->first() ?: $instA;
        if (! $instA) {
            $this->command?->error('Thiếu giảng viên. Chạy InstructorSeeder trước.');

            return;
        }

        $classrooms = Classroom::query()->where('status', true)->orderBy('id')->limit(8)->pluck('id')->all();
        if (count($classrooms) < 2) {
            $this->command?->error('Thiếu phòng học.');

            return;
        }

        // Môn: ưu tiên theo ngành lớp, fallback any
        $subjectsA = $this->subjectsForClass($classA);
        $subjectsB = $this->subjectsForClass($classB);
        if ($subjectsA->isEmpty()) {
            $this->command?->error('Thiếu môn học.');

            return;
        }

        // Năm học / khoảng thời gian: 8 tuần quanh tháng hiện tại
        $month = now()->startOfMonth();
        $start = $month->copy()->subWeeks(1)->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->addWeeks(6)->endOfWeek(Carbon::SUNDAY);
        $academicYear = $this->academicYearFor($start);

        $this->command?->info("Khoảng lịch: {$start->toDateString()} → {$end->toDateString()} · NH {$academicYear}");

        // Xóa demo cũ
        $oldIds = TrainingSchedule::withTrashed()
            ->where('code', 'like', self::CODE_PREFIX.'%')
            ->pluck('id');
        if ($oldIds->isNotEmpty()) {
            ScheduleDetail::query()->whereIn('training_schedule_id', $oldIds)->delete();
            TrainingSchedule::withTrashed()->whereIn('id', $oldIds)->forceDelete();
            $this->command?->warn('Đã xóa '.count($oldIds).' lịch DEMO cũ.');
        }

        // Users portal
        $gvUserA = $this->ensureInstructorUser('giangvien@example.com', $instA, $adminId);
        $gvUserB = $this->ensureInstructorUser('gv2@example.com', $instB, $adminId);
        $hvUserA = $this->ensureStudentUser('hocvien@example.com', 'Học viên Demo A', $classA->id, $adminId);
        $hvUserB = $this->ensureStudentUser('hv2@example.com', 'Học viên Demo B', $classB->id, $adminId);

        // Lịch 1: lớp A — sáng (tiết 1–4)
        $tsA = $this->createSchedule(
            code: self::CODE_PREFIX.'A',
            name: 'Lịch HT demo · '.$classA->name,
            class: $classA,
            academicYear: $academicYear,
            start: $start,
            end: $end,
            adminId: $adminId,
            classroomId: $classrooms[0],
        );
        $nA = $this->fillDetails(
            schedule: $tsA,
            subjects: $subjectsA,
            instructors: [$instA->id, $instB->id],
            classrooms: $classrooms,
            start: $start,
            end: $end,
            periodSets: [[1, 2, 3], [2, 3, 4]],
            preferDays: [1, 2, 3, 4, 5], // T2–T6
        );

        // Lịch 2: lớp B — chiều (tiết 6–8) + vài buổi sáng nếu khác lớp
        $tsB = $this->createSchedule(
            code: self::CODE_PREFIX.'B',
            name: 'Lịch HT demo · '.$classB->name,
            class: $classB,
            academicYear: $academicYear,
            start: $start,
            end: $end,
            adminId: $adminId,
            classroomId: $classrooms[1] ?? $classrooms[0],
        );
        $nB = $this->fillDetails(
            schedule: $tsB,
            subjects: $subjectsB->isNotEmpty() ? $subjectsB : $subjectsA,
            instructors: [$instB->id, $instA->id],
            classrooms: array_reverse($classrooms),
            start: $start,
            end: $end,
            periodSets: [[6, 7, 8], [7, 8, 9]],
            preferDays: [1, 2, 3, 4, 5],
        );

        // LMS courses map subject+class → deep-link lịch GV
        $this->ensureLmsCourses($classA, $subjectsA->take(3), $instA, $adminId, $gvUserA, $hvUserA);
        $this->ensureLmsCourses($classB, ($subjectsB->isNotEmpty() ? $subjectsB : $subjectsA)->take(2), $instB, $adminId, $gvUserB, $hvUserB);

        $total = ScheduleDetail::query()
            ->whereIn('training_schedule_id', [$tsA->id, $tsB->id])
            ->count();

        $this->command?->info("✓ Lịch A #{$tsA->id} ({$classA->code}): {$nA} tiết");
        $this->command?->info("✓ Lịch B #{$tsB->id} ({$classB->code}): {$nB} tiết");
        $this->command?->info("✓ Tổng schedule_details demo: {$total}");
        $this->command?->newLine();
        $this->command?->warn('--- Tài khoản test (password: password) ---');
        $this->command?->line('Admin:  admin@example.com');
        $this->command?->line("GV1:    giangvien@example.com  → instructor_id={$instA->id}  lịch /lms/gv/schedule");
        $this->command?->line("GV2:    gv2@example.com        → instructor_id={$instB->id}");
        $this->command?->line("HV1:    hocvien@example.com    → class_id={$classA->id} ({$classA->code}) /lms/hoc/schedule");
        $this->command?->line("HV2:    hv2@example.com        → class_id={$classB->id} ({$classB->code})");
        $this->command?->newLine();
        $this->command?->line('Xuất lịch HT: Dashboard → Lịch đào tạo → chọn lịch DEMO-LH-A / DEMO-LH-B → xuất Word/kế hoạch.');
        $this->command?->line("Khoảng ngày xuất gợi ý: {$start->toDateString()} → {$end->toDateString()}");
    }

    protected function ensurePermissionsAndRoles(): void
    {
        foreach (['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index', 'student-schedule.index', 'dashboards.index'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $student = Role::findOrCreate('student', 'web');
        $student->givePermissionTo(['lms.index', 'lms.show', 'student-schedule.index', 'dashboards.index']);
        $instructor = Role::findOrCreate('instructor', 'web');
        $instructor->givePermissionTo(['lms.index', 'lms.show', 'lms.edit', 'instructor-schedule.index', 'dashboards.index']);
        Role::findOrCreate('super-admin', 'web');
    }

    protected function subjectsForClass(ClassModel $class)
    {
        $q = Subject::query()->where('is_active', true);
        if ($class->specialization_id) {
            $bySpec = (clone $q)->where('specialization_id', $class->specialization_id)->orderBy('id')->limit(12)->get();
            if ($bySpec->isNotEmpty()) {
                return $bySpec;
            }
        }

        return $q->orderBy('id')->limit(12)->get();
    }

    protected function academicYearFor(Carbon $date): string
    {
        // Năm học: nếu tháng >= 8 → Y-(Y+1), else (Y-1)-Y
        $y = (int) $date->year;
        $m = (int) $date->month;
        if ($m >= 8) {
            return $y.'-'.($y + 1);
        }

        return ($y - 1).'-'.$y;
    }

    protected function createSchedule(
        string $code,
        string $name,
        ClassModel $class,
        string $academicYear,
        Carbon $start,
        Carbon $end,
        int $adminId,
        int $classroomId,
    ): TrainingSchedule {
        $payload = [
            'name' => $name,
            'code' => $code,
            'specialization_id' => $class->specialization_id,
            'class_id' => $class->id,
            'class_code' => $class->code,
            'academic_year' => $academicYear,
            'semester' => 'semester_2',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'weekly_schedule' => null,
            'is_active' => true,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ];
        if (Schema::hasColumn('training_schedules', 'classroom_id')) {
            $payload['classroom_id'] = $classroomId;
        }
        if (Schema::hasColumn('training_schedules', 'abbreviation')) {
            $payload['abbreviation'] = 'DEMO';
        }

        return TrainingSchedule::query()->create($payload);
    }

    /**
     * @param  list<int>  $instructors
     * @param  list<int>  $classrooms
     * @param  list<list<int>>  $periodSets
     * @param  list<int>  $preferDays  ISO 1=Mon … 7=Sun
     */
    protected function fillDetails(
        TrainingSchedule $schedule,
        $subjects,
        array $instructors,
        array $classrooms,
        Carbon $start,
        Carbon $end,
        array $periodSets,
        array $preferDays,
    ): int {
        $types = ['theory', 'theory', 'practice', 'theory', 'practice', 'self_study'];
        $subjectIds = $subjects->pluck('id')->values()->all();
        $n = 0;
        $cursor = $start->copy();
        $weekIdx = 0;
        $daySlot = 0;

        while ($cursor->lte($end)) {
            $iso = $cursor->dayOfWeekIso;
            if (in_array($iso, $preferDays, true)) {
                $periods = $periodSets[$daySlot % count($periodSets)];
                $daySlot++;
                $subjId = $subjectIds[($weekIdx + $iso) % count($subjectIds)];
                $type = $types[($weekIdx + $iso) % count($types)];
                // Cuối tuần thứ 6 thỉnh thoảng thi
                if ($iso === 5 && $weekIdx % 3 === 2) {
                    $type = 'final_exam';
                }

                foreach ($periods as $pIdx => $period) {
                    $instId = $instructors[($weekIdx + $pIdx) % count($instructors)];
                    $roomId = $classrooms[($iso + $pIdx) % count($classrooms)];

                    // Tránh unique conflict: skip nếu slot GV/phòng đã có
                    $busyInst = ScheduleDetail::query()
                        ->where('date', $cursor->toDateString())
                        ->where('period', $period)
                        ->where('instructor_id', $instId)
                        ->exists();
                    $busyRoom = ScheduleDetail::query()
                        ->where('date', $cursor->toDateString())
                        ->where('period', $period)
                        ->where('classroom_id', $roomId)
                        ->exists();
                    if ($busyInst || $busyRoom) {
                        // thử instructor/phòng khác
                        $instId = $instructors[($weekIdx + $pIdx + 1) % count($instructors)];
                        $roomId = $classrooms[($iso + $pIdx + 2) % count($classrooms)];
                        $busyInst = ScheduleDetail::query()
                            ->where('date', $cursor->toDateString())
                            ->where('period', $period)
                            ->where('instructor_id', $instId)
                            ->exists();
                        $busyRoom = ScheduleDetail::query()
                            ->where('date', $cursor->toDateString())
                            ->where('period', $period)
                            ->where('classroom_id', $roomId)
                            ->exists();
                        if ($busyInst || $busyRoom) {
                            continue;
                        }
                    }

                    $row = [
                        'training_schedule_id' => $schedule->id,
                        'date' => $cursor->toDateString(),
                        'period' => $period,
                        'subject_id' => $subjId,
                        'instructor_id' => $instId,
                        'classroom_id' => $roomId,
                        'lesson_type' => $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    try {
                        DB::table('schedule_details')->insert($row);
                        $n++;
                    } catch (\Throwable $e) {
                        // unique conflict — bỏ qua
                    }
                }
            }
            if ($cursor->dayOfWeekIso === 7) {
                $weekIdx++;
            }
            $cursor->addDay();
        }

        return $n;
    }

    protected function ensureInstructorUser(string $email, Instructor $instructor, int $adminId): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $instructor->name,
                'password' => Hash::make('password'),
                'user_type' => 'instructor',
                'instructor_id' => $instructor->id,
                'unit_id' => $instructor->unit_id,
                'status' => 1,
                'email_verified_at' => now(),
                'code' => $instructor->code,
            ]
        );
        $role = Role::findByName('instructor', 'web');
        if ($role && ! $user->hasRole('instructor')) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function ensureStudentUser(string $email, string $name, int $classId, int $adminId): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'user_type' => 'student',
                'class_id' => $classId,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );
        $role = Role::findByName('student', 'web');
        if ($role && ! $user->hasRole('student')) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function ensureLmsCourses(
        ClassModel $class,
        $subjects,
        Instructor $instructor,
        int $adminId,
        ?User $gvUser,
        ?User $hvUser,
    ): void {
        if (! Schema::hasTable('lms_courses')) {
            return;
        }
        foreach ($subjects as $i => $subject) {
            $code = 'LMS-DEMO-'.$class->code.'-S'.$subject->id;
            // Unique (subject_id, class_id) trên lms_courses
            $course = LmsCourse::withTrashed()
                ->where('subject_id', $subject->id)
                ->where('class_id', $class->id)
                ->first();
            if ($course) {
                if (method_exists($course, 'trashed') && $course->trashed()) {
                    $course->restore();
                }
                $course->update([
                    'code' => $code,
                    'title' => $subject->name.' · '.$class->name,
                    'description' => 'Khóa LMS demo gắn lịch HT (ScheduleDemoSeeder)',
                    'instructor_id' => $instructor->id,
                    'status' => 'published',
                    'is_standalone' => false,
                    'updated_by' => $adminId,
                ]);
            } else {
                $course = LmsCourse::query()->create([
                    'code' => $code,
                    'title' => $subject->name.' · '.$class->name,
                    'description' => 'Khóa LMS demo gắn lịch HT (ScheduleDemoSeeder)',
                    'subject_id' => $subject->id,
                    'class_id' => $class->id,
                    'instructor_id' => $instructor->id,
                    'status' => 'published',
                    'is_standalone' => false,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }

            if ($gvUser && Schema::hasTable('lms_course_members')) {
                LmsCourseMember::query()->updateOrCreate(
                    ['lms_course_id' => $course->id, 'user_id' => $gvUser->id],
                    ['role' => LmsCourseMember::ROLE_LECTURER, 'joined_at' => now()]
                );
            }
            if ($hvUser && Schema::hasTable('lms_course_members')) {
                LmsCourseMember::query()->updateOrCreate(
                    ['lms_course_id' => $course->id, 'user_id' => $hvUser->id],
                    ['role' => LmsCourseMember::ROLE_STUDENT, 'joined_at' => now()]
                );
            }
        }
    }
}
