<?php

use App\Support\TrainingDept;
use App\Support\TrainingScheduleAccess;
use Illuminate\Support\Facades\Route;
use Modules\Subject\Controllers\SubjectController;
use Modules\Subject\Controllers\SubjectLessonController;
use Modules\Subject\Models\Subject;

/*
|--------------------------------------------------------------------------
| Subject Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Subject module.
| These routes are loaded by the SubjectServiceProvider within a
| group which contains the "web" middleware group and "auth" middleware.
|
*/

// Bài học / khung CTĐT (menu riêng)
Route::prefix('lessons-manage')->group(function () {
    Route::get('/', [SubjectLessonController::class, 'index'])
        ->middleware('permission:subject-lessons.index')
        ->name('subject-lessons.index');
    Route::get('/import', [SubjectLessonController::class, 'importForm'])
        ->middleware('permission:subject-lessons.create')
        ->name('subject-lessons.import');
    Route::get('/import/template', [SubjectLessonController::class, 'downloadLessonsTemplate'])
        ->middleware('permission:subject-lessons.create')
        ->name('subject-lessons.import.template');
    Route::post('/import', [SubjectLessonController::class, 'import'])
        ->middleware('permission:subject-lessons.create')
        ->name('subject-lessons.import.store');
    Route::get('/create', [SubjectLessonController::class, 'create'])
        ->middleware('permission:subject-lessons.create')
        ->name('subject-lessons.create');
    Route::post('/', [SubjectLessonController::class, 'store'])
        ->middleware('permission:subject-lessons.create')
        ->name('subject-lessons.store');
    Route::get('/{subjectLesson}/edit', [SubjectLessonController::class, 'edit'])
        ->middleware('permission:subject-lessons.edit')
        ->name('subject-lessons.edit');
    Route::put('/{subjectLesson}', [SubjectLessonController::class, 'update'])
        ->middleware('permission:subject-lessons.edit')
        ->name('subject-lessons.update');
    Route::delete('/{subjectLesson}', [SubjectLessonController::class, 'destroy'])
        ->middleware('permission:subject-lessons.delete')
        ->name('subject-lessons.destroy');
    Route::post('/bulk-delete', [SubjectLessonController::class, 'bulkDestroy'])
        ->middleware('permission:subject-lessons.delete')
        ->name('subject-lessons.bulk-delete');
    Route::get('/api/by-subject', [SubjectLessonController::class, 'bySubject'])
        ->middleware('permission:subject-lessons.index')
        ->name('subject-lessons.api.by-subject');
});

// Subject resource routes
Route::resource('/', SubjectController::class, [
    'names' => [
        'index' => 'subjects.index',
        'create' => 'subjects.create',
        'store' => 'subjects.store',
        'show' => 'subjects.show',
        'edit' => 'subjects.edit',
        'update' => 'subjects.update',
        'destroy' => 'subjects.destroy',
    ],
    'parameters' => [
        '' => 'subject',
    ],
]);

// Additional routes
Route::prefix('/')->group(function () {

    // Toggle status
    Route::patch('{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])
        ->middleware('permission:subjects.edit')
        ->name('subjects.toggle-status');

    // Toggle required status
    Route::patch('{subject}/toggle-required', [SubjectController::class, 'toggleRequired'])
        ->middleware('permission:subjects.edit')
        ->name('subjects.toggle-required');

    // Assign instructors to subject
    Route::middleware(['permission:teaching-assignments.create'])->group(function () {
        Route::get('{subject}/assign-instructors', [SubjectController::class, 'assignInstructors'])
            ->name('subjects.assign-instructors');
        Route::post('{subject}/assign-instructors', [SubjectController::class, 'storeInstructorAssignments'])
            ->name('subjects.store-instructor-assignments');
    });

    // Restore soft deleted
    Route::patch('{id}/restore', [SubjectController::class, 'restore'])
        ->middleware('permission:subjects.delete')
        ->name('subjects.restore')
        ->where('id', '[0-9]+');

    // Bulk delete (chon nhieu roi xoa 1 lan)
    Route::post('bulk-delete', [SubjectController::class, 'bulkDestroy'])
        ->middleware('permission:subjects.delete')
        ->name('subjects.bulk-delete');

    // Export to CSV
    Route::get('export', [SubjectController::class, 'export'])
        ->middleware('permission:subjects.index')
        ->name('subjects.export');

    // Import Excel (declare before {subject} resource conflicts where possible)
    Route::get('import/template', [SubjectController::class, 'downloadTemplate'])
        ->middleware('permission:subjects.create')
        ->name('subjects.import.template');
    Route::post('import', [SubjectController::class, 'importExcel'])
        ->middleware('permission:subjects.create')
        ->name('subjects.import');

    // Chi tiết môn học (bài học) — import theo file mẫu
    Route::get('lessons/import/template', [SubjectController::class, 'downloadLessonsTemplate'])
        ->middleware('permission:subject-lessons.create')
        ->name('subjects.lessons.import.template');
    Route::post('lessons/import', [SubjectController::class, 'importLessons'])
        ->middleware('permission:subject-lessons.create')
        ->name('subjects.lessons.import');

    // API endpoints for AJAX requests
    Route::prefix('api')->middleware('permission:subjects.index')->group(function () {

        // Get subjects list for select2/autocomplete
        Route::get('list', function () {
            $query = request('q');
            $specializationId = request('specialization_id');

            $subjectsQuery = Subject::active()
                ->with('specialization')
                ->when($specializationId, function ($q) use ($specializationId) {
                    $q->bySpecialization($specializationId);
                })
                ->when($query, function ($q) use ($query) {
                    $q->search($query);
                })
                ->select('id', 'name', 'code', 'credits', 'specialization_id', 'is_special_activity')
                ->limit(20);
            TrainingDept::applySubjectFacultyScope($subjectsQuery);
            $subjects = $subjectsQuery->get();

            return response()->json([
                'results' => $subjects->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->name.' ('.$item->display_code.') - '.$item->credits.' TC',
                        'data' => $item,
                        'display_code' => $item->display_code,
                    ];
                }),
            ]);
        })->name('subjects.api.list');

        // Get subjects by specialization
        Route::get('by-specialization', [SubjectController::class, 'getBySpecialization'])
            ->name('subjects.api.by-specialization');

        // Get subject details
        Route::get('{subject}', function (Subject $subject) {
            TrainingScheduleAccess::ensureSubjectInScope($subject);

            return response()->json([
                'success' => true,
                'data' => $subject->load(['specialization', 'creator', 'updater']),
            ]);
        })->name('subjects.api.show');

        // Check if code exists (for validation)
        Route::post('check-code', function () {
            $code = request('code');
            $id = request('id');

            $exists = Subject::where('code', $code)
                ->when($id, function ($q) use ($id) {
                    $q->where('id', '!=', $id);
                })
                ->exists();

            return response()->json([
                'exists' => $exists,
                'available' => ! $exists,
            ]);
        })->name('subjects.api.check-code');

    });

});
