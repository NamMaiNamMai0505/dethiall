<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Modules\Inventory\Models\{InventoryAsset, InventoryAuditLog, InventoryMovement, InventoryReportTemplate, InventoryTransfer};

class InventoryReportTemplateController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;
    private const POSITION_FIXED_WIDTHS_5 = [900, 3000, 430, 480, 720];
    private const POSITION_FIXED_WIDTHS_4 = [900, 3300, 430, 720];
    private const POSITION_TABLE_WIDTH = 15400;

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
        ];

        $type = match ((string) $request->input('report_type', 'position')) {
            'summary' => $request->input('scope', 'position') === 'all' ? 'total-position' : 'position',
            'movement' => 'increase-decrease',
            'using' => $request->input('scope', 'position') === 'all' ? 'using-total' : 'using-position',
            'unit' => 'unit',
            default => (string) $request->input('report_type', 'position'),
        };
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
            $template = InventoryReportTemplate::whereKey($request->integer('template_id'))->where('active', true)->where('report_type', $type)->first();
            abort_unless($template, 404, 'Không tìm thấy mẫu báo cáo đã chọn.');
            $path = $template->absolutePath();
            abort_unless($path && is_file($path), 404, 'File mẫu báo cáo đã chọn không tồn tại.');
            abort_unless(strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'docx', 422, 'Mẫu báo cáo Word phải là file .docx.');

            return [$path, basename((string) $template->file_path)];
        }

        $custom = InventoryReportTemplate::where('report_type', $type)->where('active', true)->latest()->first();
        if ($custom) {
            $path = $custom->absolutePath();
            if ($path && is_file($path)) {
                return [$path, basename((string) $custom->file_path)];
            }
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
            'warehouse' => 'Báo cáo kho vật tư',
            'system-warehouse' => 'Báo cáo hệ thống kho vật tư',
            'transfer' => 'Quyết định điều động vật tư',
            'recall' => 'Quyết định thu hồi vật tư',
            'repair' => 'Báo cáo vật tư hư hại và sửa chữa',
            'update-log' => 'Báo cáo cập nhật vật tư',
            'using-position' => 'Báo cáo vật tư đang sử dụng theo vị trí',
            'using-total' => 'Báo cáo vật tư đang sử dụng tổng thể',
        ];
        $processor->setValues([
            'ngay_bao_cao' => $today->format('d/m/Y'),
            'ngay' => $today->format('d'),
            'thang' => $today->format('m'),
            'nam' => $today->format('Y'),
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

        return response()->download($output, $safeCode.'.docx')->deleteFileAfterSend(true);
    }

    private function loadReportRows(Request $request, string $type): array
    {
        $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'material.category.parent', 'holdingUnit'])
            ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->orderBy('name')->get();

        $rowsData = $assets;
        if ($type === 'repair') {
            $rowsData = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
        } elseif (in_array($type, ['transfer', 'recall'], true)) {
            $rowsData = InventoryTransfer::with(['asset', 'material', 'fromClassroom.managingUnit', 'toClassroom.managingUnit'])->where('type', $type === 'recall' ? 'RECALL' : 'TRANSFER')->latest()->get();
        } elseif (in_array($type, ['increase-decrease', 'update-log'], true)) {
            $actions = $type === 'increase-decrease' ? ['INCREASE', 'DECREASE', 'ADJUST'] : ['CREATE', 'UPDATE', 'IMPORT', 'INCREASE', 'DECREASE', 'ADJUST', 'MOVEMENT'];
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', $actions)
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
        $assets = InventoryAsset::with(['classroom.building', 'classroom.managingUnit', 'material.category.parent', 'holdingUnit'])
            ->when($request->filled('building_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('building_id', $request->integer('building_id'))))
            ->when($request->filled('classroom_id'), fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('unit_id'), fn ($q) => $q->whereHas('classroom', fn ($room) => $room->where('managing_unit_id', $request->integer('unit_id'))))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->orderBy('name')->get();
        $rowsData = $assets;
        if ($type === 'repair') {
            $rowsData = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
        } elseif (in_array($type, ['transfer', 'recall'], true)) {
            $rowsData = InventoryTransfer::with(['asset', 'fromClassroom.managingUnit', 'toClassroom.managingUnit'])->where('type', $type === 'recall' ? 'RECALL' : 'TRANSFER')->latest()->get();
            abort_if($rowsData->isEmpty(), 422, $type === 'recall' ? 'Chưa có phiếu thu hồi để xuất quyết định.' : 'Chưa có phiếu điều động để xuất quyết định.');
            // Mẫu quyết định là một quyết định cho một phiếu; lấy phiếu mới nhất đã có trong hệ thống.
            $rowsData = collect([$rowsData->first()]);
        } elseif ($type === 'increase-decrease') {
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', ['INCREASE', 'DECREASE', 'ADJUST'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()->get();
        } elseif ($type === 'update-log') {
            $rowsData = InventoryAuditLog::with('user')->whereIn('action', ['CREATE', 'UPDATE', 'IMPORT', 'INCREASE', 'DECREASE', 'ADJUST', 'MOVEMENT'])
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
        $tableIndexes = $tables->length > 1 ? ($type === 'warehouse' || $type === 'system-warehouse' ? [1, 2] : [1]) : [0];
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
            if (($type === 'warehouse' || $type === 'system-warehouse') && $tableIndex === 1) $source = $assets->whereNotIn('status', ['BROKEN', 'REPAIRING'])->values();
            if (($type === 'warehouse' || $type === 'system-warehouse') && $tableIndex === 2) $source = $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values();
            foreach ($source as $index => $record) {
                $values = $this->reportRowValues($record, $type, $index + 1);
                $this->setTemplateRow($xml, $templateRow->cloneNode(true), $values, $table);
            }
            if ($totalRow) {
                $total = $source->sum(fn ($item) => (float) ($item instanceof InventoryAsset
                    ? $item->quantity
                    : ($item instanceof InventoryAuditLog
                        ? abs((float) (($item->details['change'] ?? $item->details['quantity'] ?? 0)))
                        : ($item->quantity ?? $item->asset?->quantity ?? 0))));
                $this->setTemplateRow($xml, $totalRow, [null, 'TỔNG CỘNG', null, $total], $table);
            }
        }
        $this->replaceReportDate($xpath, $request);
        if (in_array($type, ['transfer', 'recall'], true)) $this->replaceTransferDocument($xpath, $rowsData->first(), $type);
        if ($type === 'update-log') $this->replaceUpdateSummary($xpath, $rowsData);
        if ($type === 'repair') $this->replaceRepairSummary($xpath, $rowsData);
        if (in_array($type, ['warehouse', 'system-warehouse'], true)) {
            $this->replaceWarehouseSummary($xpath, $assets);
            $this->removeEmptyRepairSection($xpath, $assets->whereIn('status', ['BROKEN', 'REPAIRING'])->values());
        }
        $this->replaceScalarTemplateValues($xpath, $request, $type);
        $documentXml = $xml->saveXML();
        $zip->close();
        return $this->writeReportZip($template, $documentXml, $filename);
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
            'tren_cap' => '',
            'mua_sam' => '',
            'tang_phan_cap' => '',
            'kiem_ke_tang' => '',
            'tang_khac' => '',
            'tra_tren' => '',
            'hao_hut' => '',
            'thanh_ly' => '',
            'hu_hong' => '',
            'kiem_ke_giam' => '',
            'giam_khac' => '',
        ];
        $key = match (true) {
            $change >= 0 && (str_contains($reason, 'trên cấp') || str_contains($reason, 'tren cap')) => 'tren_cap',
            $change >= 0 && (str_contains($reason, 'mua sắm') || str_contains($reason, 'mua sam')) => 'mua_sam',
            $change >= 0 && str_contains($reason, 'phân cấp') => 'tang_phan_cap',
            $change >= 0 && str_contains($reason, 'kiểm kê') => 'kiem_ke_tang',
            $change < 0 && (str_contains($reason, 'trả trên') || str_contains($reason, 'tra tren')) => 'tra_tren',
            $change < 0 && (str_contains($reason, 'hao hụt') || str_contains($reason, 'hao hut')) => 'hao_hut',
            $change < 0 && str_contains($reason, 'thanh lý') => 'thanh_ly',
            $change < 0 && (str_contains($reason, 'hư hỏng') || str_contains($reason, 'hu hong')) => 'hu_hong',
            $change < 0 && str_contains($reason, 'kiểm kê') => 'kiem_ke_giam',
            default => $change >= 0 ? 'tang_khac' : 'giam_khac',
        };
        $row[$key] = (string) ($details['reason'] ?? $details['note'] ?? '');
        return $row;
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

    private function reportRowValues(mixed $record, string $type, int $number): array
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
                $quantity = $details['quantity'] ?? abs((float) ($details['change'] ?? 0));
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
            $values = array_fill(0, 16, '');
            $values[0] = $material;
            $values[1] = $details['unit'] ?? '';
            $values[2] = $details['grade'] ?? '';
            $values[3] = $change > 0 ? $change : '';
            $values[4] = $change < 0 ? abs($change) : '';
            $reason = mb_strtolower(trim((string) ($details['reason'] ?? $details['note'] ?? '')));
            $reasonColumns = $change >= 0
                ? ['mua sắm' => 6, 'mua sam' => 6, 'trên cấp' => 5, 'tăng phân cấp' => 7, 'kiểm kê' => 8]
                : ['hao hụt' => 11, 'hao hut' => 11, 'thanh lý' => 12, 'hư hỏng' => 13, 'hư hong' => 13, 'trả trên' => 10, 'kiểm kê' => 14];
            $values[$reasonColumns[$reason] ?? ($change >= 0 ? 9 : 15)] = $details['reason'] ?? $details['note'] ?? '';
            return $values;
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
            $details = (array) $record->details;
            $row['ngay_du_lieu'] = optional($record->created_at)->format('d/m/Y');
            $row['ma_vat_tu'] = (string) ($details['asset_code'] ?? $details['code'] ?? '');
            $row['ten_vat_tu'] = (string) ($details['name'] ?? 'Vật tư');
            $row['don_vi_tinh'] = (string) ($details['unit'] ?? '');
            $row['so_luong'] = (string) ($details['quantity'] ?? abs((float) ($details['change'] ?? 0)));
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
        $row['ma_vat_tu'] = (string) ($record->asset_code ?: $record->material?->code ?: '');
        $row['ten_vat_tu'] = (string) ($record->name ?: $record->material?->name ?: '');
        $row['nganh'] = (string) ($record->material?->category?->parent?->name ?: '');
        $row['loai_vat_tu'] = (string) ($record->material?->category?->name ?: '');
        $row['don_vi_tinh'] = (string) ($record->unit ?: $record->material?->unit ?: '');
        $row['so_luong'] = (string) $record->quantity;
        $row['phan_cap'] = (string) ($record->grade ?: '');
        $row['trang_thai'] = ['NORMAL' => 'Bình thường', 'BROKEN' => 'Hỏng', 'REPAIRING' => 'Đang sửa', 'LIQUIDATED' => 'Đã thanh lý'][$record->status] ?? (string) $record->status;
        $row['toa_nha'] = (string) ($record->classroom?->building?->name ?: 'Kho vật tư');
        $row['phong'] = (string) ($record->classroom?->name ?: 'Kho vật tư');
        $row['don_vi_quan_ly'] = (string) ($record->classroom?->managingUnit?->name ?: $record->holdingUnit?->name ?: '');
        $row['vi_tri'] = trim($row['toa_nha'].' / '.$row['phong'], ' /');
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
            'trang_thai' => '',
            'toa_nha' => '',
            'phong' => '',
            'don_vi_quan_ly' => '',
            'vi_tri' => '',
            'loai_bien_dong' => '',
            'truoc' => '',
            'sau' => '',
            'nguoi_thuc_hien' => '',
            'ly_do' => '',
            'ghi_chu' => '',
        ];
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
        $this->setDocumentLandscapeWidth($xml, $xpath, $tableWidth + 1800);
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
                $materials = $typeAssets->groupBy(fn ($asset) => $asset->material_id ?: $asset->asset_code ?: $asset->name);
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

    private function setDocumentLandscapeWidth(\DOMDocument $xml, \DOMXPath $xpath, int $contentWidth): void
    {
        $pageWidth = max(23811, $contentWidth);
        $pageHeight = 16838;

        foreach ($xpath->query('//w:sectPr') as $sectionProperties) {
            $pageSize = $xpath->query('./w:pgSz', $sectionProperties)->item(0);
            if (!$pageSize) {
                $pageSize = $xml->createElement('w:pgSz');
                $sectionProperties->insertBefore($pageSize, $sectionProperties->firstChild);
            }

            $pageSize->setAttribute('w:w', (string) $pageWidth);
            $pageSize->setAttribute('w:h', (string) $pageHeight);
            $pageSize->setAttribute('w:orient', 'landscape');
        }
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
