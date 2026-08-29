<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Modules\Inventory\Models\{InventoryAsset, InventoryAuditLog, InventoryMovement, InventoryTransfer};

class InventoryReportTemplateController extends ModuleBaseController
{
    public function download(Request $request)
    {
        $files = [
            'position' => 'bao-cao-thuc-luc-hien-co-theo-vi-tri.docx',
            'total-position' => 'bao-cao-thuc-luc-hien-co-tong-the.docx',
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
            'unit' => 'total-position',
            default => (string) $request->input('report_type', 'position'),
        };
        abort_unless(isset($files[$type]), 422, 'Loại báo cáo không hợp lệ.');

        $path = resource_path('inventory-report-templates/'.$files[$type]);
        abort_unless(is_file($path), 404, 'Chưa có mẫu báo cáo tương ứng.');

        return in_array($type, ['position', 'total-position', 'using-position', 'using-total'], true)
            ? $this->fillPositionTemplate($request, $path, $files[$type])
            : $this->fillReportTemplate($request, $path, $files[$type], $type);
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
        $documentXml = $xml->saveXML();
        $zip->close();
        return $this->writeReportZip($template, $documentXml, $filename);
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

    private function fillPositionTemplate(Request $request, string $template, string $filename): mixed
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
        // Trong mẫu gốc: dòng 2 là tổng cộng mẫu, dòng 3 là ngành,
        // dòng 4 là loại và dòng 5 là vật tư chi tiết.
        $groupTemplate = $rows->item(3)->cloneNode(true);
        $categoryTemplate = $rows->item(4)->cloneNode(true);
        $itemTemplate = $rows->item(5)->cloneNode(true);
        $roomTemplate = $rows->item(9)->cloneNode(true);
        $totalTemplate = $rows->item($rows->length - 1)->cloneNode(true);

        $unitModels = $assets->map(fn ($asset) => $asset->classroom?->managingUnit ?: $asset->holdingUnit)
            ->filter()->unique('id')->sortBy('id')->values();
        $unitColumns = $unitModels->take(max(0, $xpath->query('./w:tc', $rows->item(1))->length - 4))->values();
        $this->setUnitHeaders($xml, $xpath, $rows->item(1), $unitColumns);

        for ($i = $rows->length - 2; $i >= 2; $i--) {
            $table->removeChild($rows->item($i));
        }

        // Cấu trúc báo cáo: ngành -> loại -> vật tư -> các phòng đang sử dụng.
        $industries = $assets->groupBy(fn ($asset) => $asset->material?->category?->parent?->name ?: 'Chưa xác định ngành');
        $industryNo = 0;
        $buildingNames = $assets->map(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư')->unique()->values();
        $buildingCount = $assets->map(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư')->unique()->count();
        foreach ($industries as $industry => $industryAssets) {
            $industryNo++;
            $this->setTemplateRow($xml, $groupTemplate->cloneNode(true), [null, $industry, null, $industryAssets->sum('quantity')], $table);
            $types = $industryAssets->groupBy(fn ($asset) => $asset->material?->category?->name ?: 'Chưa xác định loại');
            $typeNo = 0;
            foreach ($types as $type => $typeAssets) {
                $typeNo++;
                $this->setTemplateRow($xml, $categoryTemplate->cloneNode(true), [null, $type, null, $typeAssets->sum('quantity')], $table);
                $materials = $typeAssets->groupBy(fn ($asset) => $asset->material_id ?: $asset->asset_code ?: $asset->name);
                foreach ($materials as $materialAssets) {
                    $first = $materialAssets->first();
                    $total = $materialAssets->sum('quantity');
                    $itemValues = [
                        $first->asset_code ?: $first->material?->code,
                        $first->name ?: $first->material?->name,
                        $first->unit ?: $first->material?->unit,
                        $total,
                    ];
                    $itemValues = array_merge($itemValues, $this->unitQuantities($materialAssets, $unitColumns));
                    $this->setTemplateRow($xml, $this->boldRow($xml, $itemTemplate->cloneNode(true)), $itemValues, $table);
                    $buildings = $materialAssets->groupBy(fn ($asset) => $asset->classroom?->building?->name ?: 'Kho vật tư');
                    foreach ($buildings as $building => $buildingAssets) {
                        $buildingNumber = $buildingNames->search($building) + 1;
                        $buildingLabel = $buildingCount > 1 ? $buildingNumber.'. '.$building : $building;
                        $buildingValues = [null, $buildingLabel, null, $buildingAssets->sum('quantity')];
                        $this->setTemplateRow($xml, $roomTemplate->cloneNode(true), array_merge($buildingValues, $this->unitQuantities($buildingAssets, $unitColumns)), $table);
                        $rooms = $buildingAssets->groupBy(fn ($asset) => $asset->classroom?->id ?: 'warehouse');
                        foreach ($rooms as $roomAssets) {
                        $roomAsset = $roomAssets->first();
                        $room = $roomAsset->classroom;
                        $roomName = $room?->name ?: 'Kho vật tư';
                        $address = trim(implode(' - ', array_filter([$room?->building?->name, $room?->code])));
                        $roomValues = [
                            null,
                            '| '.$roomName,
                            null,
                            $roomAssets->sum('quantity'),
                        ];
                        $roomRow = $roomTemplate->cloneNode(true);
                        $this->rightAlignCell($xml, $roomRow, 1);
                        $this->setTemplateRow($xml, $roomRow, array_merge($roomValues, $this->unitQuantities($roomAssets, $unitColumns)), $table);
                        }
                    }
                }
            }
        }

        $this->setTemplateRow($xml, $totalTemplate, [null, 'TỔNG CỘNG', null, $assets->sum('quantity')], $table);
        foreach ($xpath->query('//w:t') as $text) {
            if (str_contains($text->nodeValue, 'Vị trí quản lý sử dụng (chi tiết theo phòng)')) $text->nodeValue = '';
        }
        $this->replaceReportDate($xpath, $request);
        $this->replaceUnitReportTitle($xpath, $request);
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
        foreach ($units as $unit) {
            $quantity = $assets->filter(fn ($asset) => (int) $asset->classroom?->managing_unit_id === (int) $unit->id)->sum('quantity');
            $values[] = $quantity > 0 ? $quantity : '';
        }
        return $values;
    }

    private function setUnitHeaders(\DOMDocument $xml, \DOMXPath $xpath, ?\DOMNode $headerRow, $units): void
    {
        if (!$headerRow) return;
        $cells = $xpath->query('./w:tc', $headerRow);
        foreach ($units as $index => $unit) {
            $cell = $cells->item($index + 4);
            if (!$cell) continue;
            $texts = $xpath->query('.//w:t', $cell);
            if ($texts->length) {
                $texts->item(0)->nodeValue = $unit->abbreviation ?: $unit->code ?: $unit->name;
                for ($i = 1; $i < $texts->length; $i++) $texts->item($i)->nodeValue = '';
            }
        }
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
