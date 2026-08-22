<?php

use Illuminate\Support\Facades\Route;
use Modules\Specialization\Controllers\CurriculumHubController;
use Modules\Specialization\Controllers\SpecializationController;
use Modules\Specialization\Controllers\TrainingSystemController;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Controllers\SubjectController;

/*
|--------------------------------------------------------------------------
| Specialization Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Specialization module.
| These routes are loaded by the SpecializationServiceProvider within a
| group which contains the "web" middleware group and "auth" middleware.
|
*/

// Hub Ngành đào tạo: Hệ đào tạo → Ngành → Môn học → Bài học.
// Khai báo trước resource để `/{specialization}` không bắt nhầm "hub".
Route::get('hub', [CurriculumHubController::class, 'index'])->name('specializations.hub');

// Hệ đào tạo (Dân sự / Quân sự / …) — khai báo trước resource
Route::get('training-systems', [TrainingSystemController::class, 'index'])->name('training-systems.index');
Route::post('training-systems', [TrainingSystemController::class, 'store'])->name('training-systems.store');
Route::put('training-systems/{trainingSystem}', [TrainingSystemController::class, 'update'])->name('training-systems.update');
Route::delete('training-systems/{trainingSystem}', [TrainingSystemController::class, 'destroy'])->name('training-systems.destroy');

// Khai báo route tĩnh trước resource để không bị /{specialization} bắt nhầm.
Route::get('export', [SpecializationController::class, 'export'])
    ->name('specializations.export');

// Specialization resource routes
Route::resource('/', SpecializationController::class, [
    'names' => [
        'index' => 'specializations.index',
        'create' => 'specializations.create',
        'store' => 'specializations.store',
        'show' => 'specializations.show',
        'edit' => 'specializations.edit',
        'update' => 'specializations.update',
        'destroy' => 'specializations.destroy',
    ],
    'parameters' => [
        '' => 'specialization',
    ],
]);

// Additional routes
Route::prefix('/')->group(function () {

    // Toggle status
    Route::patch('{specialization}/toggle-status', [SpecializationController::class, 'toggleStatus'])
        ->name('specializations.toggle-status');

    // Restore soft deleted
    Route::patch('{id}/restore', [SpecializationController::class, 'restore'])
        ->name('specializations.restore')
        ->where('id', '[0-9]+');

    // Import subjects for a specialization (JSON rows from client-side Excel parse)
    Route::post('{specialization}/subjects/import', [SubjectController::class, 'import'])
        ->name('specializations.subjects.import');

    // API endpoints for AJAX requests
    Route::prefix('api')->group(function () {

        // Get specializations list for select2/autocomplete (optional filter by Hệ)
        Route::get('list', function () {
            $query = request('q');
            $systemId = request('training_system_id');
            $specializations = Specialization::active()
                ->with('trainingSystem:id,name')
                ->when($query, function ($q) use ($query) {
                    $q->search($query);
                })
                ->when($systemId, fn ($q) => $q->where('training_system_id', $systemId))
                ->select('id', 'name', 'code', 'major_code', 'level', 'training_form', 'training_system_id')
                ->limit(50)
                ->get();

            return response()->json([
                'results' => $specializations->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->selection_label,
                        'training_system_id' => $item->training_system_id,
                        'data' => $item,
                    ];
                }),
            ]);
        })->name('specializations.api.list');

        // Training systems for cascade filters
        Route::get('training-systems', function () {
            $items = TrainingSystem::query()
                ->active()
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'results' => $items->map(fn ($s) => [
                    'id' => $s->id,
                    'text' => $s->name,
                    'code' => $s->code,
                ]),
            ]);
        })->name('specializations.api.training-systems');

        // Get specialization details
        Route::get('{specialization}', function (Specialization $specialization) {
            return response()->json([
                'success' => true,
                'data' => $specialization->load(['creator', 'updater']),
            ]);
        })->name('specializations.api.show');

        // Check if code exists (for validation)
        Route::post('check-code', function () {
            $code = request('code');
            $id = request('id');

            $exists = Specialization::where('code', $code)
                ->when($id, function ($q) use ($id) {
                    $q->where('id', '!=', $id);
                })
                ->exists();

            return response()->json([
                'exists' => $exists,
                'available' => ! $exists,
            ]);
        })->name('specializations.api.check-code');

    });

});
