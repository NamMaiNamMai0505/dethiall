<?php

use Illuminate\Support\Facades\Route;
use Modules\ExportTemplates\Controllers\ExportTemplateController;
use Modules\ExportTemplates\Controllers\TemplateBuilderController;

Route::middleware(['web', 'auth'])->group(function () {
    // Legacy URL → redirect portal
    Route::get('/export-templates', [ExportTemplateController::class, 'legacyIndex'])->name('export-templates.index');

    // Portal-scoped: /export-templates/{dashboard|lms|grades}
    Route::prefix('export-templates/{portal}')
        ->whereIn('portal', ['dashboard', 'lms', 'grades'])
        ->name('export-templates.portal.')
        ->group(function () {
            Route::get('/', [ExportTemplateController::class, 'index'])->name('index');
            Route::get('/create', [ExportTemplateController::class, 'create'])->name('create');
            Route::get('/builder/create', [TemplateBuilderController::class, 'create'])->name('builder.create');
            Route::post('/builder', [TemplateBuilderController::class, 'store'])->name('builder.store');
            Route::get('/builder/{version}', [TemplateBuilderController::class, 'edit'])->name('builder.edit');
            Route::put('/builder/{version}', [TemplateBuilderController::class, 'update'])->name('builder.update');
            Route::post('/builder/{version}/version', [TemplateBuilderController::class, 'newVersion'])->name('builder.version');
            Route::get('/variables/{featureKey}', [ExportTemplateController::class, 'variables'])
                ->where('featureKey', '.*')
                ->name('variables');
            Route::post('/', [ExportTemplateController::class, 'store'])->name('store');
            Route::post('/{exportTemplate}/clone', [ExportTemplateController::class, 'clone'])->name('clone');
            Route::get(
                '/{exportTemplate}/test-export',
                [ExportTemplateController::class, 'testExport']
            )->name('test-export');
            Route::post('/{exportTemplate}/versions', [ExportTemplateController::class, 'createVersion'])->name('versions.store');
            Route::post(
                '/{exportTemplate}/versions/{version}/activate',
                [ExportTemplateController::class, 'activate']
            )->name('versions.activate');
            Route::get(
                '/{exportTemplate}/versions/{version}/download',
                [ExportTemplateController::class, 'download']
            )->name('versions.download');
            Route::post(
                '/{exportTemplate}/versions/{version}/analyze',
                [ExportTemplateController::class, 'analyze']
            )->name('versions.analyze');
            Route::get(
                '/{exportTemplate}/versions/{version}/preview',
                [ExportTemplateController::class, 'preview']
            )->name('versions.preview');
            Route::get(
                '/{exportTemplate}/versions/{version}/bindings',
                [ExportTemplateController::class, 'bindingsEditor']
            )->name('versions.bindings.index');
            Route::post(
                '/{exportTemplate}/versions/{version}/bindings',
                [ExportTemplateController::class, 'storeBinding']
            )->name('versions.bindings.store');
            Route::delete(
                '/{exportTemplate}/versions/{version}/bindings/{binding}',
                [ExportTemplateController::class, 'destroyBinding']
            )->name('versions.bindings.destroy');
            Route::get('/{exportTemplate}', [ExportTemplateController::class, 'show'])->name('show');
            Route::delete('/{exportTemplate}', [ExportTemplateController::class, 'destroy'])->name('destroy');
        });
});
