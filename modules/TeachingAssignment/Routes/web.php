<?php

use App\Support\ManagerUnitScope;
use App\Support\TrainingDept;
use Illuminate\Support\Facades\Route;
use Modules\Instructor\Models\Instructor;
use Modules\Subject\Models\Subject;
use Modules\TeachingAssignment\Controllers\TeachingAssignmentController;

/*
|--------------------------------------------------------------------------
| Teaching Assignment Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('teaching-assignments')
    ->name('teaching-assignments.')
    ->group(function () {

        // === API endpoints for AJAX ===
        Route::prefix('api')->middleware('permission:teaching-assignments.index')->name('api.')->group(function () {
            Route::get('instructors-by-unit/{unit}', function ($unitId) {
                abort_unless(ManagerUnitScope::canAccessUnit((int) $unitId), 403);

                return Instructor::where('unit_id', $unitId)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get();
            })->name('instructors');

            Route::get('subjects-by-specialization/{specialization}', function ($specializationId) {
                $query = Subject::where('specialization_id', $specializationId)
                    ->select('id', 'name')
                    ->orderBy('name');
                TrainingDept::applySubjectFacultyScope($query);

                return $query->get();
            })->name('subjects');
        });

        // === Resource routes ===
        Route::resource('/', TeachingAssignmentController::class, [
            'names' => [
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'destroy' => 'destroy',
            ],
            'parameters' => [
                '' => 'instructor',
            ],
        ]);
    });
