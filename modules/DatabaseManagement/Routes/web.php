<?php

use Illuminate\Support\Facades\Route;
use Modules\DatabaseManagement\Controllers\DatabaseManagementController;

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('database-management')
    ->name('database-management.')
    ->group(function (): void {
        Route::get('/', [DatabaseManagementController::class, 'index'])->name('index');
        Route::get('/map', [DatabaseManagementController::class, 'map'])->name('map');
        Route::get('/business', [DatabaseManagementController::class, 'business'])->name('business');
        Route::post('/business', [DatabaseManagementController::class, 'storeBusiness'])->name('business.store');
        Route::get('/data', [DatabaseManagementController::class, 'data'])->name('data');
        Route::patch('/data', [DatabaseManagementController::class, 'updateData'])->name('data.update');
        Route::get('/sql', [DatabaseManagementController::class, 'sql'])->name('sql');
        Route::post('/sql', [DatabaseManagementController::class, 'executeSql'])->name('sql.execute');
        Route::get('/migrations', [DatabaseManagementController::class, 'migrationDesigner'])->name('migrations');
        Route::post('/migrations/versions', [DatabaseManagementController::class, 'createMigrationVersion'])->name('migrations.versions.store');
        Route::post('/migrations/{version}/validate', [DatabaseManagementController::class, 'validateMigrationVersion'])->name('migrations.validate');
        Route::post('/migrations/{version}/reject', [DatabaseManagementController::class, 'rejectMigrationVersion'])->name('migrations.reject');
        Route::post('/migrations/{version}/publish', [DatabaseManagementController::class, 'publishMigrationVersion'])->name('migrations.publish');
        Route::post('/migrations/{version}/backup', [DatabaseManagementController::class, 'backupMigrationVersion'])->name('migrations.backup');
        Route::post('/migrations/{version}/rollback', [DatabaseManagementController::class, 'rollbackMigrationVersion'])->name('migrations.rollback');
        Route::post('/business/{map}/activate', [DatabaseManagementController::class, 'activateBusinessMap'])->name('business.activate');
        Route::get('/integrity', [DatabaseManagementController::class, 'integrity'])->name('integrity');
        Route::get('/audits', [DatabaseManagementController::class, 'audits'])->name('audits');
        Route::get('/schema', [DatabaseManagementController::class, 'schema'])->name('schema');
    });
