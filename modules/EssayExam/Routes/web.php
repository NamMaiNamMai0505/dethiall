<?php

use Illuminate\Support\Facades\Route;
use Modules\EssayExam\Controllers\EssayExamController;

Route::middleware(['web','auth'])->prefix('essay-exams')->name('essay-exams.')->group(function () {
    Route::get('/', [EssayExamController::class, 'index'])->middleware('permission:essay-exams.index')->name('index');
    Route::get('/mine', [EssayExamController::class, 'mine'])->middleware('permission:essay-exams.index')->name('mine');
    Route::get('/create', [EssayExamController::class, 'create'])->middleware('permission:essay-exams.authoring.index')->name('create');
    Route::post('/import', [EssayExamController::class, 'import'])->middleware('permission:essay-exams.import.create')->name('import');
    Route::post('/import/preview', [EssayExamController::class, 'previewImport'])->middleware('permission:essay-exams.import.create')->name('import.preview');
    Route::post('/import/confirm', [EssayExamController::class, 'confirmImport'])->middleware('permission:essay-exams.import.create')->name('import.confirm');
    Route::get('/integrated-answers', [EssayExamController::class, 'integratedAnswers'])->middleware('permission:essay-exams.index')->name('integrated-answers.index');
    Route::get('/integrated-answers/{answerSet}', [EssayExamController::class, 'integratedAnswerShow'])->middleware('permission:essay-exams.index')->name('integrated-answers.show');
    Route::post('/integrated-answers/{answerSet}/submit', [EssayExamController::class, 'integratedAnswerSubmit'])->middleware('permission:essay-exams.submission.create')->name('integrated-answers.submit');
    Route::post('/integrated-answers/{answerSet}/approve', [EssayExamController::class, 'integratedAnswerApprove'])->middleware('permission:essay-exams.approval.approve')->name('integrated-answers.approve');
    Route::post('/integrated-answers/{answerSet}/return', [EssayExamController::class, 'integratedAnswerReturn'])->middleware('permission:essay-exams.approval.approve')->name('integrated-answers.return');
    Route::post('/', [EssayExamController::class, 'store'])->middleware('permission:essay-exams.authoring.create')->name('store');
    Route::get('/approval', [EssayExamController::class, 'approval'])->middleware('permission:essay-exams.approval.index')->name('approval');
    Route::post('/approval/lms-banks/{bank}/approve', [EssayExamController::class, 'approveLmsBank'])->middleware('permission:essay-exams.approval.approve')->name('approval.lms-banks.approve');
    Route::post('/approval/lms-banks/bulk-approve', [EssayExamController::class, 'approveLmsBanksBulk'])->middleware('permission:essay-exams.approval.approve')->name('approval.lms-banks.bulk-approve');
    Route::get('/bank', [EssayExamController::class, 'bank'])->middleware('permission:essay-exams.bank.index')->name('bank');
    Route::get('/used', [EssayExamController::class, 'used'])->middleware('permission:essay-exams.bank.index')->name('used');
    Route::get('/draw', [EssayExamController::class, 'draw'])->middleware('permission:essay-exams.draw.index')->name('draw');
    Route::get('/draw/minutes', [EssayExamController::class, 'minutes'])->middleware('permission:essay-exams.draw.index')->name('draw.minutes');
    Route::post('/draw', [EssayExamController::class, 'drawStore'])->middleware('permission:essay-exams.draw.create')->name('draw.store');
    Route::get('/draw/{draw}/print', [EssayExamController::class, 'printDraw'])->middleware('permission:essay-exams.draw.export')->name('draw.print');
    Route::get('/{essayExam}', [EssayExamController::class, 'show'])->middleware('permission:essay-exams.index')->name('show');
    Route::post('/{essayExam}/submit', [EssayExamController::class, 'submit'])->middleware('permission:essay-exams.submission.create')->name('submit');
    Route::post('/{essayExam}/approve', [EssayExamController::class, 'approve'])->middleware('permission:essay-exams.approval.approve')->name('approve');
    Route::post('/{essayExam}/return', [EssayExamController::class, 'returnExam'])->middleware('permission:essay-exams.approval.approve')->name('return');
});
