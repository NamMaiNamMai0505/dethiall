<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCertificate;
use Modules\Lms\Models\LmsCertificateTemplate;
use Modules\Lms\Models\LmsChatMessage;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsForumTopic;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Models\LmsQuestion;
use Modules\Lms\Models\LmsQuestionBank;
use Modules\Lms\Models\LmsSurvey;
use Modules\Lms\Models\LmsSurveyQuestion;
use Modules\Lms\Services\LmsAlertService;
use Modules\Lms\Services\LmsGradebookService;
use Modules\Lms\Services\LmsProgressService;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Spatie\Permission\Models\Role;

/**
 * Demo đầy đủ LMS để test quản trị, giảng viên và học viên.
 *
 * php artisan db:seed --class=LmsDemoSeeder
 */
class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding LMS demo…');

        $admin = User::query()->where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->update(['password' => Hash::make('password'), 'status' => 1]);
            if (! $admin->hasRole('super-admin')) {
                $admin->assignRole('super-admin');
            }
        }

        // Test students with known passwords
        $studentRole = Role::findByName('student');
        $hv1 = User::query()->updateOrCreate(
            ['email' => 'hocvien@example.com'],
            [
                'name' => 'Học viên Demo',
                'password' => Hash::make('password'),
                'user_type' => 'student',
                'class_id' => ClassModel::query()->value('id') ?? 1,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );
        if (! $hv1->hasRole('student')) {
            $hv1->assignRole($studentRole);
        }

        $hv2 = User::query()->updateOrCreate(
            ['email' => 'hv2@example.com'],
            [
                'name' => 'Nguyễn Văn Học Viên 2',
                'password' => Hash::make('password'),
                'user_type' => 'student',
                'class_id' => $hv1->class_id,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );
        if (! $hv2->hasRole('student')) {
            $hv2->assignRole($studentRole);
        }

        // Giảng viên demo (mật khẩu biết trước để test portal GV)
        $instructorRole = Role::findByName('instructor');
        $instructor = Instructor::query()->orderBy('id')->first();
        $gv1 = null;
        if ($instructor) {
            $gv1 = User::query()->updateOrCreate(
                ['email' => 'giangvien@example.com'],
                [
                    'name' => $instructor->name ?: 'Giảng viên Demo',
                    'password' => Hash::make('password'),
                    'user_type' => 'instructor',
                    'instructor_id' => $instructor->id,
                    'unit_id' => $instructor->unit_id,
                    'status' => 1,
                    'email_verified_at' => now(),
                    'code' => $instructor->code ?? 'GV-DEMO',
                ]
            );
            if ($instructorRole && ! $gv1->hasRole('instructor')) {
                $gv1->assignRole($instructorRole);
            }
            // Đồng bộ user gắn instructor_id (nếu có user khác trùng instructor_id)
            User::query()
                ->where('instructor_id', $instructor->id)
                ->where('id', '!=', $gv1->id)
                ->update(['password' => Hash::make('password'), 'status' => 1]);
        }

        $gv2Instructor = Instructor::query()->orderBy('id')->skip(1)->first();
        $gv2 = null;
        if ($gv2Instructor) {
            $gv2 = User::query()->updateOrCreate(
                ['email' => 'gv2@example.com'],
                [
                    'name' => $gv2Instructor->name ?: 'Giảng viên Demo 2',
                    'password' => Hash::make('password'),
                    'user_type' => 'instructor',
                    'instructor_id' => $gv2Instructor->id,
                    'unit_id' => $gv2Instructor->unit_id,
                    'status' => 1,
                    'email_verified_at' => now(),
                    'code' => $gv2Instructor->code ?? 'GV-DEMO-2',
                ]
            );
            if ($instructorRole && ! $gv2->hasRole('instructor')) {
                $gv2->assignRole($instructorRole);
            }
        }

        $subject = Subject::query()->orderBy('id')->first();
        $class = ClassModel::query()->find($hv1->class_id) ?: ClassModel::query()->first();

        if (! $subject || ! $class) {
            $this->command?->error('Thiếu subject/class — chạy seed core trước.');

            return;
        }

        // Khóa unique thật của bảng là subject_id + class_id. ScheduleDemoSeeder
        // cũng tạo course từ cặp này, vì vậy dọn cả theo mã lẫn khóa unique để
        // Full Demo chạy được ở mọi thứ tự và chạy lặp không bị duplicate.
        $oldCourses = LmsCourse::withTrashed()
            ->where(function ($query) use ($subject, $class) {
                $query->where('code', 'LMS-DEMO-001')
                    ->orWhere(function ($uniqueQuery) use ($subject, $class) {
                        $uniqueQuery->where('subject_id', $subject->id)
                            ->where('class_id', $class->id);
                    });
            })
            ->get();
        foreach ($oldCourses as $oldCourse) {
            $oldCourse->forceDelete();
        }

        // Năm học suy từ lịch đào tạo của lớp, khớp qua academic_years.code —
        // thiếu trường này thì bảng điểm nhận từ LMS sẽ mất mốc năm học.
        $academicYearId = AcademicYear::query()
            ->where('code', TrainingSchedule::query()
                ->where('class_id', $class->id)
                ->whereNotNull('academic_year')
                ->orderByDesc('id')
                ->value('academic_year'))
            ->value('id')
            ?? AcademicYear::query()->where('is_current', true)->value('id');

        $course = LmsCourse::create([
            'code' => 'LMS-DEMO-001',
            'section_code' => 'HP-DEMO-2026',
            'title' => 'Khóa demo LMS — Điều lệnh & kỹ năng số',
            'description' => "Khóa học demo đầy đủ.\nGồm bài học, học liệu, bài tập, thi, điểm danh, tiến độ, khảo sát và chứng chỉ.",
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $academicYearId,
            'instructor_id' => $instructor?->id,
            'source_type' => 'manual',
            'status' => LmsCourse::STATUS_PUBLISHED,
            'is_standalone' => false,
            'starts_at' => now()->subDays(7)->toDateString(),
            'ends_at' => now()->addMonths(2)->toDateString(),
            'created_by' => $admin?->id,
            'updated_by' => $admin?->id,
        ]);

        // Members
        foreach ([$hv1, $hv2] as $stu) {
            LmsCourseMember::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'user_id' => $stu->id],
                ['role' => LmsCourseMember::ROLE_STUDENT, 'source' => 'class', 'status' => 'active', 'joined_at' => now(), 'synced_at' => now()]
            );
        }
        // Sync more class students
        $classStudents = User::query()->where('class_id', $class->id)->where('user_type', 'student')->take(8)->get();
        foreach ($classStudents as $stu) {
            LmsCourseMember::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'user_id' => $stu->id],
                ['role' => LmsCourseMember::ROLE_STUDENT, 'source' => 'class', 'status' => 'active', 'joined_at' => now(), 'synced_at' => now()]
            );
        }

        $lectUser = $gv1 ?: ($instructor
            ? User::query()->where('instructor_id', $instructor->id)->first()
            : null);
        if ($lectUser) {
            LmsCourseMember::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'user_id' => $lectUser->id],
                ['role' => LmsCourseMember::ROLE_LECTURER, 'source' => 'manual', 'status' => 'active', 'joined_at' => now(), 'synced_at' => now()]
            );
        }

        // Lessons
        $lessons = [];
        foreach (['Giới thiệu khóa học', 'Nội dung chính', 'Ôn tập & kiểm tra'] as $i => $title) {
            $lessons[] = LmsLesson::create([
                'lms_course_id' => $course->id,
                'title' => $title,
                'summary' => 'Bài demo '.($i + 1),
                'content' => '<p>Nội dung demo cho <strong>'.$title.'</strong>.</p>',
                'sort_order' => $i + 1,
                'week_number' => $i + 1,
                'is_published' => true,
                'published_at' => now(),
                'created_by' => $admin?->id,
            ]);
        }

        // PDF material (simple text file renamed as .pdf content for demo path)
        Storage::disk('public')->makeDirectory('lms/courses/'.$course->id.'/materials');
        $pdfRel = 'lms/courses/'.$course->id.'/materials/huong-dan-demo.txt';
        Storage::disk('public')->put($pdfRel, "Tai lieu demo LMS CDHC2\nXem file nay trong portal.\n");
        LmsMaterial::create([
            'lms_course_id' => $course->id,
            'lms_lesson_id' => $lessons[0]->id,
            'title' => 'Tài liệu hướng dẫn (demo text)',
            'kind' => 'document',
            'disk' => 'public',
            'path' => $pdfRel,
            'original_name' => 'huong-dan-demo.txt',
            'mime' => 'text/plain',
            'size_bytes' => Storage::disk('public')->size($pdfRel),
            'is_published' => true,
            'sort_order' => 1,
            'uploaded_by' => $admin?->id,
        ]);

        // Assignments gắn theo bài học
        $assignment = LmsAssignment::create([
            'lms_course_id' => $course->id,
            'lms_lesson_id' => $lessons[0]->id,
            'title' => 'Bài tập 1 — Tóm tắt bài giới thiệu',
            'description' => 'Viết tóm tắt 5–10 dòng nội dung đã học (Bài 1).',
            'due_at' => now()->addDays(14),
            'max_score' => 10,
            'allow_late' => true,
            'is_published' => true,
            'created_by' => $admin?->id,
        ]);
        LmsAssignment::create([
            'lms_course_id' => $course->id,
            'lms_lesson_id' => $lessons[1]->id ?? null,
            'title' => 'Bài tập 2 — Câu hỏi nội dung chính',
            'description' => 'Trả lời ngắn 3 câu hỏi sau bài Nội dung chính.',
            'due_at' => now()->addDays(21),
            'max_score' => 10,
            'allow_late' => true,
            'is_published' => true,
            'created_by' => $admin?->id,
        ]);

        LmsAssignmentSubmission::create([
            'lms_assignment_id' => $assignment->id,
            'user_id' => $hv1->id,
            'text_answer' => "Em đã học các nội dung demo LMS.\nHiểu cách nộp bài và xem điểm.",
            'submitted_at' => now()->subDay(),
            'status' => 'graded',
            'score' => 8.5,
            'feedback' => 'Tốt — đủ ý.',
            'graded_by' => $admin?->id,
            'graded_at' => now()->subHours(12),
        ]);

        // Question bank + exam
        $bank = LmsQuestionBank::create([
            'lms_course_id' => $course->id,
            'title' => 'NHCH Demo',
            'description' => 'Ngân hàng câu hỏi demo',
            'created_by' => $admin?->id,
        ]);
        $q1 = LmsQuestion::create([
            'lms_question_bank_id' => $bank->id,
            'type' => 'mcq',
            'stem' => 'LMS là viết tắt của?',
            'options' => ['Learning Management System', 'Local Media Server', 'Long Math Sheet'],
            'correct_answer' => '0',
            'points' => 1,
            'sort_order' => 1,
        ]);
        $q2 = LmsQuestion::create([
            'lms_question_bank_id' => $bank->id,
            'type' => 'true_false',
            'stem' => 'Học viên có thể nộp bài tập trên cổng /lms/hoc.',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
            'points' => 1,
            'sort_order' => 2,
        ]);
        $q3 = LmsQuestion::create([
            'lms_question_bank_id' => $bank->id,
            'type' => 'short',
            'stem' => 'Mã khóa demo?',
            'correct_answer' => 'LMS-DEMO-001',
            'points' => 1,
            'sort_order' => 3,
        ]);

        $exam = LmsExam::create([
            'lms_course_id' => $course->id,
            'title' => 'Kiểm tra nhanh Demo',
            'description' => '3 câu — demo auto-grade',
            'duration_minutes' => 20,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(30),
            'max_attempts' => 3,
            'pass_score' => 2,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'proctor_basic' => true,
            'is_published' => true,
            'created_by' => $admin?->id,
        ]);
        $exam->questions()->attach([
            $q1->id => ['sort_order' => 1, 'points' => 1],
            $q2->id => ['sort_order' => 2, 'points' => 1],
            $q3->id => ['sort_order' => 3, 'points' => 1],
        ]);

        LmsExamAttempt::create([
            'lms_exam_id' => $exam->id,
            'user_id' => $hv1->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHours(2)->addMinutes(5),
            'status' => 'submitted',
            'question_order' => [$q1->id, $q2->id, $q3->id],
            'answers' => [(string) $q1->id => '0', (string) $q2->id => 'true', (string) $q3->id => 'LMS-DEMO-001'],
            'score' => 3,
            'max_score' => 3,
            'proctor_events' => [],
            'blur_count' => 0,
        ]);

        // Forum
        LmsForumTopic::create([
            'lms_course_id' => $course->id,
            'user_id' => $admin?->id ?? $hv1->id,
            'title' => 'Chào mừng khóa demo',
            'body' => 'Các em trao đổi thắc mắc tại đây.',
            'is_pinned' => true,
        ]);

        // Attendance theo NGÀY (lịch) — nhiều ngày trong tháng hiện tại
        $openSession = null;
        $calendarDays = [
            ['offset' => -7, 'title' => 'Ngày khai giảng', 'mode' => 'manual', 'status' => 'closed', 'hv1' => 'present', 'hv2' => 'present'],
            ['offset' => -5, 'title' => 'Học lý thuyết', 'mode' => 'manual', 'status' => 'closed', 'hv1' => 'present', 'hv2' => 'late'],
            ['offset' => -3, 'title' => 'Thảo luận nhóm', 'mode' => 'manual', 'status' => 'closed', 'hv1' => 'present', 'hv2' => 'absent'],
            ['offset' => -1, 'title' => 'Ôn tập', 'mode' => 'qr', 'status' => 'closed', 'hv1' => 'present', 'hv2' => 'present'],
            ['offset' => 0, 'title' => 'Hôm nay — điểm danh', 'mode' => 'self', 'status' => 'open', 'hv1' => null, 'hv2' => null],
            ['offset' => 2, 'title' => 'Lịch sắp tới', 'mode' => 'self', 'status' => 'open', 'hv1' => null, 'hv2' => null],
        ];
        foreach ($calendarDays as $day) {
            $date = now()->addDays($day['offset']);
            $session = LmsAttendanceSession::create([
                'lms_course_id' => $course->id,
                'title' => $day['title'],
                'session_date' => $date->toDateString(),
                'mode' => $day['mode'],
                'status' => $day['status'],
                'open_from' => $date->copy()->startOfDay(),
                'open_until' => $day['status'] === 'open'
                    ? now()->addDays(7)->endOfDay()
                    : $date->copy()->endOfDay(),
                'checkin_token' => in_array($day['mode'], ['qr', 'self', 'gps'], true)
                    ? LmsAttendanceSession::makeToken()
                    : null,
                'created_by' => $admin?->id,
            ]);
            if ($day['status'] === 'open' && $day['mode'] === 'self' && $day['offset'] === 0) {
                $openSession = $session;
            }
            foreach ([['u' => $hv1, 'st' => $day['hv1']], ['u' => $hv2, 'st' => $day['hv2']]] as $pair) {
                if (! $pair['st']) {
                    continue;
                }
                LmsAttendanceRecord::create([
                    'lms_attendance_session_id' => $session->id,
                    'user_id' => $pair['u']->id,
                    'status' => $pair['st'],
                    'method' => 'manual',
                    'checked_in_at' => in_array($pair['st'], ['present', 'late'], true) ? $date : null,
                    'marked_by' => $admin?->id,
                ]);
            }
        }
        if (! $openSession) {
            $openSession = LmsAttendanceSession::query()
                ->where('lms_course_id', $course->id)
                ->where('status', 'open')
                ->orderByDesc('id')
                ->first();
        }

        // Chat demo (GV + HV)
        $chatAuthorAdmin = $admin?->id ?? $hv1->id;
        $chatAuthorLect = $lectUser?->id ?? $chatAuthorAdmin;
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $chatAuthorLect,
            'body' => 'Chào các em — đây là kênh chat khóa demo. GV có thể khóa chat khi cần.',
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $hv1->id,
            'body' => 'Em chào thầy/cô! Em đã xem bài 1 rồi ạ.',
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(4),
        ]);
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $hv2->id,
            'body' => 'Cho em hỏi deadline bài tập 1 là khi nào ạ?',
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $chatAuthorLect,
            'body' => 'Hạn nộp trên tab Bài tập — các em bấm vào khung bài để nộp.',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        // DM demo
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $chatAuthorLect,
            'recipient_user_id' => $hv1->id,
            'body' => '[Riêng] Em làm tốt bài tập 1 — cố gắng tiếp nhé.',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
        LmsChatMessage::create([
            'lms_course_id' => $course->id,
            'user_id' => $hv1->id,
            'recipient_user_id' => $chatAuthorLect,
            'body' => '[Riêng] Em cảm ơn thầy/cô ạ!',
            'created_at' => now()->subMinutes(40),
            'updated_at' => now()->subMinutes(40),
        ]);

        // Thông báo hệ thống cho HV (nếu bảng đã migrate)
        if (Schema::hasTable('system_notifications')) {
            foreach ([$hv1, $hv2] as $stu) {
                SystemNotification::query()->create([
                    'user_id' => $stu->id,
                    'title' => 'Lịch học cập nhật',
                    'message' => 'Có cập nhật lịch học tuần này. Xem tại Cổng LMS → Lịch học.',
                    'type' => 'student_schedule',
                    'module' => 'student-schedule',
                    'action' => 'update',
                    'url' => '/lms/hoc/schedule',
                    'created_at' => now()->subHours(6),
                    'updated_at' => now()->subHours(6),
                ]);
                SystemNotification::query()->create([
                    'user_id' => $stu->id,
                    'title' => 'Sắp có bài thi demo',
                    'message' => 'Khóa LMS-DEMO có bài kiểm tra nhanh. Vào phòng học tab Thi để làm bài.',
                    'type' => 'student_exam',
                    'module' => 'lms',
                    'action' => 'remind',
                    'url' => '/lms/hoc/courses/'.$course->id.'?tab=exams',
                    'created_at' => now()->subHours(2),
                    'updated_at' => now()->subHours(2),
                ]);
            }
        }

        // Progress for hv1 (high) and hv2 (low)
        $progress = app(LmsProgressService::class);
        foreach ($lessons as $lesson) {
            $progress->record($course, 'lesson', $lesson->id, 'view', 80, null, $hv1);
        }
        $progress->record($course, 'lesson', $lessons[0]->id, 'view', 40, null, $hv2);
        $progress->recompute($course, $hv1);
        $progress->recompute($course, $hv2);

        // Gradebook snapshot
        app(LmsGradebookService::class)->refreshStored($course);

        // Khảo sát DEMO — CHƯA có ai gửi → học viên bấm sao + gửi được ngay
        $survey = LmsSurvey::create([
            'lms_course_id' => $course->id,
            'title' => 'Khảo sát đánh giá khóa học & giảng viên',
            'description' => 'Vui lòng chấm sao từng mục rồi bấm Gửi đánh giá. (Demo — mọi HV đều thao tác được)',
            'is_published' => true,
            'is_anonymous' => false,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addMonths(3),
            'created_by' => $admin?->id,
        ]);
        foreach ([
            'Mức độ hài lòng tổng thể về khóa học',
            'Chất lượng nội dung / học liệu',
            'Đánh giá giảng viên phụ trách',
            'Phương pháp giảng dạy của giảng viên',
            'Hỗ trợ & tương tác trong quá trình học',
            'Tiện ích nền tảng LMS',
        ] as $i => $stem) {
            LmsSurveyQuestion::create([
                'lms_survey_id' => $survey->id,
                'type' => 'rating_1_5',
                'stem' => $stem,
                'is_required' => true,
                'sort_order' => $i + 1,
            ]);
        }
        LmsSurveyQuestion::create([
            'lms_survey_id' => $survey->id,
            'type' => 'text',
            'stem' => 'Góp ý thêm (tuỳ chọn)',
            'is_required' => false,
            'sort_order' => 7,
        ]);

        // Certificate template + issue for hv1
        $tpl = LmsCertificateTemplate::create([
            'lms_course_id' => $course->id,
            'title' => 'Chứng nhận hoàn thành khóa demo LMS',
            'issuer_name' => 'Trường Cao đẳng Hậu cần 2',
            'body_html' => '<p>Đã hoàn thành các hoạt động học tập demo trên hệ thống LMS.</p>',
            'min_score' => 5,
            'min_progress_pct' => 50,
            'require_survey' => true,
            'is_active' => true,
            'created_by' => $admin?->id,
        ]);

        LmsCertificate::create([
            'lms_course_id' => $course->id,
            'user_id' => $hv1->id,
            'lms_certificate_template_id' => $tpl->id,
            'code' => LmsCertificate::makeCode(),
            'title' => $tpl->title,
            'final_score' => 8.5,
            'progress_pct' => LmsProgressSummary::query()->where('lms_course_id', $course->id)->where('user_id', $hv1->id)->value('overall_pct'),
            'issued_at' => now(),
            'issued_by' => $admin?->id,
            'status' => 'issued',
            'meta' => ['issuer' => $tpl->issuer_name, 'course_title' => $course->title],
        ]);

        try {
            app(LmsAlertService::class)->evaluateCourse($course);
        } catch (\Throwable $e) {
            // ok
        }

        $this->command?->info('✓ LMS demo course #'.$course->id.' — '.$course->title);
        $this->command?->warn('Admin: admin@example.com / password');
        $this->command?->warn('GV1:   giangvien@example.com / password  (phụ trách khóa demo)');
        if ($gv2) {
            $this->command?->warn('GV2:   gv2@example.com / password');
        }
        $this->command?->warn('HV1:  hocvien@example.com / password');
        $this->command?->warn('HV2:  hv2@example.com / password');
        $this->command?->info('Open attendance self token: '.($openSession->checkin_token ?? '—'));
        $this->command?->info('Learner portal: /lms/hoc  · Admin: /lms/courses/'.$course->id);
        $this->command?->info('Hướng dẫn LMS: docs/HDSD_LMS.md');
    }
}
