<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Controllers\InventoryController;
use Modules\Inventory\Controllers\InventoryMovementReportController;
use Modules\Inventory\Controllers\InventoryReportTemplateController;
use Modules\Inventory\Controllers\InventoryWorkflowController;

Route::middleware(['web', 'auth', 'permission:inventory.access.index'])
    ->prefix('vat-tu')
    ->name('inventory.')
    ->group(function (): void {
        Route::get('/cong', [InventoryController::class, 'portal'])
            ->middleware('permission:inventory.access.index')
            ->name('portal');
        Route::get('/', [InventoryController::class, 'index'])
            ->middleware('permission:inventory.access.index')
            ->name('index');

        Route::get('/danh-sach-vat-tu', [InventoryController::class, 'materials'])
            ->middleware('permission:inventory.materials.index')
            ->name('materials');
        Route::get('/mau-import', [InventoryController::class, 'importTemplate'])
            ->middleware('permission:inventory.materials.import')
            ->name('import.template');
        Route::post('/', [InventoryController::class, 'store'])
            ->middleware('permission:inventory.materials.create')
            ->name('store');
        Route::post('/nhap', [InventoryController::class, 'import'])
            ->middleware('permission:inventory.materials.import')
            ->name('import');
        Route::patch('/{material}', [InventoryController::class, 'update'])
            ->middleware('permission:inventory.materials.edit')
            ->name('update');
        Route::delete('/{material}', [InventoryController::class, 'destroy'])
            ->middleware('permission:inventory.materials.delete')
            ->name('destroy');
        Route::post('/{material}/movement', [InventoryController::class, 'movement'])
            ->middleware('permission:inventory.materials.edit')
            ->name('movement');

        Route::get('/danh-muc-loai', [InventoryWorkflowController::class, 'category'])
            ->middleware('permission:inventory.categories.index')
            ->name('types');
        Route::get('/danh-muc-nganh', [InventoryWorkflowController::class, 'category'])
            ->middleware('permission:inventory.categories.index')
            ->name('category');
        Route::post('/danh-muc-nganh', [InventoryWorkflowController::class, 'categoryStore'])
            ->middleware('permission:inventory.categories.create')
            ->name('category.store');
        Route::post('/danh-muc-nganh/gan-tai-khoan', [InventoryWorkflowController::class, 'assignCategory'])
            ->middleware('permission:inventory.categories.edit')
            ->name('category.assign');
        Route::get('/danh-muc-nganh/{category}', [InventoryWorkflowController::class, 'categoryShow'])
            ->middleware('permission:inventory.categories.show')
            ->name('category.show');
        Route::patch('/danh-muc-nganh/{category}', [InventoryWorkflowController::class, 'categoryUpdate'])
            ->middleware('permission:inventory.categories.edit')
            ->name('category.update');
        Route::delete('/danh-muc-nganh/{category}', [InventoryWorkflowController::class, 'categoryDelete'])
            ->middleware('permission:inventory.categories.delete')
            ->name('category.delete');

        Route::get('/cap-nhat', [InventoryWorkflowController::class, 'assets'])
            ->middleware('permission:inventory.assets.index')
            ->name('assets');
        Route::post('/cap-nhat', [InventoryWorkflowController::class, 'assetStore'])
            ->middleware('permission:inventory.assets.create')
            ->name('assets.store');
        Route::post('/cap-nhat/nhieu-dong', [InventoryWorkflowController::class, 'assetBulkStoreDelta'])
            ->middleware('permission:inventory.assets.create')
            ->name('assets.bulk.store');
        Route::post('/cap-nhat/tang-giam', [InventoryWorkflowController::class, 'assetChangeDelta'])
            ->middleware('permission:inventory.assets.edit')
            ->name('assets.change');
        Route::post('/cap-nhat/dieu-chinh', [InventoryWorkflowController::class, 'assetAdjust'])
            ->middleware('permission:inventory.assets.edit')
            ->name('assets.adjust');
        Route::post('/cap-nhat/dieu-chinh-vat-tu', [InventoryWorkflowController::class, 'materialAdjust'])
            ->middleware('permission:inventory.assets.edit')
            ->name('assets.material-adjust');
        Route::patch('/cap-nhat/{asset}', [InventoryWorkflowController::class, 'assetUpdate'])
            ->middleware('permission:inventory.assets.edit')
            ->name('assets.update');
        Route::delete('/cap-nhat/{asset}', [InventoryWorkflowController::class, 'assetDelete'])
            ->middleware('permission:inventory.assets.delete')
            ->name('assets.delete');

        Route::get('/kho', [InventoryWorkflowController::class, 'warehouse'])
            ->middleware('permission:inventory.warehouses.index')
            ->name('warehouse');
        Route::post('/kho', [InventoryWorkflowController::class, 'warehouseStore'])
            ->middleware('permission:inventory.warehouses.create')
            ->name('warehouse.store');
        Route::patch('/kho/{warehouse}', [InventoryWorkflowController::class, 'warehouseUpdate'])
            ->middleware('permission:inventory.warehouses.edit')
            ->name('warehouse.update');
        Route::delete('/kho/{warehouse}', [InventoryWorkflowController::class, 'warehouseDestroy'])
            ->middleware('permission:inventory.warehouses.delete')
            ->name('warehouse.destroy');
        Route::post('/kho/ton', [InventoryWorkflowController::class, 'warehouseItemStore'])
            ->middleware('permission:inventory.warehouses.edit')
            ->name('warehouse.item.store');
        Route::patch('/kho/{warehouse}/ton/{item}', [InventoryWorkflowController::class, 'warehouseItemUpdate'])
            ->middleware('permission:inventory.warehouses.edit')
            ->name('warehouse.item.update');
        Route::delete('/kho/{warehouse}/ton/{item}', [InventoryWorkflowController::class, 'warehouseItemDestroy'])
            ->middleware('permission:inventory.warehouses.delete')
            ->name('warehouse.item.destroy');

        Route::get('/de-xuat', [InventoryWorkflowController::class, 'proposals'])
            ->middleware('permission:inventory.proposals.index')
            ->name('proposals');
        Route::post('/de-xuat', [InventoryWorkflowController::class, 'proposalStore'])
            ->middleware('permission:inventory.proposals.create')
            ->name('proposals.store');
        Route::patch('/de-xuat/{proposal}/cap-nhat', [InventoryWorkflowController::class, 'proposalUpdate'])
            ->middleware('permission:inventory.proposals.edit')
            ->name('proposals.update');
        Route::delete('/de-xuat/{proposal}', [InventoryWorkflowController::class, 'proposalDelete'])
            ->middleware('permission:inventory.proposals.delete')
            ->name('proposals.delete');
        Route::get('/duyet-de-xuat', [InventoryWorkflowController::class, 'proposalApproval'])
            ->middleware('permission:inventory.proposals.approve')
            ->name('proposals.approval');
        Route::get('/duyet-de-xuat/{proposal}', [InventoryWorkflowController::class, 'proposalDetail'])
            ->middleware('permission:inventory.proposals.approve')
            ->name('proposals.detail');
        Route::post('/duyet-de-xuat/{proposal}/in', [InventoryWorkflowController::class, 'proposalPrint'])
            ->middleware('permission:inventory.proposals.export')
            ->name('proposals.print');
        Route::patch('/de-xuat/{proposal}', [InventoryWorkflowController::class, 'proposalDecide'])
            ->middleware('permission:inventory.proposals.approve')
            ->name('proposals.decide');

        Route::get('/phan-cong', [InventoryWorkflowController::class, 'repairs'])
            ->middleware('permission:inventory.repairs.index')
            ->name('repairs');
        Route::post('/phan-cong', [InventoryWorkflowController::class, 'repairStore'])
            ->middleware('permission:inventory.repairs.create')
            ->name('repairs.store');
        Route::patch('/phan-cong/{repair}/assign', [InventoryWorkflowController::class, 'repairAssign'])
            ->middleware('permission:inventory.repairs.edit')
            ->name('repairs.assign');
        Route::patch('/phan-cong/{repair}/cap-nhat', [InventoryWorkflowController::class, 'repairUpdate'])
            ->middleware('permission:inventory.repairs.edit')
            ->name('repairs.update');
        Route::patch('/phan-cong/{repair}', [InventoryWorkflowController::class, 'repairComplete'])
            ->middleware('permission:inventory.repairs.edit')
            ->name('repairs.complete');
        Route::delete('/phan-cong/{repair}', [InventoryWorkflowController::class, 'repairDelete'])
            ->middleware('permission:inventory.repairs.delete')
            ->name('repairs.delete');

        Route::get('/dieu-dong', [InventoryWorkflowController::class, 'transfers'])
            ->middleware('permission:inventory.transfers.index')
            ->name('transfers');
        Route::post('/dieu-dong', [InventoryWorkflowController::class, 'transferStore'])
            ->middleware('permission:inventory.transfers.create')
            ->name('transfers.store');
        Route::get('/dieu-dong/{transfer}', [InventoryWorkflowController::class, 'transferDetail'])
            ->middleware('permission:inventory.transfers.show')
            ->name('transfers.detail');
        Route::patch('/dieu-dong/{transfer}/update', [InventoryWorkflowController::class, 'transferUpdate'])
            ->middleware('permission:inventory.transfers.edit')
            ->name('transfers.update');
        Route::delete('/dieu-dong/{transfer}', [InventoryWorkflowController::class, 'transferDelete'])
            ->middleware('permission:inventory.transfers.delete')
            ->name('transfers.delete');
        Route::get('/dieu-dong/{transfer}/word', [InventoryWorkflowController::class, 'transferWord'])
            ->middleware('permission:inventory.transfers.export')
            ->name('transfers.word');
        Route::patch('/dieu-dong/{transfer}', [InventoryWorkflowController::class, 'transferDecide'])
            ->middleware('permission:inventory.transfers.approve')
            ->name('transfers.decide');

        Route::get('/thanh-ly', [InventoryWorkflowController::class, 'liquidation'])
            ->middleware('permission:inventory.liquidations.index')
            ->name('liquidation');
        Route::get('/nganh-cua-toi', [InventoryWorkflowController::class, 'myCatalog'])
            ->middleware('permission:inventory.categories.index')
            ->name('my-catalog');
        Route::get('/tim-kiem', [InventoryWorkflowController::class, 'search'])
            ->middleware('permission:inventory.search.index')
            ->name('search');

        Route::get('/nhat-ky', [InventoryWorkflowController::class, 'logs'])
            ->middleware('permission:inventory.logs.index')
            ->name('logs');
        Route::patch('/nhat-ky/{log}', [InventoryWorkflowController::class, 'auditLogUpdate'])
            ->middleware('permission:inventory.logs.edit')
            ->name('logs.update');
        Route::delete('/nhat-ky/{log}', [InventoryWorkflowController::class, 'auditLogDelete'])
            ->middleware('permission:inventory.logs.delete')
            ->name('logs.delete');
        Route::patch('/nhat-ky-nhap-xuat/{movement}', [InventoryWorkflowController::class, 'movementUpdate'])
            ->middleware('permission:inventory.logs.edit')
            ->name('movements.update');
        Route::delete('/nhat-ky-nhap-xuat/{movement}', [InventoryWorkflowController::class, 'movementDelete'])
            ->middleware('permission:inventory.logs.delete')
            ->name('movements.delete');
        Route::patch('/nhat-ky-hong/{brokenLog}', [InventoryWorkflowController::class, 'brokenLogUpdate'])
            ->middleware('permission:inventory.logs.edit')
            ->name('broken-logs.update');
        Route::delete('/nhat-ky-hong/{brokenLog}', [InventoryWorkflowController::class, 'brokenLogDelete'])
            ->middleware('permission:inventory.logs.delete')
            ->name('broken-logs.delete');

        Route::get('/bao-cao', [InventoryWorkflowController::class, 'reports'])
            ->middleware('permission:inventory.reports.index')
            ->name('reports');
        Route::get('/bao-cao/word', [InventoryReportTemplateController::class, 'download'])
            ->middleware('permission:inventory.reports.export')
            ->name('reports.word');
        Route::get('/bao-cao/xuat-mau', [InventoryReportTemplateController::class, 'download'])
            ->middleware('permission:inventory.reports.export')
            ->name('reports.word.templates');
        Route::get('/bao-cao/csv', [InventoryWorkflowController::class, 'reportCsv'])
            ->middleware('permission:inventory.reports.export')
            ->name('reports.csv');
        Route::get('/bao-cao/di-chuyen', [InventoryMovementReportController::class, 'index'])
            ->middleware('permission:inventory.reports.index')
            ->name('movement-report');
        Route::post('/bao-cao/di-chuyen/dong-bo', [InventoryMovementReportController::class, 'syncLocations'])
            ->middleware('permission:inventory.locations.import')
            ->name('movement-report.sync');
        Route::get('/bao-cao/di-chuyen/csv', [InventoryMovementReportController::class, 'csv'])
            ->middleware('permission:inventory.reports.export')
            ->name('movement-report.csv');

        Route::get('/mau-bao-cao', [InventoryWorkflowController::class, 'templates'])
            ->middleware('permission:inventory.templates.index')
            ->name('templates');
        Route::post('/mau-bao-cao', [InventoryWorkflowController::class, 'templateStore'])
            ->middleware('permission:inventory.templates.create')
            ->name('templates.store');
        Route::get('/mau-bao-cao/bien/{type}', [InventoryWorkflowController::class, 'defaultTemplateDownload'])
            ->middleware('permission:inventory.templates.export')
            ->name('templates.variable.download');
        Route::get('/mau-bao-cao-mac-dinh/{type}/download', [InventoryWorkflowController::class, 'defaultTemplateDownload'])
            ->middleware('permission:inventory.templates.export')
            ->name('templates.default.download');
        Route::post('/mau-bao-cao-mac-dinh/{type}/replace', [InventoryWorkflowController::class, 'templateReplaceDefault'])
            ->middleware('permission:inventory.templates.edit')
            ->name('templates.default.replace');
        Route::delete('/mau-bao-cao-mac-dinh/{type}', [InventoryWorkflowController::class, 'templateDeleteDefault'])
            ->middleware('permission:inventory.templates.delete')
            ->name('templates.default.delete');
        Route::patch('/mau-bao-cao/{template}', [InventoryWorkflowController::class, 'templateUpdate'])
            ->middleware('permission:inventory.templates.edit')
            ->name('templates.update');
        Route::get('/mau-bao-cao/{template}/download', [InventoryWorkflowController::class, 'templateDownload'])
            ->middleware('permission:inventory.templates.export')
            ->name('templates.download');
        Route::delete('/mau-bao-cao/{template}', [InventoryWorkflowController::class, 'templateDelete'])
            ->middleware('permission:inventory.templates.delete')
            ->name('templates.delete');

        Route::get('/toa-nha', [InventoryWorkflowController::class, 'buildings'])
            ->middleware('permission:inventory.locations.index')
            ->name('buildings');
        Route::get('/phong', [InventoryWorkflowController::class, 'classrooms'])
            ->middleware('permission:inventory.locations.index')
            ->name('classrooms');
        Route::get('/toa-nha/{building}', [InventoryWorkflowController::class, 'building'])
            ->middleware('permission:inventory.locations.show')
            ->name('building');
        Route::get('/phong/{classroom}', [InventoryWorkflowController::class, 'room'])
            ->middleware('permission:inventory.locations.show')
            ->name('room');
        Route::post('/phong/{classroom}/vat-tu', [InventoryWorkflowController::class, 'roomAssetStore'])
            ->middleware('permission:inventory.assets.create')
            ->name('room.assets.store');
        Route::post('/phong/{classroom}/vat-tu/import', [InventoryWorkflowController::class, 'roomAssetImport'])
            ->middleware('permission:inventory.assets.import')
            ->name('room.assets.import');
        Route::patch('/phong/{classroom}/vat-tu/{asset}', [InventoryWorkflowController::class, 'roomAssetUpdate'])
            ->middleware('permission:inventory.assets.edit')
            ->name('room.assets.update');
        Route::delete('/phong/{classroom}/vat-tu/{asset}', [InventoryWorkflowController::class, 'roomAssetDelete'])
            ->middleware('permission:inventory.assets.delete')
            ->name('room.assets.delete');
        Route::post('/phong/{classroom}/anh', [InventoryWorkflowController::class, 'roomImageStore'])
            ->middleware('permission:inventory.locations.create')
            ->name('room.images.store');
        Route::delete('/phong-anh/{image}', [InventoryWorkflowController::class, 'roomImageDelete'])
            ->middleware('permission:inventory.locations.delete')
            ->name('room.images.delete');
        Route::post('/phong/{classroom}/nguoi-dung', [InventoryWorkflowController::class, 'roomUserStore'])
            ->middleware('permission:inventory.locations.edit')
            ->name('room.users.store');
        Route::delete('/phong-nguoi-dung/{roomUser}', [InventoryWorkflowController::class, 'roomUserDelete'])
            ->middleware('permission:inventory.locations.edit')
            ->name('room.users.delete');
    });
