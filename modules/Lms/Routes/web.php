<?php

use Illuminate\Support\Facades\Route;
use Modules\Lms\Controllers\AlertController;
use Modules\Lms\Controllers\AssignmentController;
use Modules\Lms\Controllers\AttendanceController;
use Modules\Lms\Controllers\CampusNetworkController;
use Modules\Lms\Controllers\CertificateController;
use Modules\Lms\Controllers\ChatController;
use Modules\Lms\Controllers\CourseController;
use Modules\Lms\Controllers\ExamController;
use Modules\Lms\Controllers\ForumController;
use Modules\Lms\Controllers\GradebookController;
// Controllers reused by teach routes (Sprint 6–7): Survey, Certificate, Alert, Gradebook
use Modules\Lms\Controllers\HubController;
use Modules\Lms\Controllers\Learn\CourseRoomController;
use Modules\Lms\Controllers\Learn\HomeController as LearnHomeController;
use Modules\Lms\Controllers\Learn\ProfileController as LearnProfileController;
use Modules\Lms\Controllers\Learn\ScheduleController as LearnScheduleController;
use Modules\Lms\Controllers\LessonController;
use Modules\Lms\Controllers\MaterialController;
use Modules\Lms\Controllers\MemberController;
use Modules\Lms\Controllers\ProgressController;
use Modules\Lms\Controllers\ProvisioningController;
use Modules\Lms\Controllers\SurveyController;
use Modules\Lms\Controllers\Teach\AssignmentController as TeachAssignmentController;
use Modules\Lms\Controllers\Teach\AttendanceController as TeachAttendanceController;
use Modules\Lms\Controllers\Teach\ContentController as TeachContentController;
use Modules\Lms\Controllers\Teach\EngageController as TeachEngageController;
use Modules\Lms\Controllers\Teach\ExamController as TeachExamController;
use Modules\Lms\Controllers\Teach\HomeController as TeachHomeController;
use Modules\Lms\Controllers\Teach\ScheduleController as TeachScheduleController;
use Modules\Lms\Controllers\Teach\StandardHours\ConversionRecordController as TeachConversionRecordController;
use Modules\Lms\Controllers\Teach\StandardHours\HourExchangeController as TeachHourExchangeController;
use Modules\Lms\Controllers\Teach\StandardHours\HubController as TeachStandardHoursHubController;
use Modules\Lms\Controllers\Teach\StandardHours\MyResultController as TeachMyResultController;
use Modules\Lms\Controllers\Teach\StandardHours\ExternalActivityController as TeachExternalActivityController;
use Modules\Lms\Controllers\Teach\StandardHours\ResearchRecordController as TeachResearchRecordController;
use Modules\Lms\Models\LmsCourse;

/*
|--------------------------------------------------------------------------
| LMS Routes
| - /lms          : admin/manager hub
| - /lms/hoc/*    : portal học viên
| - /lms/gv/*     : portal giảng viên (Sprint GV-1+)
| Sprint 3: assignments + exams
| Sprint 4: gradebook, attendance, progress, alerts, standalone members
| Sprint 5: certificates, surveys (no SSO)
|--------------------------------------------------------------------------
*/

// Public certificate verify (no auth)
Route::middleware(['web'])->get('/lms/certificates/verify', [CertificateController::class, 'verify'])->name('lms.certificates.verify');

// Admin: Wi‑Fi trường (MAC AP / CIDR) — sidebar, không portal GV
Route::middleware(['web', 'auth'])->prefix('campus-network')->name('campus-network.')->group(function () {
    Route::get('/', [CampusNetworkController::class, 'index'])->name('index');
    // P0/P2: static routes trước {network}
    Route::get('/test-ip', [CampusNetworkController::class, 'testIp'])->name('test-ip');
    Route::get('/stats', [CampusNetworkController::class, 'stats'])->name('stats');
    Route::get('/create', [CampusNetworkController::class, 'create'])->name('create');
    Route::post('/', [CampusNetworkController::class, 'store'])->name('store');
    Route::get('/{network}', [CampusNetworkController::class, 'show'])->name('show');
    Route::get('/{network}/edit', [CampusNetworkController::class, 'edit'])->name('edit');
    Route::put('/{network}', [CampusNetworkController::class, 'update'])->name('update');
    Route::delete('/{network}', [CampusNetworkController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth'])->prefix('lms')->name('lms.')->group(function () {

    Route::get('/', [HubController::class, 'index'])->name('hub');
    Route::get('/index', [HubController::class, 'index'])->name('index');

    // —— Instructor portal (GV-1 / GV-2) ——
    Route::prefix('gv')->name('teach.')->group(function () {
        Route::get('/', [TeachHomeController::class, 'index'])->name('home');
        Route::get('/schedule', [TeachScheduleController::class, 'index'])->name('schedule');
        // Shortcut: /lms/gv/courses/{id} → phòng học mode teach
        Route::get('/courses/{course}', function (LmsCourse $course) {
            return redirect()->route('lms.learn.courses.show', ['course' => $course, 'mode' => 'teach']);
        })->name('courses.show');

        // GV-2: soạn bài + tài liệu
        Route::post('/courses/{course}/lessons', [TeachContentController::class, 'storeLesson'])->name('lessons.store');
        Route::put('/courses/{course}/lessons/{lesson}', [TeachContentController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/courses/{course}/lessons/{lesson}', [TeachContentController::class, 'destroyLesson'])->name('lessons.destroy');
        Route::post('/courses/{course}/lessons/{lesson}/toggle', [TeachContentController::class, 'toggleLesson'])->name('lessons.toggle');
        Route::post('/courses/{course}/lessons/{lesson}/move', [TeachContentController::class, 'moveLesson'])->name('lessons.move');

        Route::post('/courses/{course}/materials', [TeachContentController::class, 'storeMaterial'])->name('materials.store');
        Route::delete('/courses/{course}/materials/{material}', [TeachContentController::class, 'destroyMaterial'])->name('materials.destroy');
        Route::post('/courses/{course}/materials/{material}/toggle', [TeachContentController::class, 'toggleMaterial'])->name('materials.toggle');
        Route::delete('/courses/{course}/scorm/{scorm}', [TeachContentController::class, 'destroyScorm'])->name('scorm.destroy');

        // GV-3: bài tập + chấm
        Route::post('/courses/{course}/assignments', [TeachAssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/courses/{course}/assignments/{assignment}', [TeachAssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/courses/{course}/assignments/{assignment}', [TeachAssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/courses/{course}/assignments/{assignment}/toggle', [TeachAssignmentController::class, 'toggle'])->name('assignments.toggle');
        Route::post('/courses/{course}/assignments/{assignment}/submissions/{submission}/grade', [TeachAssignmentController::class, 'grade'])->name('assignments.grade');
        Route::get('/courses/{course}/assignments/{assignment}/submissions/{submission}/download', [TeachAssignmentController::class, 'downloadOne'])->name('assignments.download-one');
        Route::get('/courses/{course}/assignments/{assignment}/download-all', [TeachAssignmentController::class, 'downloadAll'])->name('assignments.download-all');

        // GV-5: điểm danh lớp (Wi‑Fi MAC chỉ admin /campus-network)
        Route::post('/courses/{course}/attendance', [TeachAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/courses/{course}/attendance/{session}/close', [TeachAttendanceController::class, 'close'])->name('attendance.close');
        Route::post('/courses/{course}/attendance/{session}/reopen', [TeachAttendanceController::class, 'reopen'])->name('attendance.reopen');
        Route::post('/courses/{course}/attendance/{session}/rotate-token', [TeachAttendanceController::class, 'rotateToken'])->name('attendance.rotate-token');
        Route::post('/courses/{course}/attendance/{session}/mark', [TeachAttendanceController::class, 'mark'])->name('attendance.mark');
        Route::post('/courses/{course}/attendance/{session}/mark-all-present', [TeachAttendanceController::class, 'markAllPresent'])->name('attendance.mark-all-present');

        // GV-7: thông báo lớp
        Route::post('/courses/{course}/announce', [TeachEngageController::class, 'announce'])->name('announce');

        // GV-4 + Sprint 6: thi online + CRUD câu NHCH
        Route::post('/courses/{course}/exam-banks', [TeachExamController::class, 'storeBank'])->name('exam-banks.store');
        Route::post('/courses/{course}/exam-banks/import', [TeachExamController::class, 'importBank'])->name('exam-banks.import');
        Route::post('/courses/{course}/exam-banks/{bank}/submit', [TeachExamController::class, 'submitBank'])->name('exam-banks.submit');
        Route::post('/courses/{course}/exam-banks/submit-pending', [TeachExamController::class, 'submitPendingBanks'])->name('exam-banks.submit-pending');
        Route::post('/courses/{course}/exam-banks/{bank}/approve', [TeachExamController::class, 'approveBank'])->name('exam-banks.approve');
        Route::delete('/courses/{course}/exam-banks/{bank}', [TeachExamController::class, 'destroyBank'])->name('exam-banks.destroy');
        Route::post('/courses/{course}/exam-banks/{bank}/questions', [TeachExamController::class, 'storeQuestion'])->name('exam-questions.store');
        Route::put('/courses/{course}/exam-banks/{bank}/questions/{question}', [TeachExamController::class, 'updateQuestion'])->name('exam-questions.update');
        Route::delete('/courses/{course}/exam-banks/{bank}/questions/{question}', [TeachExamController::class, 'destroyQuestion'])->name('exam-questions.destroy');
        Route::post('/courses/{course}/exam-banks/{bank}/questions/{question}/move', [TeachExamController::class, 'moveQuestion'])->name('exam-questions.move');
        Route::post('/courses/{course}/exams', [TeachExamController::class, 'storeExam'])->name('exams.store');
        Route::put('/courses/{course}/exams/{exam}/schedule', [TeachExamController::class, 'updateExamSchedule'])->name('exams.schedule.update');
        Route::post('/courses/{course}/exams/{exam}/toggle', [TeachExamController::class, 'toggleExam'])->name('exams.toggle');
        Route::delete('/courses/{course}/exams/{exam}', [TeachExamController::class, 'destroyExam'])->name('exams.destroy');
        Route::get('/courses/{course}/exams/{exam}/attempts', [TeachExamController::class, 'attempts'])->name('exams.attempts');
        Route::get('/courses/{course}/exams/{exam}/export', [TeachExamController::class, 'exportCsv'])->name('exams.export');

        // Sprint 6 G3: override / refresh gradebook (reuse GradebookController → redirect teach tab)
        Route::post('/courses/{course}/gradebook/refresh', [GradebookController::class, 'refresh'])->name('gradebook.refresh');
        Route::post('/courses/{course}/gradebook/transfer', [GradebookController::class, 'transferToGrades'])->name('gradebook.transfer');
        Route::post('/courses/{course}/gradebook/{user}', [GradebookController::class, 'override'])->name('gradebook.override');

        // Sprint 7: khảo sát / chứng chỉ / cảnh báo (reuse controllers → tab class)
        Route::post('/courses/{course}/surveys', [SurveyController::class, 'store'])->name('surveys.store');
        Route::post('/courses/{course}/surveys/apply-template', [SurveyController::class, 'applyTemplate'])->name('surveys.apply-template');
        Route::post('/courses/{course}/surveys/{survey}/questions', [SurveyController::class, 'storeQuestion'])->name('surveys.questions.store');
        Route::post('/courses/{course}/surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
        Route::post('/courses/{course}/certificates/issue-eligible', [CertificateController::class, 'issueEligible'])->name('certificates.issue-eligible');
        Route::post('/courses/{course}/certificates/{user}/issue', [CertificateController::class, 'issueOne'])->name('certificates.issue-one');
        Route::post('/courses/{course}/alerts/evaluate', [AlertController::class, 'evaluate'])->name('alerts.evaluate');
        Route::post('/courses/{course}/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

        // Giờ chuẩn GV — bản sao native, tự phục vụ GV (không đụng modules/StandardHours)
        Route::prefix('standard-hours')->name('standard-hours.')->group(function () {
            Route::get('/', [TeachStandardHoursHubController::class, 'index'])->name('hub');
            Route::get('/guide', [TeachStandardHoursHubController::class, 'guide'])->name('guide');

            Route::get('/my-results', [TeachMyResultController::class, 'index'])->name('my-results.index');
            Route::get('/my-results/export', [TeachMyResultController::class, 'export'])->name('my-results.export');
            Route::get('/my-results/declaration/edit', [TeachMyResultController::class, 'declaration'])
                ->name('my-results.declaration');
            Route::post('/my-results/declaration/retrieve-schedule', [TeachMyResultController::class, 'retrieveSchedule'])
                ->name('my-results.retrieve-schedule');
            Route::post('/my-results/declaration', [TeachMyResultController::class, 'storeDeclaration'])
                ->name('my-results.store-declaration');
            Route::get('/my-results/{yearlyResult}', [TeachMyResultController::class, 'show'])->name('my-results.show');

            Route::resource('conversion-records', TeachConversionRecordController::class)
                ->parameters(['conversion-records' => 'conversionRecord']);
            Route::patch('/conversion-records/{conversionRecord}/submit', [TeachConversionRecordController::class, 'submit'])
                ->name('conversion-records.submit');

            Route::resource('research-records', TeachResearchRecordController::class)
                ->parameters(['research-records' => 'researchRecord']);
            Route::patch('/research-records/{researchRecord}/submit', [TeachResearchRecordController::class, 'submit'])
                ->name('research-records.submit');

            Route::resource('external-activities', TeachExternalActivityController::class)
                ->parameters(['external-activities' => 'externalActivity']);
            Route::patch('/external-activities/{externalActivity}/submit', [TeachExternalActivityController::class, 'submit'])
                ->name('external-activities.submit');

            // Quy đổi giờ NCKH ↔ HĐCM: chỉ xem số dư + lịch sử (quyết định bù giờ
            // là đặc quyền cấp quản lý ở admin shell — xem ghi chú trong controller).
            Route::get('/hour-exchanges', [TeachHourExchangeController::class, 'index'])->name('hour-exchanges.index');
        });
    });

    // —— Learner portal ——
    Route::prefix('hoc')->name('learn.')->group(function () {
        Route::get('/', [LearnHomeController::class, 'index'])->name('home');
        Route::get('/schedule', [LearnScheduleController::class, 'index'])->name('schedule');
        Route::get('/profile', [LearnProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [LearnProfileController::class, 'update'])->name('profile.update');

        Route::get('/courses/{course}', [CourseRoomController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/lessons/{lesson}', [CourseRoomController::class, 'lesson'])->name('lessons.show');
        Route::get('/courses/{course}/materials/{material}', [CourseRoomController::class, 'material'])->name('materials.open');
        Route::get('/courses/{course}/scorm/{scorm}', [CourseRoomController::class, 'scorm'])->name('scorm.play');

        Route::get('/courses/{course}/forum', [ForumController::class, 'index'])->name('forum.index');
        Route::post('/courses/{course}/forum', [ForumController::class, 'storeTopic'])->name('forum.store');
        Route::get('/courses/{course}/forum/{topic}', [ForumController::class, 'show'])->name('forum.show');
        Route::post('/courses/{course}/forum/{topic}/reply', [ForumController::class, 'storeReply'])->name('forum.reply');

        Route::get('/courses/{course}/chat', [ChatController::class, 'index'])->name('chat.index');
        // Sprint 8 T7: rate limit chat (30 msg / phút)
        Route::post('/courses/{course}/chat', [ChatController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('chat.store');
        Route::get('/courses/{course}/chat/poll', [ChatController::class, 'poll'])->name('chat.poll');
        Route::get('/courses/{course}/chat/history', [ChatController::class, 'history'])->name('chat.history');
        Route::post('/courses/{course}/chat/toggle-lock', [ChatController::class, 'toggleLock'])->name('chat.toggle-lock');
        Route::delete('/courses/{course}/chat/{message}', [ChatController::class, 'destroy'])->name('chat.destroy');

        Route::post('/courses/{course}/forum/{topic}/pin', [ForumController::class, 'togglePin'])->name('forum.pin');
        Route::post('/courses/{course}/forum/{topic}/lock', [ForumController::class, 'toggleLock'])->name('forum.lock');

        // Sprint 3 learner
        Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/courses/{course}/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');
        Route::get('/courses/{course}/assignments/{assignment}/submission/download', [AssignmentController::class, 'downloadOwn'])
            ->name('assignments.download-own');
        Route::get('/courses/{course}/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('/courses/{course}/exams/{exam}/start', [ExamController::class, 'take'])->name('exams.start');
        Route::get('/courses/{course}/exams/{exam}/take', [ExamController::class, 'take'])->name('exams.take');
        Route::post('/courses/{course}/exams/{exam}/attempts/{attempt}', [ExamController::class, 'submit'])->name('exams.submit');
        Route::post('/courses/{course}/exams/{exam}/attempts/{attempt}/proctor', [ExamController::class, 'proctorHeartbeat'])->name('exams.proctor');
        Route::get('/courses/{course}/exams/{exam}/attempts/{attempt}/result', [ExamController::class, 'result'])->name('exams.result');
        // Sprint 9 T3 SCORM commit
        Route::post('/courses/{course}/scorm/{scorm}/commit', [CourseRoomController::class, 'scormCommit'])->name('scorm.commit');

        // Sprint 4 learner
        Route::get('/courses/{course}/grades', [GradebookController::class, 'my'])->name('grades.my');
        Route::get('/courses/{course}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        // GET: quét QR (link token) · POST: nút điểm danh trong app
        // Sprint 8 T7: rate limit check-in (10 / phút / user)
        Route::match(['get', 'post'], '/courses/{course}/attendance/{session}/checkin', [AttendanceController::class, 'checkin'])
            ->middleware('throttle:10,1')
            ->name('attendance.checkin');
        Route::get('/courses/{course}/progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('/courses/{course}/progress/poll', [ProgressController::class, 'poll'])->name('progress.poll');
        Route::post('/courses/{course}/progress/record', [ProgressController::class, 'record'])->name('progress.record');
        Route::get('/courses/{course}/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('/courses/{course}/alerts/{alert}/read', [AlertController::class, 'markRead'])->name('alerts.read');

        // Sprint 5 learner
        Route::get('/courses/{course}/certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::post('/courses/{course}/certificates/request', [CertificateController::class, 'requestIssue'])->name('certificates.request');
        Route::get('/courses/{course}/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
        Route::get('/courses/{course}/surveys', [SurveyController::class, 'index'])->name('surveys.index');
        Route::get('/courses/{course}/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
        Route::post('/courses/{course}/surveys/{survey}', [SurveyController::class, 'submit'])->name('surveys.submit');
    });

    // —— Admin / manager ——
    Route::get('/provisioning', [ProvisioningController::class, 'index'])->name('provisioning.index');
    Route::post('/provisioning', [ProvisioningController::class, 'store'])->name('provisioning.store');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::get('/courses/suggest-instructors', [CourseController::class, 'suggestInstructors'])->name('courses.suggest-instructors');
    Route::get('/courses/class-student-count', [CourseController::class, 'classStudentCount'])->name('courses.class-student-count');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    // Sprint 8 M2: export điểm multi-course
    Route::get('/gradebook/export-multi', [GradebookController::class, 'exportMultiForm'])->name('gradebook.export-multi');
    Route::post('/gradebook/export-multi', [GradebookController::class, 'exportMulti'])->name('gradebook.export-multi.download');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/courses/{course}/sync-members', [CourseController::class, 'syncMembers'])->name('courses.sync-members');
    Route::post('/courses/{course}/sync-content', [CourseController::class, 'syncContent'])->name('courses.sync-content');
    Route::post('/courses/{course}/members', [MemberController::class, 'store'])->name('courses.members.store');
    Route::delete('/courses/{course}/members/{member}', [MemberController::class, 'destroy'])->name('courses.members.destroy');

    Route::prefix('courses/{course}/lessons')->name('courses.lessons.')->group(function () {
        Route::get('/', [LessonController::class, 'index'])->name('index');
        Route::get('/create', [LessonController::class, 'create'])->name('create');
        Route::post('/', [LessonController::class, 'store'])->name('store');
        Route::get('/{lesson}', [LessonController::class, 'show'])->name('show');
        Route::get('/{lesson}/edit', [LessonController::class, 'edit'])->name('edit');
        Route::put('/{lesson}', [LessonController::class, 'update'])->name('update');
        Route::delete('/{lesson}', [LessonController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('courses/{course}/materials')->name('courses.materials.')->group(function () {
        Route::get('/', [MaterialController::class, 'index'])->name('index');
        Route::post('/', [MaterialController::class, 'store'])->name('store');
        Route::get('/{material}/download', [MaterialController::class, 'download'])->name('download');
        Route::delete('/{material}', [MaterialController::class, 'destroy'])->name('destroy');
        Route::delete('/scorm/{scorm}', [MaterialController::class, 'destroyScorm'])->name('destroy-scorm');
    });

    Route::get('/courses/{course}/forum', [ForumController::class, 'index'])->name('courses.forum.index');
    Route::post('/courses/{course}/forum', [ForumController::class, 'storeTopic'])->name('courses.forum.store');
    Route::get('/courses/{course}/forum/{topic}', [ForumController::class, 'show'])->name('courses.forum.show');
    Route::post('/courses/{course}/forum/{topic}/reply', [ForumController::class, 'storeReply'])->name('courses.forum.reply');
    // pin/lock also registered above near chat (courses.forum.pin / lock)

    Route::get('/courses/{course}/chat', [ChatController::class, 'index'])->name('courses.chat.index');
    Route::post('/courses/{course}/chat', [ChatController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('courses.chat.store');
    Route::get('/courses/{course}/chat/poll', [ChatController::class, 'poll'])->name('courses.chat.poll');
    Route::get('/courses/{course}/chat/history', [ChatController::class, 'history'])->name('courses.chat.history');
    Route::post('/courses/{course}/chat/toggle-lock', [ChatController::class, 'toggleLock'])->name('courses.chat.toggle-lock');
    Route::delete('/courses/{course}/chat/{message}', [ChatController::class, 'destroy'])->name('courses.chat.destroy');

    Route::post('/courses/{course}/forum/{topic}/pin', [ForumController::class, 'togglePin'])->name('courses.forum.pin');
    Route::post('/courses/{course}/forum/{topic}/lock', [ForumController::class, 'toggleLock'])->name('courses.forum.lock');

    // Sprint 3 admin
    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('courses.assignments.index');
    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('courses.assignments.store');
    Route::get('/courses/{course}/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->name('courses.assignments.submissions');
    Route::post('/courses/{course}/assignments/{assignment}/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->name('courses.assignments.grade');

    Route::get('/courses/{course}/exams', [ExamController::class, 'index'])->name('courses.exams.index');
    Route::post('/courses/{course}/exams/banks', [ExamController::class, 'storeBank'])->name('courses.exams.banks.store');
    Route::post('/courses/{course}/exams/banks/{bank}/questions', [ExamController::class, 'storeQuestion'])->name('courses.exams.questions.store');
    Route::post('/courses/{course}/exams', [ExamController::class, 'storeExam'])->name('courses.exams.store');
    Route::get('/courses/{course}/exams/{exam}/attempts', [ExamController::class, 'attempts'])->name('courses.exams.attempts');

    // Sprint 4 admin
    Route::get('/courses/{course}/gradebook', [GradebookController::class, 'show'])->name('courses.gradebook');
    Route::post('/courses/{course}/gradebook/refresh', [GradebookController::class, 'refresh'])->name('courses.gradebook.refresh');
    Route::post('/courses/{course}/gradebook/transfer', [GradebookController::class, 'transferToGrades'])->name('courses.gradebook.transfer');
    Route::post('/courses/{course}/gradebook/{user}', [GradebookController::class, 'override'])->name('courses.gradebook.override');

    Route::get('/courses/{course}/attendance', [AttendanceController::class, 'index'])->name('courses.attendance.index');
    Route::post('/courses/{course}/attendance', [AttendanceController::class, 'storeSession'])->name('courses.attendance.store');
    Route::get('/courses/{course}/attendance/{session}', [AttendanceController::class, 'show'])->name('courses.attendance.show');
    Route::post('/courses/{course}/attendance/{session}/mark', [AttendanceController::class, 'mark'])->name('courses.attendance.mark');
    Route::post('/courses/{course}/attendance/{session}/close', [AttendanceController::class, 'close'])->name('courses.attendance.close');

    Route::get('/courses/{course}/progress', [ProgressController::class, 'index'])->name('courses.progress.index');
    Route::get('/courses/{course}/alerts', [AlertController::class, 'index'])->name('courses.alerts.index');
    Route::post('/courses/{course}/alerts/evaluate', [AlertController::class, 'evaluate'])->name('courses.alerts.evaluate');
    Route::post('/courses/{course}/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('courses.alerts.resolve');

    // Sprint 5 admin
    Route::get('/courses/{course}/certificates', [CertificateController::class, 'index'])->name('courses.certificates.index');
    Route::post('/courses/{course}/certificates/template', [CertificateController::class, 'saveTemplate'])->name('courses.certificates.template');
    Route::post('/courses/{course}/certificates/issue-eligible', [CertificateController::class, 'issueEligible'])->name('courses.certificates.issue-eligible');
    Route::post('/courses/{course}/certificates/{user}/issue', [CertificateController::class, 'issueOne'])->name('courses.certificates.issue-one');
    Route::get('/courses/{course}/certificates/{certificate}/view', [CertificateController::class, 'show'])->name('courses.certificates.show');

    Route::get('/courses/{course}/surveys', [SurveyController::class, 'index'])->name('courses.surveys.index');
    Route::post('/courses/{course}/surveys', [SurveyController::class, 'store'])->name('courses.surveys.store');
    Route::post('/courses/{course}/surveys/apply-template', [SurveyController::class, 'applyTemplate'])->name('courses.surveys.apply-template');
    Route::get('/courses/{course}/surveys/{survey}', [SurveyController::class, 'show'])->name('courses.surveys.show');
    Route::post('/courses/{course}/surveys/{survey}/questions', [SurveyController::class, 'storeQuestion'])->name('courses.surveys.questions.store');
    Route::post('/courses/{course}/surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('courses.surveys.publish');

    // Sprint 9 M6 — survey templates (cross-course)
    Route::get('/survey-templates', [SurveyController::class, 'templatesIndex'])->name('survey-templates.index');
    Route::post('/survey-templates', [SurveyController::class, 'templatesStore'])->name('survey-templates.store');
    Route::get('/survey-templates/{template}', [SurveyController::class, 'templatesShow'])->name('survey-templates.show');
    Route::post('/survey-templates/{template}/questions', [SurveyController::class, 'templatesStoreQuestion'])->name('survey-templates.questions.store');
});
