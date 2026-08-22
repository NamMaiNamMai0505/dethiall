<?php

use App\Http\Middleware\EnsureApprovalAgencyRouteScope;
use Illuminate\Support\Facades\Route;
use Modules\Grades\Controllers\AcademicHubController;
use Modules\Grades\Controllers\GradeBookController;
use Modules\Grades\Controllers\GradeHubController;
use Modules\Grades\Controllers\GraduationProfileController;
use Modules\Grades\Controllers\GraduationSummaryController;
use Modules\Grades\Controllers\ScoreEntryController;
use Modules\Grades\Controllers\StudentExtractController;

Route::middleware(['web', 'auth', EnsureApprovalAgencyRouteScope::class])
    ->prefix('grades')
    ->name('grades.')
    ->group(function () {
    // Entry + wizard (nhập điểm theo môn/lớp hoặc khoa)
    Route::get('/', GradeHubController::class)->name('hub');

    // GV: Môn → Lớp → phòng điểm
    Route::get('/subjects/{subject}/classes', [GradeHubController::class, 'classesForSubject'])->name('subjects.classes');
    Route::get('/subjects/{subject}/classes/{class}', [GradeHubController::class, 'room'])->name('room');

    // PDOT: Khoa → Môn → Lớp → phòng điểm
    Route::get('/faculties/{unit}/subjects', [GradeHubController::class, 'facultySubjects'])->name('faculties.subjects');
    Route::get('/faculties/{unit}/subjects/{subject}/classes', [GradeHubController::class, 'facultyClasses'])->name('faculties.classes');
    Route::get('/faculties/{unit}/subjects/{subject}/classes/{class}', [GradeHubController::class, 'facultyRoom'])->name('faculties.room');

    // Legacy class room → redirect hub
    Route::get('/classes/{class}', function ($class) {
        return redirect()->route('grades.hub');
    })->name('classes.show');

    Route::get('/books', [GradeBookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [GradeBookController::class, 'create'])->name('books.create');
    Route::post('/books', [GradeBookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}', [GradeBookController::class, 'show'])->name('books.show');
    Route::post('/books/{book}/scores', [GradeBookController::class, 'saveScores'])->name('books.scores');
    Route::post('/books/{book}/lock', [GradeBookController::class, 'lock'])->name('books.lock');
    Route::post('/books/{book}/approve', [GradeBookController::class, 'approve'])->name('books.approve');
    Route::post('/books/{book}/request-unlock', [GradeBookController::class, 'requestUnlock'])->name('books.request-unlock');
    Route::get('/books/{book}/export', [GradeBookController::class, 'export'])->name('books.export');
    Route::post('/change-requests/{changeRequest}/review', [GradeBookController::class, 'reviewRequest'])->name('requests.review');

    // —— Tổng kết năm học / TN / trích ngang ——
    Route::prefix('academic')->name('academic.')->group(function () {
        Route::get('/', AcademicHubController::class)->name('hub');

        // Tổng kết khóa / xét TN
        Route::get('/summary', [GraduationSummaryController::class, 'index'])->name('summary.index');
        Route::post('/summary/sessions', [GraduationSummaryController::class, 'storeSession'])->name('summary.sessions.store');
        Route::get('/summary/{session}', [GraduationSummaryController::class, 'show'])->name('summary.show');
        Route::post('/summary/{session}/calculate', [GraduationSummaryController::class, 'calculate'])->name('summary.calculate');
        Route::post('/summary/{session}/submit', [GraduationSummaryController::class, 'submitApprove'])->name('summary.submit');
        Route::post('/summary/{session}/approve', [GraduationSummaryController::class, 'approve'])->name('summary.approve');
        Route::post('/summary/{session}/reorder', [GraduationSummaryController::class, 'reorder'])->name('summary.reorder');
        Route::get('/summary/{session}/print', [GraduationSummaryController::class, 'printBook'])->name('summary.print');
        Route::patch('/results/{result}', [GraduationSummaryController::class, 'updateResult'])->name('results.update');

        // Hồ sơ TN
        Route::get('/profiles', [GraduationProfileController::class, 'index'])->name('profiles.index');
        Route::post('/profiles', [GraduationProfileController::class, 'store'])->name('profiles.store');
        Route::get('/profiles/{profile}/edit', [GraduationProfileController::class, 'edit'])->name('profiles.edit');
        Route::put('/profiles/{profile}', [GraduationProfileController::class, 'update'])->name('profiles.update');
        Route::post('/profiles/{profile}/submit', [GraduationProfileController::class, 'submitApprove'])->name('profiles.submit');
        Route::post('/profiles/{profile}/approve', [GraduationProfileController::class, 'approve'])->name('profiles.approve');
        Route::get('/profiles/{profile}/print-score', [GraduationProfileController::class, 'printScore'])->name('profiles.print-score');
        Route::get('/profiles/{profile}/print-diploma', [GraduationProfileController::class, 'printDiploma'])->name('profiles.print-diploma');

        // Trích ngang
        Route::get('/extracts', [StudentExtractController::class, 'index'])->name('extracts.index');
        Route::get('/extracts/print', [StudentExtractController::class, 'print'])->name('extracts.print');
        Route::get('/extracts/{student}/edit', [StudentExtractController::class, 'edit'])->name('extracts.edit');
        Route::put('/extracts/{student}', [StudentExtractController::class, 'update'])->name('extracts.update');

        // Chọn lớp–môn vào điểm
        Route::get('/entry', [ScoreEntryController::class, 'index'])->name('entry.index');
        Route::post('/entry/open', [ScoreEntryController::class, 'openBook'])->name('entry.open');
        Route::post('/entry/import', [ScoreEntryController::class, 'importFile'])->name('entry.import');
        Route::post('/entry/conduct', [ScoreEntryController::class, 'saveConduct'])->name('entry.conduct');
    });
    });
