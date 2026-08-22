<?php

use App\Support\TrainingScheduleAccess;
use Illuminate\Support\Facades\Route;
use Modules\TrainingSchedule\Controllers\HubController;
use Modules\TrainingSchedule\Controllers\TrainingScheduleController;
use Modules\TrainingSchedule\Models\TrainingSchedule;

/*
|--------------------------------------------------------------------------
| Training Schedule Module Routes
|--------------------------------------------------------------------------
*/

// Get all schedule details by class_id, date, and optional filters
Route::get('schedule-details', [TrainingScheduleController::class, 'getAllScheduleDetails'])
    ->name('training-schedules.api.schedule-details');

// Get classes by specialization for dropdown
Route::get('classes', [TrainingScheduleController::class, 'getClasses'])
    ->name('training-schedules.api.classes');

// Get filtered training schedules
Route::get('api/filtered', [TrainingScheduleController::class, 'getFilteredTrainingSchedules'])
    ->name('training-schedules.api.filtered');

// Calendar view (must be before resource routes to avoid conflict)
Route::get('/calendar', [TrainingScheduleController::class, 'calendar'])
    ->name('training-schedules.calendar');

// Export
Route::get('export', [TrainingScheduleController::class, 'export'])
    ->name('training-schedules.export');

// Export schedule details to Excel
Route::post('export-schedule-details', [TrainingScheduleController::class, 'exportScheduleDetails'])
    ->name('training-schedules.export-schedule-details');

// Xuất lịch huấn luyện (PDOT — ưu tiên template Active, fallback lưới tuần)
Route::post('export-training-plan', [TrainingScheduleController::class, 'exportTrainingPlan'])
    ->name('training-schedules.export-training-plan');

// Xuất kế hoạch huấn luyện (Khoa — theo lớp, cột ngày chỉ dd)
Route::post('export-faculty-plan', [TrainingScheduleController::class, 'exportFacultyPlan'])
    ->name('training-schedules.export-faculty-plan');

// Hub menu (trang chủ module)
Route::get('/', [HubController::class, 'index'])->name('training-schedules.hub');

// Danh sách lịch (trước đây là index)
Route::get('list', [TrainingScheduleController::class, 'index'])->name('training-schedules.list');
// Giữ alias index trỏ hub để sidebar / link cũ không gãy
Route::get('index', [HubController::class, 'index'])->name('training-schedules.index');

// Training Schedule resource (trừ index — đã tách hub/list)
Route::resource('/', TrainingScheduleController::class, [
    'names' => [
        'create' => 'training-schedules.create',
        'store' => 'training-schedules.store',
        'show' => 'training-schedules.show',
        'edit' => 'training-schedules.edit',
        'update' => 'training-schedules.update',
        'destroy' => 'training-schedules.destroy',
    ],
    'parameters' => [
        '' => 'trainingSchedule',
    ],
    'except' => ['index'],
]);

// Additional routes
Route::prefix('/')->group(function () {

    // Toggle active status
    Route::patch('{trainingSchedule}/toggle-status', [TrainingScheduleController::class, 'toggleStatus'])
        ->name('training-schedules.toggle-status');

    // get subject hour usage for specific training schedule
    Route::get('{trainingSchedule}/subject-hour-usage', [TrainingScheduleController::class, 'getSubjectHourUsage'])
        ->name('training-schedules.subject-hour-usage');

    // Restore soft deleted
    Route::patch('{id}/restore', [TrainingScheduleController::class, 'restore'])
        ->name('training-schedules.restore')
        ->where('id', '[0-9]+');

    // API endpoints for AJAX requests
    Route::prefix('api')->middleware('permission:training-schedules.index')->group(function () {

        // Get training schedules list for select2/autocomplete
        Route::get('list', function () {
            TrainingScheduleAccess::ensureValidRoleConfiguration();
            $query = request('q');
            $schedules = TrainingSchedule::active()
                ->when($query, function ($q) use ($query) {
                    $q->search($query);
                })
                ->with('instructors:id,name')
                ->select('id', 'name', 'code', 'start_date', 'end_date', 'is_active')
                ->limit(20)
                ->get();

            return response()->json([
                'results' => $schedules->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->name.' ('.$item->code.')',
                        'data' => [
                            'code' => $item->code,
                            'start_date' => $item->start_date ? $item->start_date->format('d/m/Y') : null,
                            'end_date' => $item->end_date ? $item->end_date->format('d/m/Y') : null,
                            'status' => $item->is_active ? 'Đang hoạt động' : 'Tạm ngừng',
                            'instructor' => $item->instructors->pluck('name')->join(', ') ?: 'Chưa phân công',
                        ],
                    ];
                }),
            ]);
        })->name('training-schedules.api.list');

        // Get training schedule details
        Route::get('{trainingSchedule}', function (TrainingSchedule $trainingSchedule) {
            TrainingScheduleAccess::ensureValidRoleConfiguration();

            return response()->json([
                'success' => true,
                'data' => $trainingSchedule->load([
                    'specialization',
                    'class',
                    'classroom',
                    'instructors:id,name',
                    'creator',
                    'updater',
                ]),
            ]);
        })->whereNumber('trainingSchedule')->name('training-schedules.api.show');

        // Check if code exists (for validation)
        Route::post('check-code', function () {
            TrainingScheduleAccess::ensureValidRoleConfiguration();
            $code = request('code');
            $id = request('id');

            $exists = TrainingSchedule::where('code', $code)
                ->when($id, function ($q) use ($id) {
                    $q->where('id', '!=', $id);
                })
                ->exists();

            return response()->json([
                'exists' => $exists,
                'available' => ! $exists,
            ]);
        })->name('training-schedules.api.check-code');

        // Get calendar events
        Route::get('calendar-events', function () {
            TrainingScheduleAccess::ensureValidRoleConfiguration();
            $start = request('start');
            $end = request('end');

            $schedules = TrainingSchedule::with(['instructors:id,name', 'classroom:id,name'])
                ->where('is_active', true)
                ->when($start && $end, function ($q) use ($start, $end) {
                    $q->dateRange($start, $end);
                })
                ->get();

            $events = $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->name,
                    'start' => $schedule->start_date->format('Y-m-d'),
                    'end' => $schedule->end_date ? $schedule->end_date->copy()->addDay()->format('Y-m-d') : null,
                    'url' => route('training-schedules.show', $schedule->id),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                    'extendedProps' => [
                        'instructor' => $schedule->instructors->pluck('name')->join(', ') ?: 'Chưa phân công',
                        'location' => $schedule->classroom->name ?? null,
                        'classroom' => $schedule->classroom->name ?? null,
                        'classroom_id' => $schedule->classroom_id,
                        'status' => 'Đang hoạt động',
                        'time_range' => $schedule->start_date?->format('d/m/Y').' – '.$schedule->end_date?->format('d/m/Y'),
                    ],
                ];
            });

            return response()->json($events);
        })->name('training-schedules.api.calendar-events');

        // Get dashboard statistics
        Route::get('stats', function () {
            TrainingScheduleAccess::ensureValidRoleConfiguration();
            $total = TrainingSchedule::count();
            $active = TrainingSchedule::active()->count();
            $ongoing = TrainingSchedule::active()
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count();
            $scheduled = TrainingSchedule::active()
                ->whereDate('start_date', '>', today())
                ->count();
            $thisMonth = TrainingSchedule::whereMonth('created_at', now()->month)->count();

            return response()->json([
                'total' => $total,
                'active' => $active,
                'ongoing' => $ongoing,
                'scheduled' => $scheduled,
                'this_month' => $thisMonth,
            ]);
        })->name('training-schedules.api.stats');

    });

});

// Helper function for status colors
if (! function_exists('getStatusColor')) {
    function getStatusColor($status)
    {
        $colors = [
            'draft' => '#6B7280',      // gray
            'scheduled' => '#3B82F6',  // blue
            'ongoing' => '#10B981',    // green
            'completed' => '#059669',  // emerald
            'cancelled' => '#EF4444',  // red
            'postponed' => '#F59E0B',   // yellow
        ];

        return $colors[$status] ?? '#6B7280';
    }
}

// Schedule Detail routes
Route::prefix('{trainingSchedule}')->group(function () {
    // Smart navigate to create or edit based on date data
    Route::get('schedule-details/navigate', [TrainingScheduleController::class, 'navigateScheduleDetail'])
        ->name('training-schedules.schedule-details.navigate');

    // Create schedule detail for a specific date
    Route::get('schedule-details/create', [TrainingScheduleController::class, 'createScheduleDetail'])
        ->name('training-schedules.schedule-details.create');

    // Edit schedule detail for a specific date
    Route::get('schedule-details/{date}/edit', [TrainingScheduleController::class, 'editScheduleDetail'])
        ->name('training-schedules.schedule-details.edit');

    // Store schedule details (array 7 record)
    Route::post('schedule-details', [TrainingScheduleController::class, 'storeScheduleDetail'])
        ->name('training-schedules.schedule-details.store');

    // Update schedule details (array 7 record)
    Route::put('schedule-details/{date}', [TrainingScheduleController::class, 'updateScheduleDetail'])
        ->name('training-schedules.schedule-details.update');

    Route::delete('schedule-details/{date}', [TrainingScheduleController::class, 'destroyScheduleDetail'])
        ->name('training-schedules.schedule-details.destroy');
});
