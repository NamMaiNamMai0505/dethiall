<?php

use App\Http\Middleware\EnsureApprovalAgencyRouteScope;
use Illuminate\Support\Facades\Route;
use Modules\StandardHours\Controllers\CalculationController;
use Modules\StandardHours\Controllers\ConversionCategoryController;
use Modules\StandardHours\Controllers\ConversionController;
use Modules\StandardHours\Controllers\DepartmentController;
use Modules\StandardHours\Controllers\DepartmentOvertimeController;
use Modules\StandardHours\Controllers\ExternalActivityController;
use Modules\StandardHours\Controllers\HourExchangeController;
use Modules\StandardHours\Controllers\HubController;
use Modules\StandardHours\Controllers\MyResultController;
use Modules\StandardHours\Controllers\NormReductionController;
use Modules\StandardHours\Controllers\ObjectTypeController;
use Modules\StandardHours\Controllers\PositionController;
use Modules\StandardHours\Controllers\ReportController;
use Modules\StandardHours\Controllers\ResearchCategoryController;
use Modules\StandardHours\Controllers\ResearchController;
use Modules\StandardHours\Controllers\SettingsController;

Route::middleware(['web', 'auth', EnsureApprovalAgencyRouteScope::class])
    ->prefix('standard-hours')
    ->name('standard-hours.')
    ->group(function () {
    Route::get('/', [HubController::class, 'index'])->name('hub');
    Route::get('guide', [HubController::class, 'guide'])->name('guide');

    Route::get('settings/research-rules', [SettingsController::class, 'editResearchRules'])
        ->name('settings.research-rules');
    Route::put('settings/research-rules', [SettingsController::class, 'updateResearchRules'])
        ->name('settings.research-rules.update');
    Route::get('settings/period-mode', [SettingsController::class, 'editPeriodMode'])
        ->name('settings.period-mode');
    Route::put('settings/period-mode', [SettingsController::class, 'updatePeriodMode'])
        ->name('settings.period-mode.update');

    Route::resource('object-types', ObjectTypeController::class)
        ->parameters(['object-types' => 'objectType']);

    Route::patch('object-types/{objectType}/toggle-status', [ObjectTypeController::class, 'toggleStatus'])
        ->name('object-types.toggle-status');

    Route::resource('positions', PositionController::class);

    Route::patch('positions/{position}/toggle-status', [PositionController::class, 'toggleStatus'])
        ->name('positions.toggle-status');

    // Bộ môn (thuộc khoa) — chủ yếu cho vượt DM giờ chuẩn
    Route::resource('departments', DepartmentController::class);
    Route::post('departments/{department}/instructors', [DepartmentController::class, 'syncInstructors'])
        ->name('departments.sync-instructors');

    // Điều 17 — vượt định mức theo bộ môn
    Route::get('department-overtime', [DepartmentOvertimeController::class, 'index'])
        ->name('department-overtime.index');
    Route::get('department-overtime/{department}', [DepartmentOvertimeController::class, 'show'])
        ->name('department-overtime.show');
    Route::post('department-overtime/{department}/calculate', [DepartmentOvertimeController::class, 'calculate'])
        ->name('department-overtime.calculate');
    Route::post('department-overtime/pools/{pool}/allocate', [DepartmentOvertimeController::class, 'saveAllocations'])
        ->name('department-overtime.allocate');
    Route::post('department-overtime/pools/{pool}/lock', [DepartmentOvertimeController::class, 'lock'])
        ->name('department-overtime.lock');

    // Điều 11.3 — giảm trừ định mức
    Route::get('norm-reductions', [NormReductionController::class, 'index'])->name('norm-reductions.index');
    Route::post('norm-reductions', [NormReductionController::class, 'store'])->name('norm-reductions.store');
    Route::delete('norm-reductions/{reduction}', [NormReductionController::class, 'destroy'])->name('norm-reductions.destroy');

    // hour-norms / research-norms: gộp vào Đối tượng — redirect tránh 404 bookmark cũ
    Route::any('hour-norms/{any?}', fn () => redirect()->route('standard-hours.object-types.index')
        ->with('info', 'Định mức giờ chuẩn đã chuyển vào mục Đối tượng.'))->where('any', '.*')
        ->name('legacy-hour-norms');
    Route::any('research-norms/{any?}', fn () => redirect()->route('standard-hours.object-types.index')
        ->with('info', 'Định mức NCKH đã chuyển vào mục Đối tượng.'))->where('any', '.*')
        ->name('legacy-research-norms');

    Route::resource('conversion-categories', ConversionCategoryController::class)
        ->parameters(['conversion-categories' => 'conversionCategory']);

    Route::patch('conversion-categories/{conversionCategory}/toggle-status', [ConversionCategoryController::class, 'toggleStatus'])
        ->name('conversion-categories.toggle-status');

    Route::resource('research-categories', ResearchCategoryController::class)
        ->parameters(['research-categories' => 'researchCategory']);

    Route::patch('research-categories/{researchCategory}/toggle-status', [ResearchCategoryController::class, 'toggleStatus'])
        ->name('research-categories.toggle-status');

    Route::resource('conversion-records', ConversionController::class)
        ->parameters(['conversion-records' => 'conversionRecord']);

    Route::patch('conversion-records/{conversionRecord}/submit', [ConversionController::class, 'submit'])
        ->name('conversion-records.submit');
    Route::patch('conversion-records/{conversionRecord}/approve', [ConversionController::class, 'approve'])
        ->name('conversion-records.approve');
    Route::patch('conversion-records/{conversionRecord}/reject', [ConversionController::class, 'reject'])
        ->name('conversion-records.reject');

    Route::resource('research-records', ResearchController::class)
        ->parameters(['research-records' => 'researchRecord']);

    Route::patch('research-records/{researchRecord}/submit', [ResearchController::class, 'submit'])
        ->name('research-records.submit');
    Route::patch('research-records/{researchRecord}/approve', [ResearchController::class, 'approve'])
        ->name('research-records.approve');
    Route::patch('research-records/{researchRecord}/reject', [ResearchController::class, 'reject'])
        ->name('research-records.reject');

    Route::resource('external-activities', ExternalActivityController::class)
        ->parameters(['external-activities' => 'externalActivity']);
    Route::patch('external-activities/{externalActivity}/submit', [ExternalActivityController::class, 'submit'])
        ->name('external-activities.submit');
    Route::patch('external-activities/{externalActivity}/approve', [ExternalActivityController::class, 'approve'])
        ->name('external-activities.approve');
    Route::patch('external-activities/{externalActivity}/reject', [ExternalActivityController::class, 'reject'])
        ->name('external-activities.reject');

    Route::get('calculations', [CalculationController::class, 'index'])->name('calculations.index');
    Route::get('calculations/{yearlyResult}', [CalculationController::class, 'show'])->name('calculations.show');
    Route::patch('calculations/{yearlyResult}/review-declaration', [CalculationController::class, 'reviewDeclaration'])
        ->name('calculations.review-declaration');
    Route::post('calculations/preview', [CalculationController::class, 'preview'])->name('calculations.preview');
    Route::post('calculations/run', [CalculationController::class, 'calculate'])->name('calculations.calculate');
    Route::patch('calculations/rollback', [CalculationController::class, 'rollback'])->name('calculations.rollback');
    Route::patch('calculations/lock', [CalculationController::class, 'lock'])->name('calculations.lock');

    Route::get('my-results', [MyResultController::class, 'index'])->name('my-results.index');
    Route::get('my-results/export', [MyResultController::class, 'export'])->name('my-results.export');
    Route::get('my-results/declaration/edit', [MyResultController::class, 'declaration'])
        ->name('my-results.declaration');
    Route::post('my-results/declaration/retrieve-schedule', [MyResultController::class, 'retrieveSchedule'])
        ->name('my-results.retrieve-schedule');
    Route::post('my-results/declaration', [MyResultController::class, 'storeDeclaration'])
        ->name('my-results.store-declaration');
    Route::get('my-results/{yearlyResult}', [MyResultController::class, 'show'])->name('my-results.show');

    Route::get('hour-exchanges', [HourExchangeController::class, 'index'])->name('hour-exchanges.index');
    Route::post('hour-exchanges', [HourExchangeController::class, 'store'])->name('hour-exchanges.store');
    Route::get('hour-exchanges/balances', [HourExchangeController::class, 'balances'])->name('hour-exchanges.balances');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/export-conversion', [ReportController::class, 'exportConversion'])->name('reports.export-conversion');
    Route::get('reports/export-research', [ReportController::class, 'exportResearch'])->name('reports.export-research');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('reports/{yearlyResult}', [ReportController::class, 'show'])->name('reports.show');
    });
