<?php

use Illuminate\Support\Facades\Route;
use Modules\ScientificResearch\Controllers\ScientificResearchController;

Route::middleware(['web', 'auth'])
    ->prefix('nghien-cuu-khoa-hoc')
    ->name('scientific-research.')
    ->group(function (): void {
        Route::get('/', [ScientificResearchController::class, 'index'])->middleware('permission:scientific-research.index')->name('index');
        Route::get('/cong-thong-tin', [ScientificResearchController::class, 'portal'])->middleware('permission:scientific-research.portal|scientific-research.index')->name('portal');
        Route::get('/thong-bao', [ScientificResearchController::class, 'announcements'])->middleware('permission:scientific-research.announcements.index')->name('announcements.index');
        Route::get('/de-tai', [ScientificResearchController::class, 'topics'])->middleware('permission:scientific-research.registrations.index')->name('registrations.index');
        Route::get('/tham-dinh-de-tai', [ScientificResearchController::class, 'topics'])->middleware('permission:scientific-research.registrations.status|scientific-research.registrations.unit-approve|scientific-research.registrations.agency-approve|scientific-research.registrations.return')->defaults('review_queue', true)->name('registrations.review');
        Route::get('/tham-dinh-de-tai/{registration}', [ScientificResearchController::class, 'showRegistration'])->middleware('permission:scientific-research.registrations.status|scientific-research.registrations.unit-approve|scientific-research.registrations.agency-approve|scientific-research.registrations.return')->defaults('review_mode', true)->name('registrations.review.show');
        Route::get('/bo-sung-gia-han', [ScientificResearchController::class, 'registrationRequests'])->middleware('permission:scientific-research.registrations.submit|scientific-research.registrations.edit')->name('registrations.requests');
        Route::get('/ket-qua', [ScientificResearchController::class, 'results'])->middleware('permission:scientific-research.results.index')->name('results.index');
        Route::get('/bao-cao', [ScientificResearchController::class, 'reports'])->middleware('permission:scientific-research.reports.view')->name('reports.index');
        Route::get('/bao-cao/csv', [ScientificResearchController::class, 'exportReportCsv'])->middleware('permission:scientific-research.reports.export')->name('reports.csv');
        Route::get('/bao-cao/excel', [ScientificResearchController::class, 'exportReportExcel'])->middleware('permission:scientific-research.reports.export')->name('reports.excel');
        Route::get('/bao-cao/word', [ScientificResearchController::class, 'exportReportWord'])->middleware('permission:scientific-research.reports.export')->name('reports.word');
        Route::get('/tep-dinh-kem/{file}/tai-ve', [ScientificResearchController::class, 'downloadFile'])->middleware('permission:scientific-research.registrations.show|scientific-research.registrations.index|scientific-research.results.show|scientific-research.results.index')->name('files.download');
        Route::get('/nhat-ky', [ScientificResearchController::class, 'auditLogs'])->middleware('permission:scientific-research.audit.index')->name('audit.index');
        Route::get('/can-bo', [ScientificResearchController::class, 'staff'])->middleware('permission:scientific-research.staff.index')->name('staff.index');
        Route::get('/ke-hoach', [ScientificResearchController::class, 'plans'])->middleware('permission:scientific-research.plans.index')->name('plans.index');
        Route::get('/hoi-dong', [ScientificResearchController::class, 'councils'])->middleware('permission:scientific-research.councils.index')->name('councils.index');
        Route::get('/kinh-phi', [ScientificResearchController::class, 'funding'])->middleware('permission:scientific-research.funding.index')->name('funding.index');
        Route::get('/san-pham', [ScientificResearchController::class, 'products'])->middleware('permission:scientific-research.products.index')->name('products.index');
        Route::get('/kho-du-lieu', [ScientificResearchController::class, 'repository'])->middleware('permission:scientific-research.repository.index')->name('repository.index');
        Route::post('/thong-bao', [ScientificResearchController::class, 'storeAnnouncement'])->middleware('permission:scientific-research.announcements.create')->name('announcements.store');
        Route::post('/can-bo', [ScientificResearchController::class, 'storeStaff'])->middleware('permission:scientific-research.staff.create')->name('staff.store');
        Route::post('/ke-hoach', [ScientificResearchController::class, 'storePlan'])->middleware('permission:scientific-research.plans.create')->name('plans.store');
        Route::post('/hoi-dong', [ScientificResearchController::class, 'storeCouncil'])->middleware('permission:scientific-research.councils.create')->name('councils.store');
        Route::post('/hoi-dong/{council}/thanh-vien', [ScientificResearchController::class, 'storeCouncilMember'])->middleware('permission:scientific-research.councils.create')->name('councils.members.store');
        Route::post('/kinh-phi', [ScientificResearchController::class, 'storeFunding'])->middleware('permission:scientific-research.funding.create')->name('funding.store');
        Route::post('/san-pham', [ScientificResearchController::class, 'storeProduct'])->middleware('permission:scientific-research.products.create')->name('products.store');
        Route::post('/kho-du-lieu', [ScientificResearchController::class, 'storeRepositoryDocument'])->middleware('permission:scientific-research.repository.create')->name('repository.store');
        Route::patch('/thong-bao/{announcement}', [ScientificResearchController::class, 'updateAnnouncement'])->middleware('permission:scientific-research.announcements.edit')->name('announcements.update');
        Route::delete('/thong-bao/{announcement}', [ScientificResearchController::class, 'destroyAnnouncement'])->middleware('permission:scientific-research.announcements.delete')->name('announcements.destroy');
        Route::patch('/can-bo/{staffProfile}', [ScientificResearchController::class, 'updateStaff'])->middleware('permission:scientific-research.staff.edit')->name('staff.update');
        Route::delete('/can-bo/{staffProfile}', [ScientificResearchController::class, 'destroyStaff'])->middleware('permission:scientific-research.staff.delete')->name('staff.destroy');
        Route::patch('/ke-hoach/{plan}', [ScientificResearchController::class, 'updatePlan'])->middleware('permission:scientific-research.plans.edit')->name('plans.update');
        Route::patch('/ke-hoach/{plan}/nhiem-vu/{taskKey}', [ScientificResearchController::class, 'updatePlanTaskProgress'])->middleware('permission:scientific-research.plans.index|scientific-research.plans.edit')->name('plans.tasks.update');
        Route::delete('/ke-hoach/{plan}', [ScientificResearchController::class, 'destroyPlan'])->middleware('permission:scientific-research.plans.delete')->name('plans.destroy');
        Route::patch('/hoi-dong/{council}', [ScientificResearchController::class, 'updateCouncil'])->middleware('permission:scientific-research.councils.edit')->name('councils.update');
        Route::delete('/hoi-dong/{council}', [ScientificResearchController::class, 'destroyCouncil'])->middleware('permission:scientific-research.councils.delete')->name('councils.destroy');
        Route::delete('/hoi-dong-thanh-vien/{member}', [ScientificResearchController::class, 'destroyCouncilMember'])->middleware('permission:scientific-research.councils.delete')->name('councils.members.destroy');
        Route::patch('/kinh-phi/{funding}', [ScientificResearchController::class, 'updateFunding'])->middleware('permission:scientific-research.funding.edit')->name('funding.update');
        Route::delete('/kinh-phi/{funding}', [ScientificResearchController::class, 'destroyFunding'])->middleware('permission:scientific-research.funding.delete')->name('funding.destroy');
        Route::patch('/san-pham/{product}', [ScientificResearchController::class, 'updateProduct'])->middleware('permission:scientific-research.products.edit')->name('products.update');
        Route::delete('/san-pham/{product}', [ScientificResearchController::class, 'destroyProduct'])->middleware('permission:scientific-research.products.delete')->name('products.destroy');
        Route::patch('/kho-du-lieu/{document}', [ScientificResearchController::class, 'updateRepositoryDocument'])->middleware('permission:scientific-research.repository.edit')->name('repository.update');
        Route::delete('/kho-du-lieu/{document}', [ScientificResearchController::class, 'destroyRepositoryDocument'])->middleware('permission:scientific-research.repository.delete')->name('repository.destroy');

        Route::get('/dang-ky', [ScientificResearchController::class, 'createRegistration'])->middleware('permission:scientific-research.registrations.create')->name('registrations.create');
        Route::post('/dang-ky', [ScientificResearchController::class, 'storeRegistration'])->middleware('permission:scientific-research.registrations.create|scientific-research.registrations.submit')->name('registrations.store');
        Route::get('/dang-ky/{registration}', [ScientificResearchController::class, 'showRegistration'])->middleware('permission:scientific-research.registrations.show|scientific-research.registrations.index')->name('registrations.show');
        Route::patch('/dang-ky/{registration}', [ScientificResearchController::class, 'updateRegistration'])->middleware('permission:scientific-research.registrations.edit|scientific-research.registrations.submit')->name('registrations.update');
        Route::delete('/dang-ky/{registration}', [ScientificResearchController::class, 'destroyRegistration'])->middleware('permission:scientific-research.registrations.delete')->name('registrations.destroy');
        Route::patch('/dang-ky/{registration}/trang-thai', [ScientificResearchController::class, 'updateRegistrationStatus'])->middleware('permission:scientific-research.registrations.status|scientific-research.registrations.unit-approve|scientific-research.registrations.agency-approve|scientific-research.registrations.return')->name('registrations.status');
        Route::post('/dang-ky/{registration}/gui-bo-sung', [ScientificResearchController::class, 'submitRegistrationRevision'])->middleware('permission:scientific-research.registrations.edit|scientific-research.registrations.submit')->name('registrations.revision.submit');
        Route::post('/dang-ky/{registration}/xin-gia-han', [ScientificResearchController::class, 'requestRegistrationExtension'])->middleware('permission:scientific-research.registrations.edit|scientific-research.registrations.submit')->name('registrations.extension.request');
        Route::patch('/dang-ky/{registration}/duyet-gia-han', [ScientificResearchController::class, 'reviewRegistrationExtension'])->middleware('permission:scientific-research.registrations.status|scientific-research.registrations.agency-approve')->name('registrations.extension.review');
        Route::patch('/gia-han/{extension}', [ScientificResearchController::class, 'updateRegistrationExtension'])->middleware('permission:scientific-research.registrations.edit|scientific-research.registrations.submit|scientific-research.registrations.status|scientific-research.registrations.agency-approve')->name('registrations.extension.update');
        Route::delete('/gia-han/{extension}', [ScientificResearchController::class, 'destroyRegistrationExtension'])->middleware('permission:scientific-research.registrations.edit|scientific-research.registrations.submit|scientific-research.registrations.status|scientific-research.registrations.agency-approve')->name('registrations.extension.destroy');
        Route::post('/dang-ky/{registration}/dong-bo-gio-chuan', [ScientificResearchController::class, 'syncRegistrationToStandardHours'])->middleware('permission:scientific-research.registrations.sync-standard-hours')->name('registrations.sync-standard-hours');
        Route::post('/dang-ky/{registration}/ket-qua', [ScientificResearchController::class, 'storeResult'])->middleware('permission:scientific-research.results.create|scientific-research.results.submit')->name('results.store');
        Route::patch('/ket-qua/{result}', [ScientificResearchController::class, 'updateResult'])->middleware('permission:scientific-research.results.edit|scientific-research.results.approve')->name('results.update');
        Route::delete('/ket-qua/{result}', [ScientificResearchController::class, 'destroyResult'])->middleware('permission:scientific-research.results.delete')->name('results.destroy');
    });
