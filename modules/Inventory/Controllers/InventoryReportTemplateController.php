<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Modules\Inventory\Models\{InventoryAsset, InventoryAuditLog, InventoryMovement, InventoryReportTemplate, InventoryTransfer, InventoryWarehouseItem};

class InventoryReportTemplateController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;
    private const POSITION_FIXED_WIDTHS_5 = [900, 3000, 430, 480, 720];
    private const POSITION_FIXED_WIDTHS_4 = [900, 3300, 430, 720];
    private const POSITION_TABLE_WIDTH = 15400;
    private const EXCEL_ONLY_REPORT_TYPES = [
        'xlsx-public-depreciation-detail',
        'xlsx-public-assets-current',
        'xlsx-public-assets-change',
        'xlsx-public-assets-by-unit',
        'xlsx-public-assets-change-detail',
    ];

    public function download(Request $request)
    {
        $files = [
            'position' => 'bao-cao-thuc-luc-hien-co-theo-vi-tri.docx',
            'total-position' => 'bao-cao-thuc-luc-hien-co-tong-the.docx',
            'unit' => 'bao-cao-thuc-luc-hien-co-tong-the.docx',
            'period' => 'bao-cao-tong-hop-thuc-luc-theo-ky.docx',
            'increase-decrease' => 'bao-cao-tang-giam-thuc-luc-vat-tu.docx',
            'using-position' => 'bao-cao-vt-dang-su-dung-vi-tri.docx',
            'using-total' => 'bao-cao-vt-dang-su-dung-tong-the.docx',
            'warehouse' => 'bao-cao-kho-vat-tu.docx',
            'system-warehouse' => 'bao-cao-kho-he-thong-kho-vt.docx',
            'transfer' => 'bao-cao-quyet-dinh-dieu-dong.docx',
            'recall' => 'bao-cao-quyet-dinh-thu-hoi-tra-ve.docx',
            'repair' => 'bao-cao-vat-tu-dang-hu-hai-va-sua-chua.docx',
            'update-log' => 'bao-cao-cap-nhat-vat-tu.docx',
            'public-assets' => 'bao-cao-danh-sach-tai-san-cong.docx',
        ];

        $type = match ((string) $request->input('report_type', 'position')) {
            'summary' => $request->input('scope', 'position') === 'all' ? 'total-position' : 'position',
            'movement' => 'increase-decrease',
            'using' => $request->input('scope', 'position') === 'all' ? 'using-total' : 'using-position',
            'unit' => 'unit',
            default => (string) $request->input('report_type', 'position'),
        };
        if ($request->input('format') === 'xlsx') {
            abort_unless(isset($files[$type]) || in_array($type, self::EXCEL_ONLY_REPORT_TYPES, true), 422, 'Loại báo cáo Excel không hợp lệ.');

            return $this->fillExcelReport($request, $type);
        }

        abort_unless(isset($files[$type]), 422, 'Loại báo cáo không hợp lệ.');

        [$path, $filename] = $this->resolveTemplate($request, $files[$type], $type);
        abort_unless(is_file($path), 404, 'Chưa có mẫu báo cáo tương ứng.');

        return in_array($type, ['position', 'total-position', 'unit', 'using-position', 'using-total'], true)
            ? $this->fillPositionTemplate($request, $path, $filename, $type)
            : $this->fillReportTemplate($request, $path, $filename, $type);
    }

    private function resolveTemplate(Request $request, string $defaultFilename, string $type): array
    {
        if ($this->hasUploadedTemplate($request)) {
            $template = InventoryReportTemplate::whereKey($request->integer('template_id'))->where('active', true)->first();
            $path = $template?->absolutePath();
            if ($template && $template->report_type === $type && $path && is_file($path)) {
                abort_unless(strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx', 422, 'Mẫu báo cáo Word phải là file .docx.');

                return [$path, $template->versionedDownloadName()];
            }
        }

        $custom = InventoryReportTemplate::where('report_type', $type)->where('active', true)->latest()->first();
        if ($custom) {
            $path = $custom->absolutePath();
            if ($path && is_file($path)) {
                return [$path, $custom->versionedDownloadName()];
            }
        }

        $defaultPath = resource_path('inventory-report-templates/'.$defaultFilename);
        if (is_file($defaultPath)) {
            return [$defaultPath, $defaultFilename];
        }
        if ($type === 'public-assets') {
            $generatedPath = storage_path('app/'.$defaultFilename);
            $this->createPublicAssetReportTemplate($generatedPath);
            return [$generatedPath, $defaultFilename];
        }

        abort(404, 'Chưa có mẫu báo cáo Word đang dùng cho loại báo cáo này.');
    }

    private function hasUploadedTemplate(Request $request): bool
    {
        $templateId = (string) $request->input('template_id', '');

        return $templateId !== '' && ctype_digit($templateId) && (int) $templateId > 0;
    }

    private function fillUploadedVariableTemplate(Request $request, string $type): mixed
    {
        $template = InventoryReportTemplate::whereKey($request->integer('template_id'))->where('active', true)->where('report_type', $type)->first();
        abort_unless($template, 404, 'Không tìm thấy mẫu báo cáo đã chọn.');
        $path = $template->absolutePath();
        abort_unless($path && is_file($path), 404, 'File mẫu báo cáo đã chọn không tồn tại.');
        abort_unless(strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx', 422, 'Mẫu báo cáo Word phải là file .docx.');

        [$rowsData, $assets] = $this->loadReportRows($request, $type);
        $processor = new \PhpOffice\PhpWord\TemplateProcessor($path);
        $today = now();
        $titles = [
            'position' => 'Báo cáo thống kê thực lực hiện có',
            'total-position' => 'Báo cáo thống kê thực lực hiện có',
            'unit' => 'Báo cáo thực lực vật tư theo đơn vị',
            'period' => 'Báo cáo tổng hợp thực lực theo kỳ',
            'increase-decrease' => 'Báo cáo tăng giảm thực lực vật tư',
            'warehouse' => 'Báo cáo kho',
            'system-warehouse' => 'Báo cáo kho vật tư',
            'transfer' => 'Quyết định điều động vật tư',
            'recall' => 'Quyết định thu hồi vật tư',
            'repair' => 'Báo cáo vật tư hư hại và sửa chữa',
            'update-log' => 'Báo cáo cập nhật vật tư',
            'using-position' => 'Báo cáo vật tư đang sử dụng theo vị trí',
            'using-total' => 'Báo cáo vật tư đang sử dụng tổng thể',
            'public-assets' => 'Danh sách tài sản công',
        ];
        $stableAssets = $assets->whereNotIn('status', ['BROKEN', 'REPAIRING'])->values();
        $brokenAssets = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
        $reportYear = (int) $request->integer('year', now()->year);
        $processor->setValues([
            'ngay_bao_cao' => $today->format('d/m/Y'),
            'ngay' => $today->format('d'),
            'thang' => $today->format('m'),
            'nam' => $today->format('Y'),
            'nam_thong_ke' => (string) $reportYear,
            'tu_ngay' => $request->filled('from') ? date('d/m/Y', strtotime($request->input('from'))) : '',
            'den_ngay' => $request->filled('to') ? date('d/m/Y', strtotime($request->input('to'))) : $today->format('d/m/Y'),
            'tieu_de' => $titles[$type] ?? 'Báo cáo vật tư',
            'loai_bao_cao' => $titles[$type] ?? 'Báo cáo vật tư',
            'ten_mau' => $template->name,
            'ma_mau' => $template->code,
            'tong_so' => (string) $rowsData->count(),
            'tong_so_luong' => (string) $rowsData->sum(fn ($item) => (float) ($item->quantity ?? $item->asset?->quantity ?? 0)),
            'tong_vat_tu' => (string) $assets->count(),
            'tong_so_luong_vat_tu' => (string) $assets->sum('quantity'),
            'tong_so_luong_on_dinh' => (string) $stableAssets->sum('quantity'),
            'so_luong_vat_tu_on_dinh' => (string) $stableAssets->sum('quantity'),
            'so_dong_on_dinh' => (string) $stableAssets->count(),
            'tong_so_luong_hu_hai' => (string) $brokenAssets->sum('quantity'),
            'tong_so_luong_hu_hong' => (string) $brokenAssets->sum('quantity'),
            'so_luong_vat_tu_hu_hai' => (string) $brokenAssets->sum('quantity'),
            'so_luong_vat_tu_hu_hong' => (string) $brokenAssets->sum('quantity'),
            'so_dong_hu_hai' => (string) $brokenAssets->count(),
            'so_dong_hu_hong' => (string) $brokenAssets->count(),
            'tong_tai_san' => (string) $rowsData->count(),
            'tong_thanh_tien' => $this->formatMoney($rowsData->sum(fn ($asset) => $asset instanceof InventoryAsset ? $this->publicAssetInitialValue($asset) : 0)),
            'tong_tien_khau_hao' => $this->formatMoney($rowsData->sum(fn ($asset) => $asset instanceof InventoryAsset ? $this->publicAssetYearValues($asset, $reportYear)['amount'] : 0)),
            'tong_gia_tri_con_lai' => $this->formatMoney($rowsData->sum(fn ($asset) => $asset instanceof InventoryAsset ? $this->publicAssetYearValues($asset, $reportYear)['remaining_value'] : 0)),
        ]);

        $rows = $rowsData->values()->map(fn ($record, $index) => $this->variableRowValues($record, $type, $index + 1))->all();
        if (!$rows) $rows = [$this->emptyVariableRow()];

        $variables = $processor->getVariables();
        $clonedRows = false;
        if (in_array('stt', $variables, true)) {
            try {
                $processor->cloneRowAndSetValues('stt', $rows);
                $clonedRows = true;
            } catch (\Throwable $e) {
                $clonedRows = false;
            }
        }

        $textRows = collect($rows)->map(fn ($row) => trim(implode(' ', array_filter([
            $row['stt'] ? $row['stt'].'.' : '',
            $row['ma_vat_tu'],
            $row['ten_vat_tu'],
            $row['so_luong'],
            $row['don_vi_tinh'],
            $row['vi_tri'],
            $row['ghi_chu'],
        ], fn ($value) => $value !== '' && $value !== null))))->implode("\n");

        if ($clonedRows) {
            foreach (array_keys($this->emptyVariableRow()) as $macro) {
                $processor->setValue($macro, '');
            }
            $processor->setValue('bang_du_lieu', '');
        } else {
            foreach (($rows[0] ?? $this->emptyVariableRow()) as $macro => $value) {
                $processor->setValue($macro, $value);
            }
            $processor->setValue('bang_du_lieu', $textRows);
        }

        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $template->code ?: 'mau-bao-cao-vat-tu');
        $output = storage_path('app/'.$safeCode.'-'.now()->format('YmdHis').'.docx');
        $processor->saveAs($output);
        $this->applyRequestedPaperSizeToDocx($request, $output);

        return response()->download($output, $safeCode.'.docx')->deleteFileAfterSend(true);
    }

    private function loadReportRows(Request $request, string $type): array
    {
        $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'material.category.parent', 'categoryRelation.parent', 'holdingUnit', 'depreciationYears'])
            ->when(in_array($type, ['public-assets', 'xlsx-public-depreciation-detail', 'xlsx-public-assets-current', 'xlsx-public-assets-by-unit'], true), fn ($q) => $q->where('management_type', 'ASSET'))
            ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->orderBy('name')->get();

        $rowsData = $assets;
        if ($type === 'system-warehouse') {
            $rowsData = InventoryWarehouseItem::with(['warehouse', 'material.category.parent'])
                ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
                ->orderBy('warehouse_id')
                ->orderBy('name')
                ->get();
        } elseif ($type === 'repair') {
            $rowsData = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
        } elseif (in_array($type, ['transfer', 'recall'], true)) {
            $rowsData = InventoryTransfer::with(['asset', 'material', 'fromClassroom.managingUnit', 'toClassroom.managingUnit'])->where('type', $type === 'recall' ? 'RECALL' : 'TRANSFER')->latest()->get();
        } elseif (in_array($type, ['increase-decrease', 'update-log', 'xlsx-public-assets-change', 'xlsx-public-assets-change-detail'], true)) {
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', ['INCREASE', 'DECREASE', 'ADJUST'])
                ->whereIn('entity_type', ['material', 'asset'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        } elseif ($type === 'period') {
            $rowsData = InventoryMovement::with(['material.category.parent', 'material.classroom.building'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        }

        return [$rowsData, $assets];
    }

    private function fillReportTemplate(Request $request, string $template, string $filename, string $type): mixed
    {
        $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'material.category.parent', 'categoryRelation.parent', 'holdingUnit', 'depreciationYears'])
            ->when($type === 'public-assets', fn ($q) => $q->where('management_type', 'ASSET'))
            ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->orderBy('name')->get();
        $rowsData = $assets;
        if ($type === 'system-warehouse') {
            $rowsData = InventoryWarehouseItem::with(['warehouse', 'material.category.parent'])
                ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
                ->orderBy('warehouse_id')
                ->orderBy('name')
                ->get();
        } elseif ($type === 'repair') {
            $rowsData = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
        } elseif (in_array($type, ['transfer', 'recall'], true)) {
            $rowsData = InventoryTransfer::with(['asset', 'fromClassroom.managingUnit', 'toClassroom.managingUnit'])->where('type', $type === 'recall' ? 'RECALL' : 'TRANSFER')->latest()->get();
            abort_if($rowsData->isEmpty(), 422, $type === 'recall' ? 'Chưa có phiếu thu hồi để xuất quyết định.' : 'Chưa có phiếu điều động để xuất quyết định.');
            // Mẫu quyết định là một quyết định cho một phiếu; lấy phiếu mới nhất đã có trong hệ thống.
            $rowsData = collect([$rowsData->first()]);
        } elseif ($type === 'increase-decrease') {
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', ['INCREASE', 'DECREASE', 'ADJUST'])
                ->whereIn('entity_type', ['material', 'asset'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        } elseif ($type === 'update-log') {
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', ['INCREASE', 'DECREASE', 'ADJUST'])
                ->whereIn('entity_type', ['material', 'asset'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        } elseif ($type === 'period') {
            $rowsData = InventoryMovement::with(['material.category.parent', 'material.classroom.building'])
                ->when($request->filled('building_id'), fn ($q) => $q->whereHas('material.classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
                ->when($request->filled('classroom_id'), fn ($q) => $q->whereHas('material', fn ($material) => $material->where('classroom_id', $request->integer('classroom_id'))))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        }

        if (in_array($type, ['transfer', 'recall'], true) && $rowsData->isEmpty()) {
            abort(422, $type === 'transfer'
                ? 'Chưa có phiếu điều động để xuất quyết định.'
                : 'Chưa có phiếu thu hồi để xuất quyết định.');
        }

        $zip = new \ZipArchive();
        abort_unless($zip->open($template) === true, 500, 'Không mở được mẫu báo cáo.');
        $xml = new \DOMDocument();
        $xml->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables = $xpath->query('//w:tbl');
        $tableIndexes = $tables->length > 1 ? ($type === 'warehouse' ? [1, 2] : [1]) : [0];
        foreach ($tableIndexes as $tableIndex) {
            $table = $tables->item($tableIndex);
            if (!$table) continue;
            $tableRows = $xpath->query('./w:tr', $table);
            $headerRows = $tables->length === 1 ? 2 : 1;
            if ($type === 'increase-decrease' && $this->fillIncreaseDecreaseTable($xml, $xpath, $table, $tableRows, $rowsData)) {
                continue;
            }
            // Hai mẫu tổng hợp có thêm dòng phân nhóm trước dòng dữ liệu.
            // Giữ dòng phân nhóm của mẫu và nhân bản đúng dòng chi tiết.
            $templateRowIndex = in_array($type, ['increase-decrease', 'period'], true) ? 3 : $headerRows;
            $templateRow = $tableRows->item(min($templateRowIndex, $tableRows->length - 1));
            if (!$templateRow) continue;
            $totalRow = null;
            $lastText = trim(implode(' ', array_map(fn ($n) => $n->nodeValue, iterator_to_array($xpath->query('.//w:t', $tableRows->item($tableRows->length - 1))))));
            if (str_contains($lastText, 'TỔNG CỘNG')) $totalRow = $tableRows->item($tableRows->length - 1)->cloneNode(true);
            $removeFrom = in_array($type, ['increase-decrease', 'period'], true) ? 2 : $headerRows;
            for ($i = $tableRows->length - 1; $i >= $removeFrom; $i--) $table->removeChild($tableRows->item($i));
            $source = $rowsData;
            if ($type === 'warehouse' && $tableIndex === 1) $source = $assets->whereNotIn('status', ['BROKEN', 'REPAIRING'])->values();
            if ($type === 'warehouse' && $tableIndex === 2) $source = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
            foreach ($source as $index => $record) {
                $values = $this->reportRowValues($record, $type, $index + 1, $tableIndex);
                $this->setTemplateRow($xml, $templateRow->cloneNode(true), $values, $table);
            }
            if ($totalRow) {
                if ($type === 'public-assets') {
                    $reportYear = (int) $request->integer('year', now()->year);
                    $totalQuantity = $source->sum(fn ($item) => (float) ($item->quantity ?? 0));
                    $totalAmount = $source->sum(fn ($item) => $item instanceof InventoryAsset ? $this->publicAssetInitialValue($item) : 0);
                    $depreciationAmount = $source->sum(fn ($item) => $item instanceof InventoryAsset ? $this->publicAssetYearValues($item, $reportYear)['amount'] : 0);
                    $remainingValue = $source->sum(fn ($item) => $item instanceof InventoryAsset ? $this->publicAssetYearValues($item, $reportYear)['remaining_value'] : 0);
                    $this->setTemplateRow($xml, $totalRow, ['', 'TỔNG CỘNG', '', '', '', '', $totalQuantity, '', '', $this->formatMoney($totalAmount), '', '', '', '', '', '', $this->formatMoney($depreciationAmount), $this->formatMoney($remainingValue), '', ''], $table);
                    continue;
                }
                $total = $source->sum(fn ($item) => (float) ($item instanceof InventoryAsset
                    ? $item->quantity
                    : ($item instanceof InventoryAuditLog
                        ? abs((float) (($item->details['change'] ?? $item->details['quantity'] ?? 0)))
                        : ($item->quantity ?? $item->asset?->quantity ?? 0))));
                $this->setTemplateRow($xml, $totalRow, $type === 'system-warehouse' ? [null, 'TỔNG CỘNG', null, null, $total] : [null, 'TỔNG CỘNG', null, $total], $table);
            }
        }
        $this->replaceReportDate($xpath, $request);
        if (in_array($type, ['transfer', 'recall'], true)) $this->replaceTransferDocument($xpath, $rowsData->first(), $type);
        if ($type === 'update-log') $this->replaceUpdateSummary($xpath, $rowsData);
        if ($type === 'repair') $this->replaceRepairSummary($xpath, $rowsData);
        if ($type === 'warehouse') {
            $this->replaceWarehouseSummary($xpath, $assets);
            $this->removeEmptyRepairSection($xpath, $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values());
        }
        $this->replaceScalarTemplateValues($xpath, $request, $type);
        $this->applyRequestedPaperSize($xml, $xpath, $request, 'landscape');
        $documentXml = $xml->saveXML();
        $zip->close();
        return $this->writeReportZip($template, $documentXml, $filename);
    }

    private function fillExcelReport(Request $request, string $type): mixed
    {
        abort_unless(class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class), 500, 'Chưa cài thư viện xuất Excel.');

        [$rowsData, $assets] = $this->loadReportRows($request, $type);
        if (in_array($type, ['transfer', 'recall'], true)) {
            abort_if($rowsData->isEmpty(), 422, $type === 'transfer'
                ? 'Chưa có phiếu điều động để xuất quyết định.'
                : 'Chưa có phiếu thu hồi để xuất quyết định.');
        }

        $spreadsheet = $this->makeExcelWorkbook($request, $type);
        $depreciationYears = $type === 'xlsx-public-depreciation-detail' ? $this->excelDepreciationYears($request, $rowsData) : [];
        if ($depreciationYears) {
            $this->configureExcelDepreciationYearColumns($spreadsheet, $depreciationYears);
        }
        $rowValues = $rowsData->values()->map(function ($record, $index) use ($type, $depreciationYears) {
            $values = $this->variableRowValues($record, $type, $index + 1);
            if ($depreciationYears && $record instanceof InventoryAsset) {
                foreach ($depreciationYears as $year) {
                    $depreciation = $this->publicAssetYearValues($record, $year);
                    $values['khau_hao_'.$year] = $depreciation['rate'] > 0 ? rtrim(rtrim(number_format($depreciation['rate'], 2, ',', '.'), '0'), ',').'%' : '';
                }
            }
            return $values;
        })->values();
        if ($this->fillExcelVariableTemplate($spreadsheet, $request, $type, $rowValues)) {
            $filename = $this->safeDownloadName($this->excelReportTitle($type)).'-'.now()->format('YmdHis').'.xlsx';
            $output = storage_path('app/'.$filename);
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($output);

            return response()->download($output, $filename)->deleteFileAfterSend(true);
        }

        $sheet = $spreadsheet->getSheetCount() === 1 && $spreadsheet->getActiveSheet()->getCell('A1')->getValue() === null
            ? $spreadsheet->getActiveSheet()
            : $spreadsheet->createSheet();
        $title = $this->excelReportTitle($type);
        $columns = $this->excelReportColumns($type);

        $sheet->setTitle(mb_substr($this->safeSheetTitle($title), 0, 31));
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Ngày xuất: '.now()->format('d/m/Y H:i'));
        if ($request->filled('from') || $request->filled('to')) {
            $sheet->setCellValue('A3', 'Từ ngày: '.($request->filled('from') ? date('d/m/Y', strtotime($request->input('from'))) : '').' - Đến ngày: '.($request->filled('to') ? date('d/m/Y', strtotime($request->input('to'))) : now()->format('d/m/Y')));
        } elseif (in_array($type, ['public-assets', 'xlsx-public-depreciation-detail', 'xlsx-public-assets-current', 'xlsx-public-assets-by-unit'], true)) {
            $sheet->setCellValue('A3', 'Năm thống kê: '.$request->integer('year', now()->year));
        }

        $headerRow = 5;
        $columnIndex = 1;
        foreach (array_keys($columns) as $label) {
            $sheet->setCellValue($this->excelCell($columnIndex, $headerRow), $label);
            $columnIndex++;
        }

        $rowIndex = $headerRow + 1;
        foreach ($rowValues as $values) {
            $columnIndex = 1;
            foreach ($columns as $key) {
                $sheet->setCellValue($this->excelCell($columnIndex, $rowIndex), $values[$key] ?? '');
                $columnIndex++;
            }
            $rowIndex++;
        }

        $lastColumn = count($columns);
        if ($lastColumn > 0) {
            $titleRange = 'A1:'.$this->excelCell($lastColumn, 1);
            $tableRange = 'A'.$headerRow.':'.$this->excelCell($lastColumn, max($headerRow, $rowIndex - 1));
            $sheet->mergeCells($titleRange);
            $sheet->getStyle($titleRange)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A'.$headerRow.':'.$this->excelCell($lastColumn, $headerRow))->getFont()->setBold(true);
            $sheet->getStyle($tableRange)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            for ($column = 1; $column <= $lastColumn; $column++) {
                $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
            }
            $sheet->freezePane('A'.($headerRow + 1));
            $sheet->setAutoFilter($tableRange);
        }

        $filename = $this->safeDownloadName($title).'-'.now()->format('YmdHis').'.xlsx';
        $output = storage_path('app/'.$filename);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($output);

        return response()->download($output, $filename)->deleteFileAfterSend(true);
    }

    private function makeExcelWorkbook(Request $request, string $type): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        if (! $this->hasUploadedTemplate($request)) {
            return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        }

        $template = InventoryReportTemplate::whereKey($request->integer('template_id'))
            ->where('active', true)
            ->where('report_type', $type)
            ->first();
        $path = $template?->absolutePath();
        if (! $template || $template->format() !== 'xlsx' || ! $path || ! is_file($path)) {
            return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        }

        return \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    }

    private function fillExcelVariableTemplate(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, Request $request, string $type, \Illuminate\Support\Collection $rows): bool
    {
        $usedPlaceholders = false;
        $scalarValues = $this->excelScalarValues($request, $type, $rows);
        $rowKeys = array_keys($this->emptyVariableRow());

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $templateRow = null;
            $highestRow = $sheet->getHighestRow();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowPlaceholders = [];
                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row;
                    $value = $sheet->getCell($coordinate)->getValue();
                    if (! is_string($value) || ! str_contains($value, '${')) {
                        continue;
                    }
                    if (preg_match_all('/\$\{([A-Za-z0-9_]+)\}/', $value, $matches)) {
                        $rowPlaceholders = array_merge($rowPlaceholders, $matches[1]);
                    }
                }
                if (count(array_intersect($rowPlaceholders, $rowKeys)) >= 2
                    && count(array_intersect($rowPlaceholders, ['stt', 'ten_tai_san', 'ten_vat_tu', 'ma_tai_san', 'ma_vat_tu'])) > 0) {
                    $templateRow = $row;
                    break;
                }
            }

            if ($templateRow !== null) {
                $usedPlaceholders = true;
                $rowCount = max(1, $rows->count());
                if ($rowCount > 1) {
                    $sheet->insertNewRowBefore($templateRow + 1, $rowCount - 1);
                    for ($offset = 1; $offset < $rowCount; $offset++) {
                        $this->copyExcelRowStyle($sheet, $templateRow, $templateRow + $offset, $highestColumnIndex);
                    }
                }

                $templateValues = [];
                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $templateValues[$column] = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$templateRow)->getValue();
                }

                for ($offset = 0; $offset < $rowCount; $offset++) {
                    $values = $rows->get($offset, $this->emptyVariableRow());
                    for ($column = 1; $column <= $highestColumnIndex; $column++) {
                        $value = $templateValues[$column] ?? null;
                        if (is_string($value) && str_contains($value, '${')) {
                            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).($templateRow + $offset);
                            $sheet->getCell($coordinate)->setValueExplicit($this->replaceExcelPlaceholders($value, $values + $scalarValues), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    }
                }
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($column = 1; $column <= $highestColumnIndex; $column++) {
                    $cell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row);
                    $value = $cell->getValue();
                    if (is_string($value) && str_contains($value, '${')) {
                        $usedPlaceholders = true;
                        $cell->setValueExplicit($this->replaceExcelPlaceholders($value, $scalarValues), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
            }
        }

        return $usedPlaceholders;
    }

    private function excelDepreciationYears(Request $request, \Illuminate\Support\Collection $rowsData): array
    {
        if ($request->filled('depreciation_from_year') || $request->filled('depreciation_to_year')) {
            $from = (int) ($request->input('depreciation_from_year') ?: $request->input('depreciation_to_year') ?: now()->year);
            $to = (int) ($request->input('depreciation_to_year') ?: $from);
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $to = min($to, $from + 30);
            return range($from, $to);
        }

        $years = $rowsData
            ->filter(fn ($record) => $record instanceof InventoryAsset)
            ->flatMap(fn (InventoryAsset $asset) => $asset->depreciationYears?->pluck('year') ?? collect())
            ->map(fn ($year) => (int) $year)
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $years ?: [(int) now()->year];
    }

    private function configureExcelDepreciationYearColumns(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, array $years): void
    {
        $sheet = $spreadsheet->getSheet(0);
        $startColumn = 8; // H
        $reservedColumns = 6; // H:M in the source template, including the two "..." columns.
        $years = array_slice($years, 0, $reservedColumns);
        $yearCount = max(1, count($years));

        $lastYearColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + $yearCount - 1);
        $sheet->setCellValue('H8', 'Tỷ lệ % khấu hao tài sản qua các năm');

        for ($offset = 0; $offset < $reservedColumns; $offset++) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + $offset);
            $sheet->getColumnDimension($column)->setVisible($offset < $yearCount);
            if (isset($years[$offset])) {
                $year = $years[$offset];
                $sheet->setCellValue($column.'9', (string) $year);
                $sheet->setCellValue($column.'12', '${khau_hao_'.$year.'}');
            } else {
                $sheet->setCellValue($column.'9', '');
                $sheet->setCellValue($column.'12', '');
            }
        }
    }

    private function copyExcelRowStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $sourceRow, int $targetRow, int $highestColumnIndex): void
    {
        $sheet->getRowDimension($targetRow)->setRowHeight($sheet->getRowDimension($sourceRow)->getRowHeight());
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $source = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$sourceRow;
            $target = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$targetRow;
            $sheet->duplicateStyle($sheet->getStyle($source), $target);
        }
    }

    private function replaceExcelPlaceholders(string $value, array $values): string
    {
        return preg_replace_callback('/\$\{([A-Za-z0-9_]+)\}/', fn ($match) => (string) ($values[$match[1]] ?? ''), $value) ?? $value;
    }

    private function excelScalarValues(Request $request, string $type, \Illuminate\Support\Collection $rows): array
    {
        $today = now();
        $year = (int) $request->integer('year', $today->year);
        $firstRow = (array) ($rows->first() ?: []);

        return [
            'ngay_bao_cao' => $today->format('d/m/Y'),
            'ngay' => $today->format('d'),
            'thang' => $today->format('m'),
            'nam' => $today->format('Y'),
            'nam_thong_ke' => (string) $year,
            'nam_thong_ke_1' => (string) ($year + 1),
            'nam_thong_ke_2' => (string) ($year + 2),
            'nam_thong_ke_3' => (string) ($year + 3),
            'tu_ngay' => $request->filled('from') ? date('d/m/Y', strtotime($request->input('from'))) : '',
            'den_ngay' => $request->filled('to') ? date('d/m/Y', strtotime($request->input('to'))) : $today->format('d/m/Y'),
            'tieu_de' => $this->excelReportTitle($type),
            'loai_bao_cao' => $type,
            'tong_so' => (string) $rows->count(),
            'tong_so_luong' => (string) $rows->sum(fn ($row) => (float) str_replace(',', '.', (string) ($row['so_luong'] ?? 0))),
            'tong_tai_san' => (string) $rows->count(),
            'nganh' => (string) ($firstRow['nganh'] ?? ''),
            'loai_tai_san' => (string) ($firstRow['loai_tai_san'] ?? ''),
            'don_vi_quan_ly' => (string) ($firstRow['don_vi_quan_ly'] ?? ''),
        ];
    }

    private function excelReportTitle(string $type): string
    {
        return [
            'position' => 'Thống kê thực lực hiện có theo vị trí',
            'total-position' => 'Thống kê thực lực hiện có tổng hợp',
            'unit' => 'Thống kê thực lực vật tư theo đơn vị',
            'period' => 'Báo cáo tổng hợp theo kỳ',
            'increase-decrease' => 'Thống kê tăng giảm thực lực vật tư',
            'using-position' => 'Báo cáo vật tư đang sử dụng theo vị trí',
            'using-total' => 'Báo cáo vật tư đang sử dụng tổng hợp',
            'warehouse' => 'Báo cáo kho',
            'system-warehouse' => 'Báo cáo kho vật tư',
            'transfer' => 'Quyết định điều động',
            'recall' => 'Quyết định thu hồi',
            'repair' => 'Vật tư đang hư hại và sửa chữa',
            'update-log' => 'Cập nhật vật tư',
            'public-assets' => 'Danh sách tài sản công',
            'xlsx-public-depreciation-detail' => 'Chi tiết khấu hao vật tư trang bị thuộc tài sản công',
            'xlsx-public-assets-current' => 'Thống kê thực lực vật tư trang bị thuộc tài sản cố định hiện có',
            'xlsx-public-assets-change' => 'Tăng giảm vật tư trang bị thuộc tài sản công',
            'xlsx-public-assets-by-unit' => 'Thống kê vật tư trang bị thuộc tài sản cố định hiện có tại các đơn vị',
            'xlsx-public-assets-change-detail' => 'Chi tiết tăng giảm vật tư trang bị thuộc tài sản cố định',
        ][$type] ?? 'Báo cáo vật tư';
    }

    private function excelReportColumns(string $type): array
    {
        return match ($type) {
            'increase-decrease' => [
                'Tên vật tư' => 'ten_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'Phân cấp' => 'phan_cap',
                'SL tăng' => 'so_luong_tang',
                'SL giảm' => 'so_luong_giam',
                'Trên cấp' => 'tren_cap',
                'Điều động đến' => 'dieu_dong_den',
                'Mua sắm' => 'mua_sam',
                'Kiểm kê tăng' => 'kiem_ke_tang',
                'Tăng phân cấp' => 'tang_phan_cap',
                'Tăng khác' => 'tang_khac',
                'Trả trên' => 'tra_tren',
                'Điều động đi' => 'dieu_dong_di',
                'Hao hụt' => 'hao_hut',
                'Hư hỏng' => 'hu_hong',
                'Kiểm kê giảm' => 'kiem_ke_giam',
                'Thanh lý' => 'thanh_ly',
                'Giảm phân cấp' => 'giam_phan_cap',
                'Giảm khác' => 'giam_khac',
            ],
            'period' => [
                'STT' => 'stt',
                'Ngày dữ liệu' => 'ngay_du_lieu',
                'Loại biến động' => 'loai_bien_dong',
                'Tên vật tư' => 'ten_vat_tu',
                'Số lượng' => 'so_luong',
                'Ghi chú' => 'ghi_chu',
            ],
            'system-warehouse' => [
                'STT' => 'stt',
                'Mã vật tư' => 'ma_vat_tu',
                'Tên vật tư' => 'ten_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'Số lượng' => 'so_luong',
                'Kho' => 'kho',
                'Vị trí' => 'vi_tri',
                'Tồn tối thiểu' => 'ton_toi_thieu',
                'Ghi chú' => 'ghi_chu',
            ],
            'update-log' => [
                'STT' => 'stt',
                'Ngày dữ liệu' => 'ngay_du_lieu',
                'Loại biến động' => 'loai_bien_dong',
                'Tên vật tư' => 'ten_vat_tu',
                'Số lượng' => 'so_luong',
                'Trước' => 'truoc',
                'Sau' => 'sau',
                'Vị trí' => 'vi_tri',
                'Người thực hiện' => 'nguoi_thuc_hien',
                'Lý do' => 'ly_do',
            ],
            'public-assets', 'xlsx-public-depreciation-detail' => [
                'STT' => 'stt',
                'Mã tài sản' => 'ma_tai_san',
                'Tên tài sản' => 'ten_tai_san',
                'Ngành' => 'nganh',
                'Loại tài sản' => 'loai_tai_san',
                'Phân cấp' => 'phan_cap',
                'Số lượng' => 'so_luong',
                'ĐVT' => 'don_vi_tinh',
                'Đơn giá' => 'don_gia',
                'Thành tiền' => 'thanh_tien',
                'Phòng' => 'phong',
                'Địa chỉ lắp đặt' => 'dia_chi_lap_dat',
                'Đơn vị cung cấp' => 'don_vi_cung_cap',
                'Hợp đồng / hóa đơn' => 'hop_dong_hoa_don',
                'Năm khấu hao' => 'nam_khau_hao',
                'Tỷ lệ khấu hao' => 'ty_le_khau_hao',
                'Tiền khấu hao' => 'tien_khau_hao',
                'Giá trị còn lại' => 'gia_tri_con_lai',
                'Trạng thái' => 'trang_thai',
                'Ghi chú' => 'ghi_chu',
            ],
            'xlsx-public-assets-current' => [
                'STT' => 'stt',
                'Mã tài sản' => 'ma_tai_san',
                'Tên tài sản' => 'ten_tai_san',
                'Ngành' => 'nganh',
                'Loại tài sản' => 'loai_tai_san',
                'Phân cấp' => 'phan_cap',
                'Số lượng' => 'so_luong',
                'ĐVT' => 'don_vi_tinh',
                'Thành tiền' => 'thanh_tien',
                'Hợp đồng / hóa đơn' => 'hop_dong_hoa_don',
                'Đơn vị cung cấp' => 'don_vi_cung_cap',
                'Tỷ lệ khấu hao' => 'ty_le_khau_hao',
                'Tiền khấu hao' => 'tien_khau_hao',
                'Giá trị còn lại' => 'gia_tri_con_lai',
                'SL còn sử dụng được' => 'so_luong_su_dung_duoc',
                'SL hỏng, không sử dụng được' => 'so_luong_hong',
                'Ghi chú' => 'ghi_chu',
            ],
            'xlsx-public-assets-change' => [
                'Tên vật tư' => 'ten_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'Phân cấp' => 'phan_cap',
                'Thực lực đầu kỳ' => 'truoc',
                'Tăng' => 'so_luong_tang',
                'Giảm' => 'so_luong_giam',
                'Thực lực cuối kỳ' => 'sau',
                'Trên cấp' => 'tren_cap',
                'Mua sắm' => 'mua_sam',
                'Khác tăng' => 'tang_khac',
                'Trả trên' => 'tra_tren',
                'Khấu hao / hư hỏng' => 'hu_hong',
                'Thanh lý' => 'thanh_ly',
                'Khác giảm' => 'giam_khac',
            ],
            'xlsx-public-assets-by-unit' => [
                'STT' => 'stt',
                'Đơn vị' => 'don_vi_quan_ly',
                'Mã tài sản' => 'ma_tai_san',
                'Tên tài sản' => 'ten_tai_san',
                'Ngành' => 'nganh',
                'Loại tài sản' => 'loai_tai_san',
                'Phân cấp' => 'phan_cap',
                'Số lượng hiện có' => 'so_luong',
                'ĐVT' => 'don_vi_tinh',
                'Phòng' => 'phong',
                'Trạng thái' => 'trang_thai',
                'Ghi chú' => 'ghi_chu',
            ],
            'xlsx-public-assets-change-detail' => [
                'STT' => 'stt',
                'Ngày dữ liệu' => 'ngay_du_lieu',
                'Mã vật tư' => 'ma_vat_tu',
                'Tên vật tư' => 'ten_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'Phân cấp' => 'phan_cap',
                'Loại biến động' => 'loai_bien_dong',
                'Số lượng' => 'so_luong',
                'Trước' => 'truoc',
                'Sau' => 'sau',
                'Lý do' => 'ly_do',
                'Người thực hiện' => 'nguoi_thuc_hien',
                'Ghi chú' => 'ghi_chu',
            ],
            default => [
                'STT' => 'stt',
                'Mã vật tư' => 'ma_vat_tu',
                'Tên vật tư' => 'ten_vat_tu',
                'Ngành' => 'nganh',
                'Loại vật tư' => 'loai_vat_tu',
                'ĐVT' => 'don_vi_tinh',
                'Số lượng' => 'so_luong',
                'Phân cấp' => 'phan_cap',
                'Tòa nhà' => 'toa_nha',
                'Phòng' => 'phong',
                'Đơn vị quản lý' => 'don_vi_quan_ly',
                'Trạng thái' => 'trang_thai',
                'Ghi chú' => 'ghi_chu',
            ],
        };
    }

    private function safeSheetTitle(string $title): string
    {
        return preg_replace('/[\\\\\\/\\?\\*\\[\\]:]+/u', ' ', $title) ?: 'Bao cao';
    }

    private function safeDownloadName(string $title): string
    {
        $name = \Illuminate\Support\Str::ascii($title);
        $name = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?: 'bao-cao-vat-tu');

        return trim($name, '-') ?: 'bao-cao-vat-tu';
    }

    private function excelCell(int $column, int $row): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column).$row;
    }

    private function fillIncreaseDecreaseTable(\DOMDocument $xml, \DOMXPath $xpath, \DOMNode $table, \DOMNodeList $rows, $source): bool
    {
        $categoryIndex = $this->findTableRowIndexContaining($xpath, $rows, 'loai_vat_tu');
        $industryIndex = $this->findTableRowIndexContaining($xpath, $rows, 'nganh');
        $itemIndex = $this->findTableRowIndexContaining($xpath, $rows, 'ten_vat_tu');
        if ($itemIndex === null) return false;

        $categoryTemplate = $categoryIndex !== null ? $rows->item($categoryIndex)->cloneNode(true) : null;
        $industryTemplate = $industryIndex !== null ? $rows->item($industryIndex)->cloneNode(true) : null;
        $itemTemplate = $rows->item($itemIndex)->cloneNode(true);
        $totalTemplate = ($this->findTableRowContaining($xpath, $rows, 'TỔNG CỘNG') ?: $rows->item($rows->length - 1))?->cloneNode(true);
        $removeFrom = min(array_filter([$categoryIndex, $industryIndex, $itemIndex], fn ($index) => $index !== null));
        for ($i = $rows->length - 1; $i >= $removeFrom; $i--) $table->removeChild($rows->item($i));

        $rowsByCategory = collect($source)->map(fn ($record) => $this->increaseDecreaseRow($record))->groupBy('loai_vat_tu');
        foreach ($rowsByCategory as $category => $categoryRows) {
            if ($categoryTemplate) $this->setTemplateRowVariables($xpath, $categoryTemplate->cloneNode(true), ['loai_vat_tu' => $category ?: 'Chưa xác định loại'], $table);
            foreach ($categoryRows->groupBy('nganh') as $industry => $industryRows) {
                if ($industryTemplate) $this->setTemplateRowVariables($xpath, $industryTemplate->cloneNode(true), ['nganh' => $industry ?: 'Chưa xác định ngành'], $table);
                foreach ($industryRows as $row) {
                    $this->setTemplateRowVariables($xpath, $itemTemplate->cloneNode(true), $row, $table);
                }
            }
        }

        if ($totalTemplate) {
            $total = collect($source)->sum(fn ($record) => abs((float) (((array) $record->details)['change'] ?? ((array) $record->details)['quantity'] ?? 0)));
            $this->setTemplateRowVariables($xpath, $totalTemplate, ['tong_so_luong' => $total], $table);
        }

        return true;
    }

    private function increaseDecreaseRow(InventoryAuditLog $record): array
    {
        $details = (array) $record->details;
        $material = null;
        if ($record->entity_type === 'material' && $record->entity_id) {
            $material = \Modules\Inventory\Models\InventoryMaterial::with('category.parent')->find($record->entity_id);
        } elseif ($record->entity_type === 'asset' && $record->entity_id) {
            $asset = InventoryAsset::with('material.category.parent')->find($record->entity_id);
            $material = $asset?->material;
        }

        $change = (float) ($details['change'] ?? $details['quantity'] ?? 0);
        if ($record->action === 'DECREASE') $change = -abs($change);
        $reason = mb_strtolower(trim((string) ($details['reason'] ?? $details['note'] ?? '')));
        $row = [
            'loai_vat_tu' => (string) ($material?->category?->name ?: $details['category'] ?? 'Chưa xác định loại'),
            'nganh' => (string) ($material?->category?->parent?->name ?: 'Chưa xác định ngành'),
            'ten_vat_tu' => (string) ($material?->name ?: $details['name'] ?? 'Vật tư'),
            'don_vi_tinh' => (string) ($material?->unit ?: $details['unit'] ?? ''),
            'phan_cap' => (string) ($details['grade'] ?? ''),
            'so_luong_tang' => $change > 0 ? (string) $change : '',
            'so_luong_giam' => $change < 0 ? (string) abs($change) : '',
            'truoc' => (string) ($details['before'] ?? ''),
            'sau' => (string) ($details['after'] ?? ''),
            'tren_cap' => '',
            'dieu_dong_den' => '',
            'mua_sam' => '',
            'tang_phan_cap' => '',
            'kiem_ke_tang' => '',
            'tang_khac' => '',
            'tra_tren' => '',
            'dieu_dong_di' => '',
            'hao_hut' => '',
            'hu_hong' => '',
            'kiem_ke_giam' => '',
            'thanh_ly' => '',
            'giam_phan_cap' => '',
            'giam_khac' => '',
        ];
        $key = $this->increaseDecreaseReasonColumn((string) ($details['reason_code'] ?? ''), $reason, $change);
        $row[$key] = (string) ($details['reason'] ?? $details['note'] ?? '');
        return $row;
    }

    private function increaseDecreaseReasonColumn(string $code, string $reason, float $change): string
    {
        $code = strtoupper(trim($code));
        $byCode = [
            'T01' => 'tren_cap',
            'T02' => 'dieu_dong_den',
            'T03' => 'mua_sam',
            'T04' => 'kiem_ke_tang',
            'T05' => 'tang_phan_cap',
            'T06' => 'tang_khac',
            'G01' => 'tra_tren',
            'G02' => 'dieu_dong_di',
            'G03' => 'hao_hut',
            'G04' => 'hu_hong',
            'G05' => 'kiem_ke_giam',
            'G06' => 'thanh_ly',
            'G07' => 'giam_phan_cap',
            'G08' => 'giam_khac',
        ];
        if (isset($byCode[$code])) return $byCode[$code];

        return match (true) {
            $change >= 0 && (str_contains($reason, 'trên cấp') || str_contains($reason, 'tren cap')) => 'tren_cap',
            $change >= 0 && (str_contains($reason, 'điều động đến') || str_contains($reason, 'dieu dong den')) => 'dieu_dong_den',
            $change >= 0 && (str_contains($reason, 'mua sắm') || str_contains($reason, 'mua sam')) => 'mua_sam',
            $change >= 0 && str_contains($reason, 'phân cấp') => 'tang_phan_cap',
            $change >= 0 && str_contains($reason, 'kiểm kê') => 'kiem_ke_tang',
            $change < 0 && (str_contains($reason, 'trả trên') || str_contains($reason, 'tra tren')) => 'tra_tren',
            $change < 0 && (str_contains($reason, 'điều động đi') || str_contains($reason, 'dieu dong di')) => 'dieu_dong_di',
            $change < 0 && (str_contains($reason, 'hao hụt') || str_contains($reason, 'hao hut')) => 'hao_hut',
            $change < 0 && str_contains($reason, 'thanh lý') => 'thanh_ly',
            $change < 0 && (str_contains($reason, 'hư hỏng') || str_contains($reason, 'hu hong')) => 'hu_hong',
            $change < 0 && str_contains($reason, 'kiểm kê') => 'kiem_ke_giam',
            $change < 0 && str_contains($reason, 'phân cấp') => 'giam_phan_cap',
            default => $change >= 0 ? 'tang_khac' : 'giam_khac',
        };
    }

    private function setTemplateRowVariables(\DOMXPath $xpath, \DOMNode $row, array $values, \DOMNode $table): void
    {
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $texts = $xpath->query('.//w:t', $cell);
            if (!$texts->length) continue;

            $value = '';
            foreach ($texts as $text) {
                $value .= $text->nodeValue;
            }
            foreach ($values as $key => $replacement) {
                $value = str_replace(['${'.$key.'}', '${ '.$key.' }', '${ '.str_replace('_', '_', $key).' }'], (string) $replacement, $value);
                $value = preg_replace('/\$\{\s*'.preg_quote($key, '/').'\s*\}/u', (string) $replacement, $value);
            }
            $texts->item(0)->nodeValue = $value;
            for ($i = 1; $i < $texts->length; $i++) {
                $texts->item($i)->nodeValue = '';
            }
        }
        $table->appendChild($row);
    }

    private function reportRowValues(mixed $record, string $type, int $number, ?int $tableIndex = null): array
    {
        if ($record instanceof InventoryTransfer) {
            return [$number, $record->asset?->name ?: $record->material?->name, $record->asset?->unit ?: 'Cái', $record->asset?->grade ?: 1, $record->quantity ?: 1, $record->general_note ?: ''];
        }
        if ($record instanceof InventoryMovement) {
            return [$number, optional($record->created_at)->format('d/m/Y'), ['IN' => 'Tăng', 'OUT' => 'Giảm', 'ADJUST' => 'Điều chỉnh'][$record->type] ?? $record->type, $record->material?->name, $record->quantity, $record->note ?: ''];
        }
        if ($record instanceof InventoryAuditLog) {
            $details = (array) $record->details;
            if ($type === 'update-log') {
                $action = [
                    'CREATE' => 'Thêm mới', 'UPDATE' => 'Cập nhật', 'IMPORT' => 'Import',
                    'INCREASE' => 'Tăng', 'DECREASE' => 'Giảm', 'ADJUST' => 'Điều chỉnh',
                    'MOVEMENT' => 'Biến động',
                ][$record->action] ?? $record->action;
                $quantity = abs((float) ($details['quantity'] ?? $details['change'] ?? 0));
                $location = $details['install_address'] ?? $details['location'] ?? '';
                return [
                    $number,
                    optional($record->created_at)->format('d/m/Y'),
                    $action,
                    $details['name'] ?? $details['asset_code'] ?? $details['code'] ?? 'Vật tư',
                    $quantity,
                    $details['before'] ?? '',
                    $details['after'] ?? '',
                    $location,
                    $record->user?->name ?? '',
                    $details['reason'] ?? $details['note'] ?? '',
                ];
            }
            $change = (float) ($details['change'] ?? $details['quantity'] ?? 0);
            if ($record->action === 'DECREASE') $change = -abs($change);
            $material = $details['name'] ?? 'Vật tư';
            // Mẫu tăng/giảm không có cột mã ở đầu: cột 0 là tên vật tư,
            // cột 1 là ĐVT, cột 2 là phân cấp, cột 3/4 là tăng/giảm.
            $values = array_fill(0, 19, '');
            $values[0] = $material;
            $values[1] = $details['unit'] ?? '';
            $values[2] = $details['grade'] ?? '';
            $values[3] = $change > 0 ? $change : '';
            $values[4] = $change < 0 ? abs($change) : '';
            $reason = mb_strtolower(trim((string) ($details['reason'] ?? $details['note'] ?? '')));
            $columnName = $this->increaseDecreaseReasonColumn((string) ($details['reason_code'] ?? ''), $reason, $change);
            $reasonColumns = [
                'tren_cap' => 5,
                'dieu_dong_den' => 6,
                'mua_sam' => 7,
                'kiem_ke_tang' => 8,
                'tang_phan_cap' => 9,
                'tang_khac' => 10,
                'tra_tren' => 11,
                'dieu_dong_di' => 12,
                'hao_hut' => 13,
                'hu_hong' => 14,
                'kiem_ke_giam' => 15,
                'thanh_ly' => 16,
                'giam_phan_cap' => 17,
                'giam_khac' => 18,
            ];
            $values[$reasonColumns[$columnName] ?? ($change >= 0 ? 10 : 18)] = $details['reason'] ?? $details['note'] ?? '';
            return $values;
        }
        if ($record instanceof InventoryWarehouseItem) {
            return [
                $number,
                $record->code ?: $record->material?->code,
                $record->name ?: $record->material?->name,
                $record->unit ?: $record->material?->unit,
                $record->quantity,
                $record->warehouse?->name ?: '',
                $record->warehouse?->location ?: '',
                $record->minimum_quantity,
                $record->note ?: '',
            ];
        }
        if ($type === 'public-assets') {
            $year = (int) request()->integer('year', now()->year);
            $depreciation = $this->publicAssetYearValues($record, $year);
            $category = $record->material?->category ?: $record->categoryRelation;
            return [
                $number,
                $record->asset_code ?: $record->material?->code,
                $record->name ?: $record->material?->name,
                $category?->parent?->name ?: '',
                $category?->name ?: (string) ($record->category ?: ''),
                $record->grade ?: '',
                $record->quantity,
                $record->unit ?: $record->material?->unit,
                $this->formatMoney((float) $record->unit_price),
                $this->formatMoney($this->publicAssetInitialValue($record)),
                $record->classroom?->name ?: '',
                $record->install_address ?: trim(($record->classroom?->building?->name ?: '').' / '.($record->classroom?->name ?: ''), ' /'),
                $record->supplier ?: '',
                $record->contract_invoice_number ?: '',
                $year,
                $depreciation['rate'] > 0 ? rtrim(rtrim(number_format($depreciation['rate'], 2, ',', '.'), '0'), ',').'%' : '',
                $this->formatMoney($depreciation['amount']),
                $this->formatMoney($depreciation['remaining_value']),
                ['NORMAL' => 'Bình thường', 'BROKEN' => 'Hỏng', 'REPAIRING' => 'Đang sửa', 'LIQUIDATED' => 'Đã thanh lý'][$record->status] ?? (string) $record->status,
                $record->note ?: '',
            ];
        }
        if ($type === 'warehouse') {
            $status = $record->status === 'BROKEN' ? 'Hỏng' : ($record->status === 'REPAIRING' ? 'Đang sửa chữa' : '');
            $base = [
                $number,
                $record->asset_code,
                $record->name,
                $record->unit ?: $record->material?->unit,
                $record->grade ?: 1,
                $record->quantity,
                $record->classroom?->building?->name ?: 'Kho vật tư',
            ];

            return $tableIndex === 2
                ? array_merge($base, [$record->note ?: '', optional($record->broken_at)->format('d/m/Y') ?: '', $status])
                : array_merge($base, [$record->classroom?->name ?: 'Kho vật tư']);
        }

        return [$record->asset_code, $record->name, $record->unit ?: $record->material?->unit, $record->grade ?: 1, $record->quantity, $record->classroom?->building?->name ?: 'Kho vật tư', $record->classroom?->name ?: 'Kho vật tư', $record->status === 'BROKEN' ? 'Hỏng' : ($record->status === 'REPAIRING' ? 'Đang sửa chữa' : '')];
    }

    private function variableRowValues(mixed $record, string $type, int $number): array
    {
        $row = $this->emptyVariableRow();
        $row['stt'] = (string) $number;
        if ($record instanceof InventoryTransfer) {
            $asset = $record->asset;
            $material = $record->material;
            $row['ma_vat_tu'] = (string) ($asset?->asset_code ?: $material?->code ?: '');
            $row['ten_vat_tu'] = (string) ($asset?->name ?: $material?->name ?: '');
            $row['don_vi_tinh'] = (string) ($asset?->unit ?: $material?->unit ?: '');
            $row['so_luong'] = (string) ($record->quantity ?: $asset?->quantity ?: 1);
            $row['phan_cap'] = (string) ($asset?->grade ?: '');
            $row['trang_thai'] = $record->status;
            $row['phong'] = (string) ($type === 'recall' ? $record->fromClassroom?->name : $record->toClassroom?->name);
            $row['toa_nha'] = '';
            $row['don_vi_quan_ly'] = (string) ($type === 'recall' ? $record->fromClassroom?->managingUnit?->name : $record->toClassroom?->managingUnit?->name);
            $row['ly_do'] = (string) ($record->reason ?: $record->general_note ?: '');
            $row['ghi_chu'] = (string) ($record->general_note ?: '');
            return $row;
        }
        if ($record instanceof InventoryMovement) {
            $row['ngay_du_lieu'] = optional($record->created_at)->format('d/m/Y');
            $row['ma_vat_tu'] = (string) ($record->material?->code ?: '');
            $row['ten_vat_tu'] = (string) ($record->material?->name ?: '');
            $row['nganh'] = (string) ($record->material?->category?->parent?->name ?: '');
            $row['loai_vat_tu'] = (string) ($record->material?->category?->name ?: '');
            $row['don_vi_tinh'] = (string) ($record->material?->unit ?: '');
            $row['so_luong'] = (string) $record->quantity;
            $row['loai_bien_dong'] = ['IN' => 'Tăng', 'OUT' => 'Giảm', 'ADJUST' => 'Điều chỉnh'][$record->type] ?? $record->type;
            $row['ghi_chu'] = (string) ($record->note ?: '');
            return $row;
        }
        if ($record instanceof InventoryAuditLog) {
            if (in_array($type, ['increase-decrease', 'xlsx-public-assets-change'], true)) {
                return array_merge($row, $this->increaseDecreaseRow($record));
            }
            $details = (array) $record->details;
            $row['ngay_du_lieu'] = optional($record->created_at)->format('d/m/Y');
            $row['ma_vat_tu'] = (string) ($details['asset_code'] ?? $details['code'] ?? '');
            $row['ten_vat_tu'] = (string) ($details['name'] ?? 'Vật tư');
            $row['don_vi_tinh'] = (string) ($details['unit'] ?? '');
            $row['so_luong'] = (string) abs((float) ($details['quantity'] ?? $details['change'] ?? 0));
            $row['phan_cap'] = (string) ($details['grade'] ?? '');
            $row['loai_bien_dong'] = ['CREATE' => 'Thêm mới', 'UPDATE' => 'Cập nhật', 'IMPORT' => 'Import', 'INCREASE' => 'Tăng', 'DECREASE' => 'Giảm', 'ADJUST' => 'Điều chỉnh', 'MOVEMENT' => 'Biến động'][$record->action] ?? $record->action;
            $row['truoc'] = (string) ($details['before'] ?? '');
            $row['sau'] = (string) ($details['after'] ?? '');
            $row['vi_tri'] = (string) ($details['install_address'] ?? $details['location'] ?? '');
            $row['nguoi_thuc_hien'] = (string) ($record->user?->name ?? '');
            $row['ly_do'] = (string) ($details['reason'] ?? $details['note'] ?? '');
            $row['ghi_chu'] = $row['ly_do'];
            return $row;
        }
        if ($record instanceof InventoryWarehouseItem) {
            $row['ma_vat_tu'] = (string) ($record->code ?: $record->material?->code ?: '');
            $row['ten_vat_tu'] = (string) ($record->name ?: $record->material?->name ?: '');
            $row['nganh'] = (string) ($record->material?->category?->parent?->name ?: '');
            $row['loai_vat_tu'] = (string) ($record->material?->category?->name ?: '');
            $row['don_vi_tinh'] = (string) ($record->unit ?: $record->material?->unit ?: '');
            $row['so_luong'] = (string) $record->quantity;
            $row['kho'] = (string) ($record->warehouse?->name ?: '');
            $row['vi_tri'] = (string) ($record->warehouse?->location ?: '');
            $row['ton_toi_thieu'] = (string) $record->minimum_quantity;
            $row['ghi_chu'] = (string) ($record->note ?: '');
            return $row;
        }
        $row['ma_vat_tu'] = (string) ($record->asset_code ?: $record->material?->code ?: '');
        $row['ten_vat_tu'] = (string) ($record->name ?: $record->material?->name ?: '');
        $row['nganh'] = (string) ($record->material?->category?->parent?->name ?: '');
        $row['loai_vat_tu'] = (string) ($record->material?->category?->name ?: '');
        if (in_array($type, ['public-assets', 'xlsx-public-depreciation-detail', 'xlsx-public-assets-current', 'xlsx-public-assets-by-unit'], true)) {
            $year = (int) request()->integer('year', now()->year);
            $depreciation = $this->publicAssetYearValues($record, $year);
            $category = $record->material?->category ?: $record->categoryRelation;
            $row['ma_tai_san'] = (string) ($record->asset_code ?: $record->material?->code ?: '');
            $row['ten_tai_san'] = (string) ($record->name ?: $record->material?->name ?: '');
            $row['loai_tai_san'] = (string) ($category?->name ?: $record->category ?: '');
            $row['nganh'] = (string) ($category?->parent?->name ?: '');
            $row['don_gia'] = $this->formatMoney((float) $record->unit_price);
            $row['thanh_tien'] = $this->formatMoney($this->publicAssetInitialValue($record));
            $row['dia_chi_lap_dat'] = (string) ($record->install_address ?: '');
            $row['don_vi_cung_cap'] = (string) ($record->supplier ?: '');
            $row['hop_dong_hoa_don'] = (string) ($record->contract_invoice_number ?: '');
            $row['nam_khau_hao'] = (string) $year;
            $row['ty_le_khau_hao'] = $depreciation['rate'] > 0 ? rtrim(rtrim(number_format($depreciation['rate'], 2, ',', '.'), '0'), ',').'%' : '';
            $row['tien_khau_hao'] = $this->formatMoney($depreciation['amount']);
            $row['gia_tri_con_lai'] = $this->formatMoney($depreciation['remaining_value']);
            $row['so_luong_su_dung_duoc'] = in_array($record->status, ['BROKEN', 'LIQUIDATED'], true) ? '' : (string) $record->quantity;
            $row['so_luong_hong'] = in_array($record->status, ['BROKEN', 'REPAIRING'], true) ? (string) $record->quantity : '';
        }
        $row['don_vi_tinh'] = (string) ($record->unit ?: $record->material?->unit ?: '');
        $row['so_luong'] = (string) $record->quantity;
        $row['phan_cap'] = (string) ($record->grade ?: '');
        $row['trang_thai'] = ['NORMAL' => 'Bình thường', 'BROKEN' => 'Hỏng', 'REPAIRING' => 'Đang sửa', 'LIQUIDATED' => 'Đã thanh lý'][$record->status] ?? (string) $record->status;
        $row['toa_nha'] = (string) ($record->classroom?->building?->name ?: 'Kho vật tư');
        $row['phong'] = (string) ($record->classroom?->name ?: 'Kho vật tư');
        $row['don_vi_quan_ly'] = (string) ($record->classroom?->managingUnit?->name ?: $record->holdingUnit?->name ?: '');
        $row['vi_tri'] = trim($row['toa_nha'].' / '.$row['phong'], ' /');
        $row['ngay_hu'] = optional($record->broken_at)->format('d/m/Y') ?: '';
        $row['ngay_hong'] = $row['ngay_hu'];
        $row['ly_do'] = (string) ($record->note ?: '');
        $row['ly_do_hong'] = $row['ly_do'];
        $row['ghi_chu'] = (string) ($record->note ?: '');
        return $row;
    }

    private function emptyVariableRow(): array
    {
        return [
            'stt' => '',
            'ngay_du_lieu' => '',
            'ma_vat_tu' => '',
            'ten_vat_tu' => '',
            'nganh' => '',
            'loai_vat_tu' => '',
            'don_vi_tinh' => '',
            'so_luong' => '',
            'phan_cap' => '',
            'so_luong_tang' => '',
            'so_luong_giam' => '',
            'tren_cap' => '',
            'dieu_dong_den' => '',
            'mua_sam' => '',
            'kiem_ke_tang' => '',
            'tang_phan_cap' => '',
            'tang_khac' => '',
            'tra_tren' => '',
            'dieu_dong_di' => '',
            'hao_hut' => '',
            'hu_hong' => '',
            'kiem_ke_giam' => '',
            'thanh_ly' => '',
            'giam_phan_cap' => '',
            'giam_khac' => '',
            'trang_thai' => '',
            'toa_nha' => '',
            'phong' => '',
            'don_vi_quan_ly' => '',
            'kho' => '',
            'vi_tri' => '',
            'ton_toi_thieu' => '',
            'loai_bien_dong' => '',
            'truoc' => '',
            'sau' => '',
            'nguoi_thuc_hien' => '',
            'ngay_hu' => '',
            'ngay_hong' => '',
            'ly_do' => '',
            'ly_do_hong' => '',
            'ma_tai_san' => '',
            'ten_tai_san' => '',
            'loai_tai_san' => '',
            'don_gia' => '',
            'thanh_tien' => '',
            'dia_chi_lap_dat' => '',
            'don_vi_cung_cap' => '',
            'hop_dong_hoa_don' => '',
            'nam_khau_hao' => '',
            'ty_le_khau_hao' => '',
            'tien_khau_hao' => '',
            'gia_tri_con_lai' => '',
            'so_luong_su_dung_duoc' => '',
            'so_luong_hong' => '',
            'ghi_chu' => '',
        ];
    }

    private function publicAssetInitialValue(InventoryAsset $asset): float
    {
        $value = (float) $asset->total_amount;
        if ($value <= 0) {
            $value = max(10000000, (float) $asset->unit_price) * (float) $asset->quantity;
        }
        return $value;
    }

    private function publicAssetYearValues(InventoryAsset $asset, int $year): array
    {
        $initialValue = $this->publicAssetInitialValue($asset);
        $record = $asset->depreciationYears
            ?->where('year', '<=', $year)
            ->sortByDesc('year')
            ->first();
        $rate = (float) ($record?->depreciation_rate ?? 0);
        $amount = min($initialValue, $initialValue * $rate / 100);

        return [
            'rate' => $rate,
            'amount' => $amount,
            'remaining_value' => max(0, $initialValue - $amount),
        ];
    }

    private function formatMoney(float $value): string
    {
        return $value > 0 ? number_format($value, 0, ',', '.') : '';
    }

    private function createPublicAssetReportTemplate(string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(9);
        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 23811,
            'pageSizeH' => 16838,
            'marginTop' => 650,
            'marginBottom' => 650,
            'marginLeft' => 650,
            'marginRight' => 650,
        ]);
        $normal = ['name' => 'Times New Roman', 'size' => 9];
        $bold = ['name' => 'Times New Roman', 'size' => 9, 'bold' => true];
        $title = ['name' => 'Times New Roman', 'size' => 14, 'bold' => true];
        $center = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];
        $left = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT];

        $section->addText('TRƯỜNG CAO ĐẲNG HẬU CẦN 2', $bold, $center);
        $section->addText('DANH SÁCH TÀI SẢN CÔNG', $title, $center);
        $section->addText('Số liệu đến ngày ${ngay_bao_cao} - Năm thống kê: ${nam_thong_ke}', ['italic' => true] + $normal, $center);
        $section->addText('Tổng số tài sản: ${tong_tai_san} | Tổng số lượng: ${tong_so_luong} | Tổng thành tiền: ${tong_thanh_tien} | Tổng giá trị còn lại: ${tong_gia_tri_con_lai}', $normal, $left);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '222222', 'cellMargin' => 45, 'width' => 100 * 50, 'unit' => 'pct']);
        $headers = ['STT','Mã TS','Tên tài sản','Ngành','Loại','Cấp','SL','ĐVT','Đơn giá','Thành tiền','Phòng','Địa chỉ lắp đặt','Đơn vị cung cấp','HĐ/Hóa đơn','Năm KH','Tỷ lệ KH','Tiền KH','Giá trị còn lại','Trạng thái','Ghi chú'];
        $variables = ['${stt}','${ma_tai_san}','${ten_tai_san}','${nganh}','${loai_tai_san}','${phan_cap}','${so_luong}','${don_vi_tinh}','${don_gia}','${thanh_tien}','${phong}','${dia_chi_lap_dat}','${don_vi_cung_cap}','${hop_dong_hoa_don}','${nam_khau_hao}','${ty_le_khau_hao}','${tien_khau_hao}','${gia_tri_con_lai}','${trang_thai}','${ghi_chu}'];
        $widths = [600,1100,1800,1100,1200,650,650,650,1200,1300,1100,1700,1400,1300,800,900,1200,1300,1000,1500];
        foreach ([$headers, $variables, ['', 'TỔNG CỘNG', '', '', '', '', '${tong_so_luong}', '', '', '${tong_thanh_tien}', '', '', '', '', '', '', '${tong_tien_khau_hao}', '${tong_gia_tri_con_lai}', '', '']] as $rowIndex => $row) {
            $table->addRow($rowIndex === 0 ? 500 : 420);
            foreach ($row as $index => $value) {
                $table->addCell($widths[$index])->addText($value, $rowIndex === 1 ? $normal : $bold, $index === 1 ? $left : $center);
            }
        }

        (new \PhpOffice\PhpWord\Writer\Word2007($word))->save($path);
    }

    private function writeReportZip(string $template, string $documentXml, string $filename): mixed
    {
        $output = storage_path('app/'.pathinfo($filename, PATHINFO_FILENAME).'-'.now()->format('YmdHis').'.docx');
        $source = new \ZipArchive();
        $target = new \ZipArchive();
        abort_unless($source->open($template) === true && $target->open($output, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Không tạo được file báo cáo.');
        for ($i = 0; $i < $source->numFiles; $i++) {
            $name = $source->getNameIndex($i);
            $target->addFromString($name, $name === 'word/document.xml' ? $documentXml : $source->getFromIndex($i));
        }
        $source->close();
        $target->close();
        return response()->download($output, $filename)->deleteFileAfterSend(true);
    }

    private function fillPositionTemplate(Request $request, string $template, string $filename, string $type): mixed
    {
        $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'material.category.parent', 'holdingUnit'])
            ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->orderBy('name')->get();

        $xml = new \DOMDocument();
        $zip = new \ZipArchive();
        abort_unless($zip->open($template) === true, 500, 'Không mở được mẫu báo cáo.');
        $xml->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables = $xpath->query('//w:tbl');
        $table = $tables->item(1);
        abort_unless($table, 500, 'Mẫu báo cáo không có bảng vật tư.');
        $rows = $xpath->query('./w:tr', $table);
        $groupTemplate = ($this->findTableRowContaining($xpath, $rows, 'nganh') ?: $rows->item(min(3, max(0, $rows->length - 1))))?->cloneNode(true);
        $categoryTemplate = ($this->findTableRowContaining($xpath, $rows, 'loai_vat_tu') ?: $rows->item(min(4, max(0, $rows->length - 1))))?->cloneNode(true);
        $itemTemplate = ($this->findTableRowContaining($xpath, $rows, 'ma_vat_tu') ?: $this->findTableRowContaining($xpath, $rows, 'ten_vat_tu') ?: $rows->item(min(5, max(0, $rows->length - 1))))?->cloneNode(true);
        $roomTemplate = ($this->findTableRowContaining($xpath, $rows, 'ma_phong') ?: $this->findTableRowContaining($xpath, $rows, 'phong') ?: $this->findTableRowContaining($xpath, $rows, 'vi_tri'))?->cloneNode(true);
        $totalTemplate = ($this->findTableRowContaining($xpath, $rows, 'TỔNG CỘNG') ?: $rows->item($rows->length - 1))?->cloneNode(true);
        abort_unless($groupTemplate && $categoryTemplate && $itemTemplate && $totalTemplate, 500, 'Mẫu báo cáo thiếu dòng dữ liệu để đổ thông tin vật tư.');
        $fixedColumns = $this->positionFixedColumnCount($xpath, $rows->item(0), $rows->item(1));

        $unitColumns = $this->positionUnitColumns($assets);
        $fixedWidths = $this->positionFixedColumnWidths($fixedColumns);
        $unitColumnWidth = $this->positionUnitColumnWidth($unitColumns->count(), $fixedColumns);
        $tableWidth = array_sum($fixedWidths) + ($unitColumnWidth * $unitColumns->count());
        $this->setDocumentLandscapeWidth($xml, $xpath, $request, $tableWidth + 1800);
        $this->setTableFixedWidth($xml, $xpath, $table, $tableWidth);
        $this->setUnitHeaderGroupSpan($xml, $xpath, $rows->item(0), $fixedColumns, $unitColumns->count());
        $this->resizeRowCells($xml, $xpath, $rows->item(1), $fixedColumns + $unitColumns->count());
        $this->setRowCellWidths($xml, $xpath, $rows->item(0), array_merge($fixedWidths, [$unitColumnWidth * $unitColumns->count()]));
        $this->setRowCellWidths($xml, $xpath, $rows->item(1), array_merge($fixedWidths, array_fill(0, $unitColumns->count(), $unitColumnWidth)));
        foreach ([$groupTemplate, $categoryTemplate, $itemTemplate, $roomTemplate, $totalTemplate] as $templateRow) {
            if ($templateRow) {
                $this->resizeRowCells($xml, $xpath, $templateRow, $fixedColumns + $unitColumns->count());
                $this->setRowCellWidths($xml, $xpath, $templateRow, array_merge($fixedWidths, array_fill(0, $unitColumns->count(), $unitColumnWidth)));
            }
        }
        $this->setUnitHeaders($xml, $xpath, $rows->item(1), $unitColumns, $fixedColumns);

        for ($i = $rows->length - 1; $i >= 2; $i--) {
            $table->removeChild($rows->item($i));
        }

        if (in_array($type, ['position', 'using-position'], true)) {
            $this->setTemplateRow($xml, $this->boldRow($xml, $groupTemplate->cloneNode(true)), array_merge($this->positionRowValues($fixedColumns, [null, 'VẬT TƯ, TRANG BỊ KỸ THUẬT', null, null, null]), array_fill(0, $unitColumns->count(), '')), $table);
        }

        // Cấu trúc báo cáo theo vị trí: ngành -> loại -> vật tư -> mã phòng đang lắp đặt vật tư.
        $industries = $assets->groupBy(fn ($asset) => $asset->material?->category?->parent?->name ?: 'Chưa xác định ngành');
        $industryNo = 0;
        $buildingNames = $assets->map(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư')->unique()->values();
        $buildingCount = $assets->map(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư')->unique()->count();
        foreach ($industries as $industry => $industryAssets) {
            $industryNo++;
            $industryCategory = $industryAssets->first()?->material?->category?->parent;
            $this->setTemplateRow($xml, $groupTemplate->cloneNode(true), array_merge($this->positionRowValues($fixedColumns, [$industryCategory?->code, $industry, null, null, $industryAssets->sum('quantity')]), $this->unitQuantities($industryAssets, $unitColumns)), $table);
            $types = $industryAssets->groupBy(fn ($asset) => $asset->material?->category?->name ?: 'Chưa xác định loại');
            $typeNo = 0;
            foreach ($types as $materialTypeName => $typeAssets) {
                $typeNo++;
                $typeCategory = $typeAssets->first()?->material?->category;
                $this->setTemplateRow($xml, $categoryTemplate->cloneNode(true), array_merge($this->positionRowValues($fixedColumns, [$typeCategory?->code, $materialTypeName, null, null, $typeAssets->sum('quantity')]), $this->unitQuantities($typeAssets, $unitColumns)), $table);
                $materials = $typeAssets->groupBy(fn ($asset) => ($asset->material_id ?: $asset->asset_code ?: $asset->name).'|grade:'.($asset->grade ?: ''));
                foreach ($materials as $materialAssets) {
                    $first = $materialAssets->first();
                    $total = $materialAssets->sum('quantity');
                    $material = $first->material;
                    $itemValues = $this->positionRowValues($fixedColumns, [
                        $material?->code ?: $first->asset_code,
                        $material?->name ?: $first->name,
                        $material?->unit ?: $first->unit,
                        $first->grade ?: '',
                        $total,
                    ]);
                    $itemValues = array_merge($itemValues, $this->unitQuantities($materialAssets, $unitColumns));
                    $this->setTemplateRow($xml, $this->boldRow($xml, $itemTemplate->cloneNode(true)), $itemValues, $table);
                    if ($roomTemplate && in_array($type, ['position', 'using-position'], true)) {
                        $rooms = $materialAssets->groupBy(fn ($asset) => $asset->classroom?->id ?: 'warehouse');
                        foreach ($rooms as $roomAssets) {
                            $room = $roomAssets->first()->classroom;
                            $roomCode = $room ? ((string) ($room->code ?: $room->name)) : 'KHO';
                            $roomRow = $roomTemplate->cloneNode(true);
                            $this->rightAlignCell($xml, $roomRow, 1);
                            $this->setTemplateRow($xml, $roomRow, array_merge($this->positionRowValues($fixedColumns, [null, $roomCode, null, null, $roomAssets->sum('quantity')]), $this->unitQuantities($roomAssets, $unitColumns)), $table);
                        }
                    } elseif ($roomTemplate) {
                        $buildings = $materialAssets->groupBy(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư');
                        foreach ($buildings as $building => $buildingAssets) {
                            $buildingNumber = $buildingNames->search($building) + 1;
                            $buildingLabel = in_array($type, ['position', 'using-position'], true) || $buildingCount > 1 ? $buildingNumber.'. '.$building : $building;
                            $buildingValues = $this->positionRowValues($fixedColumns, [null, $buildingLabel, null, null, $buildingAssets->sum('quantity')]);
                            $this->setTemplateRow($xml, $roomTemplate->cloneNode(true), array_merge($buildingValues, $this->unitQuantities($buildingAssets, $unitColumns)), $table);
                            $rooms = $buildingAssets->groupBy(fn ($asset) => $asset->classroom?->id ?: 'warehouse');
                            foreach ($rooms as $roomAssets) {
                                $roomAsset = $roomAssets->first();
                                $room = $roomAsset->classroom;
                                $roomName = $room ? ((string) ($room->code ?: $room->name)) : 'Kho vật tư';
                                $roomValues = [
                                    null,
                                    $roomName,
                                    null,
                                    null,
                                    $roomAssets->sum('quantity'),
                                ];
                                $roomRow = $roomTemplate->cloneNode(true);
                                $this->rightAlignCell($xml, $roomRow, 1);
                                $this->setTemplateRow($xml, $roomRow, array_merge($this->positionRowValues($fixedColumns, $roomValues), $this->unitQuantities($roomAssets, $unitColumns)), $table);
                            }
                        }
                    }
                }
            }
        }

        $this->setTemplateRow($xml, $totalTemplate, array_merge($this->positionRowValues($fixedColumns, [null, 'TỔNG CỘNG', null, null, $assets->sum('quantity')]), $this->unitQuantities($assets, $unitColumns)), $table);
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains($text->nodeValue, 'Vị trí quản lý sử dụng (chi tiết theo phòng)')) $text->nodeValue = '';
        }
        $this->replaceReportDate($xpath, $request);
        $this->replaceUnitReportTitle($xpath, $request);
        $this->replaceScalarTemplateValues($xpath, $request, $type);
        $this->applyRequestedPaperSize($xml, $xpath, $request, 'landscape');
        $documentXml = $xml->saveXML();
        $zip->close();

        $output = storage_path('app/'.pathinfo($filename, PATHINFO_FILENAME).'-'.now()->format('YmdHis').'.docx');
        $source = new \ZipArchive();
        $target = new \ZipArchive();
        abort_unless($source->open($template) === true && $target->open($output, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Không tạo được file báo cáo.');
        for ($i = 0; $i < $source->numFiles; $i++) {
            $name = $source->getNameIndex($i);
            $target->addFromString($name, $name === 'word/document.xml' ? $documentXml : $source->getFromIndex($i));
        }
        $source->close();
        $target->close();

        return response()->download($output, $filename)->deleteFileAfterSend(true);
    }

    private function findTableRowContaining(\DOMXPath $xpath, \DOMNodeList $rows, string $needle): ?\DOMNode
    {
        for ($i = 0; $i < $rows->length; $i++) {
            $text = '';
            foreach ($xpath->query('.//w:t', $rows->item($i)) as $node) {
                $text .= $node->nodeValue;
            }
            if (str_contains($text, $needle)) {
                return $rows->item($i);
            }
        }

        return null;
    }

    private function findTableRowIndexContaining(\DOMXPath $xpath, \DOMNodeList $rows, string $needle): ?int
    {
        for ($i = 0; $i < $rows->length; $i++) {
            $text = '';
            foreach ($xpath->query('.//w:t', $rows->item($i)) as $node) {
                $text .= $node->nodeValue;
            }
            if (str_contains($text, $needle)) return $i;
        }

        return null;
    }

    private function replaceScalarTemplateValues(\DOMXPath $xpath, Request $request, string $type): void
    {
        $today = now();
        $from = $request->filled('from') ? date('d/m/Y', strtotime($request->input('from'))) : '';
        $to = $request->filled('to') ? date('d/m/Y', strtotime($request->input('to'))) : $today->format('d/m/Y');
        $values = [
            'ngay_bao_cao' => $to,
            'ngay' => $today->format('d'),
            'thang' => $today->format('m'),
            'nam' => $today->format('Y'),
            'nam_hien_tai' => $today->format('Y'),
            'nam_thong_ke' => (string) $request->integer('year', now()->year),
            'tu_ngay' => $from,
            'den_ngay' => $to,
            'pham_vi' => match ($type) {
                'position', 'using-position' => 'Theo vị trí lắp đặt',
                'total-position', 'using-total' => 'Tổng hợp toàn bộ phòng/tòa',
                default => 'Toàn bộ dữ liệu',
            },
            'so_van_ban' => '',
            'noi_nhan' => '',
            'chuc_danh_ky' => '',
            'nguoi_ky' => '',
        ];
        if ($type === 'warehouse') {
            $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'holdingUnit'])
                ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
                ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
                ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
                ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
                ->get();
            $stable = $assets->whereNotIn('status', ['BROKEN', 'REPAIRING']);
            $broken = $assets->whereIn('status', ['BROKEN', 'REPAIRING']);
            $values += [
                'tong_so_luong_on_dinh' => $stable->sum('quantity'),
                'so_luong_vat_tu_on_dinh' => $stable->sum('quantity'),
                'so_dong_on_dinh' => $stable->count(),
                'tong_so_luong_hu_hai' => $broken->sum('quantity'),
                'tong_so_luong_hu_hong' => $broken->sum('quantity'),
                'so_luong_vat_tu_hu_hai' => $broken->sum('quantity'),
                'so_luong_vat_tu_hu_hong' => $broken->sum('quantity'),
                'so_dong_hu_hai' => $broken->count(),
                'so_dong_hu_hong' => $broken->count(),
            ];
        } elseif ($type === 'public-assets') {
            $reportYear = (int) $request->integer('year', now()->year);
            $assets = InventoryAsset::with(['depreciationYears'])
                ->where('management_type', 'ASSET')
                ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
                ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
                ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
                ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
                ->get();
            $values += [
                'tong_tai_san' => $assets->count(),
                'tong_so_luong' => $assets->sum('quantity'),
                'tong_thanh_tien' => $this->formatMoney($assets->sum(fn ($asset) => $this->publicAssetInitialValue($asset))),
                'tong_tien_khau_hao' => $this->formatMoney($assets->sum(fn ($asset) => $this->publicAssetYearValues($asset, $reportYear)['amount'])),
                'tong_gia_tri_con_lai' => $this->formatMoney($assets->sum(fn ($asset) => $this->publicAssetYearValues($asset, $reportYear)['remaining_value'])),
            ];
        } elseif ($type === 'system-warehouse') {
            $items = InventoryWarehouseItem::with(['warehouse', 'material'])
                ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
                ->get();
            $values += [
                'tong_so_luong_kho_vat_tu' => $items->sum('quantity'),
                'so_dong_kho_vat_tu' => $items->count(),
                'tong_so_kho' => $items->pluck('warehouse_id')->filter()->unique()->count(),
            ];
        }

        foreach ($xpath->query('//w:p|//w:tc') as $container) {
            $nodes = $xpath->query('.//w:t', $container);
            if (!$nodes->length) continue;

            $text = '';
            foreach ($nodes as $node) {
                $text .= $node->nodeValue;
            }

            $replaced = $text;
            foreach ($values as $key => $value) {
                $replaced = str_replace('${'.$key.'}', (string) $value, $replaced);
            }

            if ($replaced === $text) continue;
            $nodes->item(0)->nodeValue = $replaced;
            for ($i = 1; $i < $nodes->length; $i++) {
                $nodes->item($i)->nodeValue = '';
            }
        }
    }

    private function setTemplateRow(\DOMDocument $xml, \DOMNode $row, array $values, \DOMNode $table): void
    {
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $cells = $xpath->query('./w:tc', $row);
        foreach ($cells as $index => $cell) {
            $textNodes = $xpath->query('.//w:t', $cell);
            $rawValue = $values[$index] ?? '';
            // Không in số 0 vào các ô số lượng của bất kỳ mẫu Word nào.
            $value = is_numeric($rawValue) && (float) $rawValue === 0.0 ? '' : (string) $rawValue;
            if ($textNodes->length) {
                $textNodes->item(0)->nodeValue = $value;
                for ($i = 1; $i < $textNodes->length; $i++) {
                    $textNodes->item($i)->nodeValue = '';
                }
            }
        }
        $table->appendChild($row);
    }

    private function boldRow(\DOMDocument $xml, \DOMNode $row): \DOMNode
    {
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($xpath->query('.//w:rPr', $row) as $properties) {
            if (!$xpath->query('./w:b', $properties)->length) $properties->appendChild($xml->createElement('w:b'));
        }
        return $row;
    }

    private function unitQuantities($assets, $units): array
    {
        $values = [];
        $allocated = 0;
        foreach ($units as $unit) {
            if (!$unit) {
                $values[] = '';
                continue;
            }

            if ($this->isWarehouseUnit($unit)) {
                $quantity = max(0, (int) $assets->sum('quantity') - $allocated);
                $values[] = $quantity > 0 ? $quantity : '';
                continue;
            }

            $quantity = $assets->filter(fn ($asset) => (int) ($asset->classroom?->managing_unit_id ?: $asset->holding_unit_id) === (int) $unit->id)->sum('quantity');
            $allocated += (int) $quantity;
            $values[] = $quantity > 0 ? $quantity : '';
        }
        return $values;
    }

    private function positionUnitColumns($assets)
    {
        $units = $assets->map(fn ($asset) => $asset->classroom?->managingUnit ?: $asset->holdingUnit)
            ->filter()
            ->reject(fn ($unit) => $this->isWarehouseLabel($unit->abbreviation ?: $unit->code ?: $unit->name))
            ->unique('id')
            ->sortBy('id')
            ->values();

        return $units->push($this->warehouseUnit())->values();
    }

    private function warehouseUnit(): object
    {
        return (object) ['id' => '__warehouse', 'name' => 'KHO', 'code' => 'KHO', 'abbreviation' => 'KHO'];
    }

    private function isWarehouseUnit($unit): bool
    {
        return (string) ($unit->id ?? '') === '__warehouse' || $this->isWarehouseLabel($unit->abbreviation ?? $unit->code ?? $unit->name ?? '');
    }

    private function isWarehouseLabel(?string $value): bool
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8') === 'KHO';
    }

    private function resizeRowCells(\DOMDocument $xml, \DOMXPath $xpath, ?\DOMNode $row, int $targetCells): void
    {
        if (!$row) return;

        $cells = $xpath->query('./w:tc', $row);
        while ($cells->length > $targetCells) {
            $row->removeChild($cells->item($cells->length - 1));
            $cells = $xpath->query('./w:tc', $row);
        }

        while ($cells->length < $targetCells && $cells->length > 0) {
            $clone = $cells->item($cells->length - 1)->cloneNode(true);
            foreach ($xpath->query('.//w:t', $clone) as $index => $text) {
                $text->nodeValue = $index === 0 ? '' : '';
            }
            $row->appendChild($clone);
            $cells = $xpath->query('./w:tc', $row);
        }
    }

    private function setUnitHeaderGroupSpan(\DOMDocument $xml, \DOMXPath $xpath, ?\DOMNode $row, int $fixedColumns, int $unitCount): void
    {
        if (!$row) return;
        $cells = $xpath->query('./w:tc', $row);
        $cell = $cells->item($fixedColumns);
        if (!$cell) return;

        $properties = $xpath->query('./w:tcPr', $cell)->item(0);
        if (!$properties) {
            $properties = $xml->createElement('w:tcPr');
            $cell->insertBefore($properties, $cell->firstChild);
        }

        $gridSpan = $xpath->query('./w:gridSpan', $properties)->item(0);
        if (!$gridSpan) {
            $gridSpan = $xml->createElement('w:gridSpan');
            $properties->appendChild($gridSpan);
        }
        $gridSpan->setAttribute('w:val', (string) max(1, $unitCount));
    }

    private function positionRowValues(int $fixedColumns, array $values): array
    {
        return $fixedColumns === 5
            ? array_slice(array_pad($values, 5, ''), 0, 5)
            : [$values[0] ?? null, $values[1] ?? null, $values[2] ?? null, $values[4] ?? ($values[3] ?? null)];
    }

    private function positionFixedColumnWidths(int $fixedColumns): array
    {
        return $fixedColumns === 5 ? self::POSITION_FIXED_WIDTHS_5 : self::POSITION_FIXED_WIDTHS_4;
    }

    private function positionUnitColumnWidth(int $unitCount, int $fixedColumns): int
    {
        return 360;
    }

    private function positionFixedColumnCount(\DOMXPath $xpath, ?\DOMNode $firstHeader, ?\DOMNode $secondHeader): int
    {
        $text = '';
        foreach ([$firstHeader, $secondHeader] as $row) {
            if (!$row) continue;
            foreach ($xpath->query('.//w:t', $row) as $node) $text .= ' '.$node->nodeValue;
        }
        return str_contains(mb_strtolower($text, 'UTF-8'), 'phâ') || str_contains($text, '${phan_cap}') ? 5 : 4;
    }

    private function setUnitHeaders(\DOMDocument $xml, \DOMXPath $xpath, ?\DOMNode $headerRow, $units, int $fixedColumns = 4): void
    {
        if (!$headerRow) return;
        $cells = $xpath->query('./w:tc', $headerRow);
        for ($index = $fixedColumns; $index < $cells->length; $index++) {
            $cell = $cells->item($index);
            if (!$cell) continue;
            $unit = $units[$index - $fixedColumns] ?? null;
            $texts = $xpath->query('.//w:t', $cell);
            if ($texts->length) {
                $texts->item(0)->nodeValue = $unit ? ($unit->abbreviation ?: $unit->code ?: $unit->name) : '';
                for ($i = 1; $i < $texts->length; $i++) $texts->item($i)->nodeValue = '';
            }
        }
    }

    private function setRowCellWidths(\DOMDocument $xml, \DOMXPath $xpath, ?\DOMNode $row, array $widths): void
    {
        if (!$row) return;

        foreach ($xpath->query('./w:tc', $row) as $index => $cell) {
            $properties = $xpath->query('./w:tcPr', $cell)->item(0);
            if (!$properties) {
                $properties = $xml->createElement('w:tcPr');
                $cell->insertBefore($properties, $cell->firstChild);
            }

            $width = $xpath->query('./w:tcW', $properties)->item(0);
            if (!$width) {
                $width = $xml->createElement('w:tcW');
                $properties->appendChild($width);
            }

            $width->setAttribute('w:w', (string) ($widths[$index] ?? 700));
            $width->setAttribute('w:type', 'dxa');
        }
    }

    private function setDocumentLandscapeWidth(\DOMDocument $xml, \DOMXPath $xpath, Request $request, int $contentWidth): void
    {
        $requestedSize = $this->requestedPaperSize($request);
        if ($requestedSize) {
            $dimensions = $this->paperDimensions($requestedSize);
            $orientation = $this->requestedPaperOrientation($request) ?: 'landscape';
            [$pageWidth, $pageHeight] = $this->orientedPaperDimensions($dimensions, $orientation);
        } else {
            $pageWidth = max(23811, $contentWidth);
            $pageHeight = 16838;
        }

        foreach ($xpath->query('//w:sectPr') as $sectionProperties) {
            $pageSize = $xpath->query('./w:pgSz', $sectionProperties)->item(0);
            if (!$pageSize) {
                $pageSize = $xml->createElement('w:pgSz');
                $sectionProperties->insertBefore($pageSize, $sectionProperties->firstChild);
            }

            $pageSize->setAttribute('w:w', (string) $pageWidth);
            $pageSize->setAttribute('w:h', (string) $pageHeight);
            if ($pageWidth > $pageHeight) {
                $pageSize->setAttribute('w:orient', 'landscape');
            } else {
                $pageSize->removeAttribute('w:orient');
            }
        }
    }

    private function applyRequestedPaperSize(\DOMDocument $xml, \DOMXPath $xpath, Request $request, string $defaultOrientation = 'portrait'): void
    {
        $requestedSize = $this->requestedPaperSize($request);
        if (!$requestedSize) return;

        $orientation = $this->requestedPaperOrientation($request) ?: $defaultOrientation;
        [$pageWidth, $pageHeight] = $this->orientedPaperDimensions($this->paperDimensions($requestedSize), $orientation);

        foreach ($xpath->query('//w:sectPr') as $sectionProperties) {
            $pageSize = $xpath->query('./w:pgSz', $sectionProperties)->item(0);
            if (!$pageSize) {
                $pageSize = $xml->createElement('w:pgSz');
                $sectionProperties->insertBefore($pageSize, $sectionProperties->firstChild);
            }

            $pageSize->setAttribute('w:w', (string) $pageWidth);
            $pageSize->setAttribute('w:h', (string) $pageHeight);
            if ($orientation === 'landscape') {
                $pageSize->setAttribute('w:orient', 'landscape');
            } else {
                $pageSize->removeAttribute('w:orient');
            }
        }
    }

    private function applyRequestedPaperSizeToDocx(Request $request, string $path): void
    {
        $requestedSize = $this->requestedPaperSize($request);
        if (!$requestedSize) return;

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return;

        $documentXml = $zip->getFromName('word/document.xml');
        if (!$documentXml) {
            $zip->close();
            return;
        }

        $xml = new \DOMDocument();
        $xml->loadXML($documentXml);
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $this->applyRequestedPaperSize($xml, $xpath, $request);
        $zip->addFromString('word/document.xml', $xml->saveXML());
        $zip->close();
    }

    private function requestedPaperSize(Request $request): ?string
    {
        $size = strtoupper((string) $request->input('paper_size', 'auto'));

        return in_array($size, ['A4', 'A3', 'A2'], true) ? $size : null;
    }

    private function requestedPaperOrientation(Request $request): ?string
    {
        $orientation = strtolower((string) $request->input('paper_orientation', 'auto'));

        return in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : null;
    }

    private function paperDimensions(string $paperSize): array
    {
        return match ($paperSize) {
            'A2' => [23811, 33676],
            'A3' => [16838, 23811],
            default => [11906, 16838],
        };
    }

    private function orientedPaperDimensions(array $dimensions, string $orientation): array
    {
        [$shortSide, $longSide] = $dimensions;

        return $orientation === 'landscape' ? [$longSide, $shortSide] : [$shortSide, $longSide];
    }

    private function setTableFixedWidth(\DOMDocument $xml, \DOMXPath $xpath, \DOMNode $table, int $width): void
    {
        $properties = $xpath->query('./w:tblPr', $table)->item(0);
        if (!$properties) {
            $properties = $xml->createElement('w:tblPr');
            $table->insertBefore($properties, $table->firstChild);
        }

        $tableWidth = $xpath->query('./w:tblW', $properties)->item(0);
        if (!$tableWidth) {
            $tableWidth = $xml->createElement('w:tblW');
            $properties->appendChild($tableWidth);
        }
        $tableWidth->setAttribute('w:w', (string) $width);
        $tableWidth->setAttribute('w:type', 'dxa');

        $layout = $xpath->query('./w:tblLayout', $properties)->item(0);
        if (!$layout) {
            $layout = $xml->createElement('w:tblLayout');
            $properties->appendChild($layout);
        }
        $layout->setAttribute('w:type', 'fixed');
    }

    private function rightAlignCell(\DOMDocument $xml, \DOMNode $row, int $cellIndex): void
    {
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $cell = $xpath->query('./w:tc', $row)->item($cellIndex);
        if (!$cell) return;
        foreach ($xpath->query('.//w:pPr', $cell) as $properties) {
            $justification = $xpath->query('./w:jc', $properties)->item(0);
            if (!$justification) {
                $justification = $xml->createElement('w:jc');
                $properties->appendChild($justification);
            }
            $justification->setAttribute('w:val', 'right');
        }
    }

    private function replaceReportDate(\DOMXPath $xpath, Request $request): void
    {
        $format = static function (?string $value): ?string {
            return $value ? date('d/m/Y', strtotime($value)) : null;
        };
        $from = $format($request->input('from'));
        $to = $format($request->input('to')) ?: now()->format('d/m/Y');
        $label = $from
            ? 'Số liệu từ ngày '.$from.' đến ngày '.$to
            : 'Số liệu đến ngày '.$to;
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains((string) $text->nodeValue, 'Số liệu')) {
                $text->nodeValue = preg_replace('/Số liệu.*$/u', $label, (string) $text->nodeValue);
            }
        }
    }

    private function replaceUnitReportTitle(\DOMXPath $xpath, Request $request): void
    {
        if ($request->input('report_type') !== 'unit' || !$request->filled('unit_id')) return;
        $unit = \Modules\Unit\Models\Unit::find($request->integer('unit_id'));
        if (!$unit) return;
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains((string) $text->nodeValue, 'Vị trí quản lý sử dụng')) {
                $text->nodeValue = 'Vị trí quản lý sử dụng (phòng: '.$unit->name.')';
            }
        }
    }

    private function replaceRepairSummary(\DOMXPath $xpath, $rowsData): void
    {
        $quantity = $rowsData->sum(fn ($asset) => (float) $asset->quantity);
        $count = $rowsData->count();
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains((string) $text->nodeValue, 'Tổng số lượng:')) {
                if ($count === 0) {
                    $text->nodeValue = '';
                    continue;
                }
                $text->nodeValue = preg_replace(
                    '/Tổng số lượng:\s*[^;]+;\s*số dòng:\s*\d+\.?/u',
                    'Tổng số lượng: '.$quantity.'; số dòng: '.$count.'.',
                    (string) $text->nodeValue
                );
            }
        }
    }

    private function replaceUpdateSummary(\DOMXPath $xpath, $rowsData): void
    {
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains((string) $text->nodeValue, 'Tổng số giao dịch:')) {
                $text->nodeValue = 'Tổng số giao dịch: '.$rowsData->count().'.';
            }
        }
    }

    private function replaceTransferDocument(\DOMXPath $xpath, InventoryTransfer $transfer, string $type): void
    {
        $sourceRoom = $transfer->fromClassroom;
        $targetRoom = $transfer->toClassroom;
        $sourceUnit = $sourceRoom?->managingUnit?->name ?: ($sourceRoom?->name ?: 'đơn vị giao');
        $receiverUnit = $type === 'recall'
            ? ($sourceRoom?->managingUnit?->name ?: ($sourceRoom?->name ?: 'đơn vị nhận'))
            : ($targetRoom?->managingUnit?->name ?: ($targetRoom?->name ?: 'đơn vị nhận'));
        $callingUnit = $transfer->performing_unit ?: 'đơn vị gọi giao';
        $action = $type === 'recall' ? 'thu hồi' : 'điều động';
        $verb = $type === 'recall' ? 'Thu hồi' : 'Điều động';
        $date = ($transfer->decision_date ?: $transfer->performed_at ?: $transfer->created_at)?->format('d/m/Y');
        $dateParts = $date ? explode('/', $date) : [];
        $dateText = count($dateParts) === 3 ? 'Thành phố Hồ Chí Minh, ngày '.$dateParts[0].' tháng '.$dateParts[1].' năm '.$dateParts[2] : '';
        $requesting = trim((string) $transfer->requesting_unit);

        foreach ($xpath->query('//w:t') as $text) {
            $value = (string) $text->nodeValue;
            $replacement = null;
            if (str_starts_with(trim($value), 'Số:')) $replacement = 'Số: '.($transfer->decision_number ?: '……/QĐ');
            elseif (str_contains($value, 'Thành phố Hồ Chí Minh, ngày')) $replacement = $dateText;
            elseif (str_contains($value, 'Về việc điều động') || str_contains($value, 'Về việc thu hồi')) $replacement = 'Về việc '.$action.' vật tư, trang bị kỹ thuật';
            elseif (str_contains($value, 'Căn cứ DỮ LIỆU TEST') || str_contains($value, 'Căn cứ nhu cầu')) $replacement = 'Căn cứ nhu cầu biên chế và nhu cầu huấn luyện;';
            elseif (str_contains($value, 'Theo đề nghị của')) $replacement = 'Theo đề nghị của đồng chí Trưởng: '.($requesting ?: '……………………').';';
            elseif (str_contains($value, 'Điều 1.')) $replacement = 'Điều 1. '.$verb.' của '.$sourceUnit.($type === 'recall' ? ' về kho' : ' cho '.$receiverUnit).' các loại vật tư, trang bị kỹ thuật cụ thể sau:';
            elseif (str_contains($value, 'Điều 2.')) $replacement = 'Điều 2. '.$receiverUnit.' liên hệ với '.$callingUnit.' để giao nhận tại kho '.$sourceUnit.'.';
            elseif (str_contains($value, 'Chỉ huy phòng đích')) $replacement = 'Chỉ huy '.$receiverUnit.', '.$sourceUnit.' và các đơn vị có liên quan chịu trách nhiệm thi hành Quyết định.';
            elseif (trim($value) === '- phòng nguồn;') $replacement = '- '.$sourceUnit.';';
            elseif (trim($value) === '- phòng đích;') $replacement = '- '.$receiverUnit.';';
            elseif (trim($value) === '- Phòng Hành chính;') $replacement = '- '.$callingUnit.';';
            elseif (str_starts_with(trim($value), 'Đại úy Test') || str_starts_with(trim($value), 'Người thực hiện:')) $replacement = $transfer->signer ?: '';
            if ($replacement !== null) $text->nodeValue = $replacement;
        }
    }

    private function replaceWarehouseSummary(\DOMXPath $xpath, $assets): void
    {
        $stable = $assets->whereNotIn('status', ['BROKEN', 'REPAIRING']);
        $broken = $assets->whereIn('status', ['BROKEN', 'REPAIRING']);
        foreach ($xpath->query('//w:t') as $text) {
            $value = (string) $text->nodeValue;
            if (str_contains($value, 'Tổng hợp:')) {
                $text->nodeValue = 'Tổng hợp: SL ổn định '.$stable->sum('quantity').' ('.$stable->count().' dòng); SL hư hại '.$broken->sum('quantity').' ('.$broken->count().' dòng).';
            }
        }
        $summaryNodes = $xpath->query('//w:t[contains(., "Tổng số lượng:")]');
        foreach ($summaryNodes as $index => $text) {
            $rows = $index === 0 ? $stable : $broken;
            if ($rows->count() === 0) {
                $text->nodeValue = '';
                continue;
            }
            $text->nodeValue = 'Tổng số lượng: '.$rows->sum('quantity').'; số dòng: '.$rows->count().'.';
        }
    }

    private function removeEmptyRepairSection(\DOMXPath $xpath, $rows): void
    {
        if ($rows->isNotEmpty()) return;

        $body = $xpath->query('//w:body')->item(0);
        if (!$body) return;

        foreach ($body->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE || $node->localName !== 'p') continue;
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if (!str_contains($text, 'II. Kho vật tư đang sửa chữa và hư hại')) continue;

            $next = $node->nextSibling;
            $body->removeChild($node);
            while ($next) {
                $current = $next;
                $next = $current->nextSibling;
                $currentText = trim(preg_replace('/\s+/', ' ', $current->textContent));
                if ($current->nodeType === XML_ELEMENT_NODE && $current->localName === 'tbl') {
                    $body->removeChild($current);
                    break;
                }
                if ($current->nodeType === XML_ELEMENT_NODE && $current->localName === 'p' && str_contains($currentText, 'Nơi nhận:')) break;
                if ($current->nodeType === XML_ELEMENT_NODE && $current->localName === 'p' && str_contains($currentText, 'Tổng số lượng:')) $body->removeChild($current);
            }
            break;
        }
    }
}
