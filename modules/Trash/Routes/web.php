<?php

use Illuminate\Support\Facades\Route;
use Modules\Trash\Controllers\TrashController;

Route::get('/', [TrashController::class, 'index'])->name('trash.index');
Route::post('/bulk-restore', [TrashController::class, 'bulkRestore'])->name('trash.bulk-restore');
Route::delete('/bulk-force-delete', [TrashController::class, 'bulkForceDelete'])->name('trash.bulk-force-delete');
Route::get('/{module}/{id}', [TrashController::class, 'show'])
    ->whereNumber('id')
    ->name('trash.show');
Route::post('/{module}/{id}/restore', [TrashController::class, 'restore'])
    ->whereNumber('id')
    ->name('trash.restore');
Route::delete('/{module}/{id}', [TrashController::class, 'forceDelete'])
    ->whereNumber('id')
    ->name('trash.force-delete');
