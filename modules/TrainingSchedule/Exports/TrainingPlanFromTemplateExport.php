<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất LHL bằng cách CLONE file biểu mẫu chuẩn (HK2 25-26).
 * Giữ nguyên gạch chéo ngày/tiết, border, chữ ký ảnh, font — chỉ đổ biến + lịch.
 */
class TrainingPlanFromTemplateExport
{
    /** Cột tuần đầu tiên trên mẫu = C (index 3) */
    protected int $firstWeekCol = 3;

    /** Số tuần tối đa trên mẫu (C→Z ≈ 24) */
    protected int $maxWeeks = 24;

    /** Hàng dữ liệu: Thứ 2..6 × (sáng/chiều) = rows 10..19 */
    protected int $dataStartRow = 10;

    protected int $dataEndRow = 19;

    /**
     * @param  list<array{
     *   sheet_title:string,
     *   class_name:string,
     *   semester_label:string,
     *   academic_year:string,
     *   start:Carbon,
     *   end:Carbon,
     *   cells:Collection,
     *   legend:Collection
     * }>  $classes
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        protected array $classes,
        protected array $meta = [],
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = $this->build();

        if (! str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            // 1) PhpSpreadsheet ghi data (mất line shapes trong drawing)
            $tmp = tempnam(sys_get_temp_dir(), 'lhl_').'.xlsx';
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmp);
            $spreadsheet->disconnectWorksheets();

            // 2) Ghép lại drawing gốc từ template → khôi phục gạch chéo (cxnSp) + media chữ ký
            try {
                $this->restoreTemplateDrawings($tmp);
                $this->overlayCustomSignatureImages($tmp, $this->meta['signers'] ?? []);
            } catch (\Throwable $e) {
                // vẫn trả file data nếu restore lỗi
                report($e);
            }

            readfile($tmp);
            @unlink($tmp);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * PhpSpreadsheet chỉ serialize Picture, bỏ Straight Connector (gạch chéo).
     * Copy drawing XML + media từ file mẫu HK2 vào file xuất.
     */
    protected function restoreTemplateDrawings(string $outputPath): void
    {
        $templatePath = $this->resolveTemplatePath();
        $tpl = new \ZipArchive;
        $out = new \ZipArchive;
        if ($tpl->open($templatePath) !== true || $out->open($outputPath) !== true) {
            $tpl->close();
            $out->close();

            return;
        }

        // Media gốc (png chữ ký + wdp nếu có)
        $media = [];
        for ($i = 0; $i < $tpl->numFiles; $i++) {
            $name = $tpl->getNameIndex($i);
            if (str_starts_with($name, 'xl/media/') && ! str_ends_with($name, '/')) {
                $media[$name] = $tpl->getFromIndex($i);
            }
        }

        $drawingXml = $tpl->getFromName('xl/drawings/drawing1.xml');
        $drawingRels = $tpl->getFromName('xl/drawings/_rels/drawing1.xml.rels');
        $tpl->close();

        if ($drawingXml === false || $drawingXml === '') {
            $out->close();

            return;
        }
        $drawingXml = $this->transformTemplateDrawingXml($drawingXml);

        // Xoá drawing/media do PhpSpreadsheet sinh (hash name, thiếu line)
        $toDelete = [];
        for ($i = 0; $i < $out->numFiles; $i++) {
            $name = $out->getNameIndex($i);
            if (str_starts_with($name, 'xl/drawings/') || str_starts_with($name, 'xl/media/')) {
                $toDelete[] = $name;
            }
        }
        foreach ($toDelete as $name) {
            $out->deleteName($name);
        }

        // Ghi media template
        foreach ($media as $name => $bin) {
            if ($bin !== false && $bin !== null) {
                $out->addFromString($name, $bin);
            }
        }

        // Mỗi sheet thường 1 drawing — dùng lại drawing1 (đủ line gạch chéo + 3 chữ ký)
        // Tìm rels sheet để biết drawing path, mặc định drawing1.xml
        $drawingTargets = ['xl/drawings/drawing1.xml'];
        for ($i = 0; $i < $out->numFiles; $i++) {
            $name = $out->getNameIndex($i);
            if (preg_match('#^xl/worksheets/_rels/sheet\d+\.xml\.rels$#', $name)) {
                $rels = $out->getFromIndex($i);
                if (is_string($rels) && preg_match_all('/Target="([^"]*drawing[^"]+)"/', $rels, $m)) {
                    foreach ($m[1] as $t) {
                        // Target dạng ../drawings/drawing1.xml
                        $path = 'xl/'.ltrim(preg_replace('#^\.\./#', '', $t) ?? $t, '/');
                        if (str_contains($path, 'drawing')) {
                            $drawingTargets[] = $path;
                        }
                    }
                }
            }
        }
        $drawingTargets = array_values(array_unique($drawingTargets));

        foreach ($drawingTargets as $path) {
            $out->addFromString($path, $drawingXml);
            // rels cạnh drawing
            $base = basename($path);
            $relsPath = 'xl/drawings/_rels/'.$base.'.rels';
            if ($drawingRels !== false) {
                $out->addFromString($relsPath, $drawingRels);
            }
        }

        // Đảm bảo content types có png
        $ct = $out->getFromName('[Content_Types].xml');
        if (is_string($ct) && ! str_contains($ct, 'Extension="png"')) {
            $ct = str_replace(
                '</Types>',
                '<Default Extension="png" ContentType="image/png"/><Default Extension="wdp" ContentType="image/vnd.ms-photo"/></Types>',
                $ct
            );
            $out->addFromString('[Content_Types].xml', $ct);
        }

        $out->close();
    }

    protected function transformTemplateDrawingXml(string $xml): string
    {
        $dom = new \DOMDocument;
        if (! @$dom->loadXML($xml)) {
            return $xml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace(
            'xdr',
            'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing'
        );

        $diagonalFound = false;
        $anchors = iterator_to_array($xpath->query('/xdr:wsDr/*') ?: []);
        foreach ($anchors as $anchor) {
            $fromColumn = $xpath->query('xdr:from/xdr:col', $anchor)?->item(0);
            $fromRow = $xpath->query('xdr:from/xdr:row', $anchor)?->item(0);
            $toColumn = $xpath->query('xdr:to/xdr:col', $anchor)?->item(0);
            $toRow = $xpath->query('xdr:to/xdr:row', $anchor)?->item(0);
            $isMonthWeekDiagonal = $fromColumn?->textContent === '1'
                && $fromRow?->textContent === '7'
                && $toColumn?->textContent === '1'
                && $toRow?->textContent === '9'
                && $xpath->query('.//xdr:cxnSp', $anchor)?->length > 0;

            if (! $isMonthWeekDiagonal) {
                continue;
            }
            if ($diagonalFound) {
                $anchor->parentNode?->removeChild($anchor);

                continue;
            }
            $diagonalFound = true;

            // Neo đúng mép khung B8:B9. Mẫu gốc dùng offset pixel cố định
            // nên đường chéo bị ngắn/dài khi Excel hoặc LibreOffice đổi scale.
            $fromColumn->textContent = '1';
            $fromRow->textContent = '7';
            $toColumn->textContent = '2';
            $toRow->textContent = '9';
            foreach ([
                'xdr:from/xdr:colOff',
                'xdr:from/xdr:rowOff',
                'xdr:to/xdr:colOff',
                'xdr:to/xdr:rowOff',
            ] as $offsetPath) {
                $offset = $xpath->query($offsetPath, $anchor)?->item(0);
                if ($offset) {
                    $offset->textContent = '0';
                }
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    /**
     * Thay image1/3/5.png trong xlsx bằng ảnh chữ ký user đã chọn (nếu có).
     *
     * @param  list<array<string,mixed>>  $signers
     */
    protected function overlayCustomSignatureImages(string $xlsxPath, array $signers): void
    {
        // drawing1 rels: rId1=image1.png, rId3=image3.png, rId5=image5.png
        $map = [
            0 => 'xl/media/image1.png',
            1 => 'xl/media/image3.png',
            2 => 'xl/media/image5.png',
        ];
        $zip = new \ZipArchive;
        if ($zip->open($xlsxPath) !== true) {
            return;
        }
        foreach ($signers as $i => $signer) {
            if (! isset($map[$i])) {
                break;
            }
            if (isset($signer['enabled']) && ! $signer['enabled']) {
                continue;
            }
            $path = $this->resolveSignatureImage((string) ($signer['image'] ?? ''));
            if (! $path || ! is_file($path)) {
                continue;
            }
            // Bỏ qua nếu đã là ảnh mẫu lhl
            $norm = str_replace('\\', '/', $path);
            if (str_contains($norm, '/signatures/lhl/')) {
                continue;
            }
            $bin = file_get_contents($path);
            if ($bin === false) {
                continue;
            }
            $zip->addFromString($map[$i], $bin);
        }
        $zip->close();
    }

    public function build(): Spreadsheet
    {
        $templatePath = $this->resolveTemplatePath();
        $usedTitles = [];

        if ($this->classes === []) {
            $book = $this->loadTemplateBook($templatePath);
            $sheet = $book->getSheet(0);
            $this->fillSheet($sheet, [
                'sheet_title' => 'LHL',
                'class_name' => '—',
                'semester_label' => '',
                'academic_year' => '',
                'start' => Carbon::now()->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
                'cells' => collect(),
                'legend' => collect(),
            ]);
            $sheet->setTitle('LHL');

            return $book;
        }

        /** @var Spreadsheet|null $out */
        $out = null;
        foreach ($this->classes as $classData) {
            $book = $this->loadTemplateBook($templatePath);
            $sheet = $book->getSheet(0);
            $title = $this->uniqueTitle((string) ($classData['sheet_title'] ?? 'LHL'), $usedTitles);
            $this->fillSheet($sheet, $classData);
            $sheet->setTitle($title);

            if ($out === null) {
                $out = $book;
            } else {
                // Chuyển sheet (kèm drawing chữ ký) sang workbook đích
                $out->addExternalSheet($sheet);
                $book->disconnectWorksheets();
            }
        }

        return $out ?? $this->loadTemplateBook($templatePath);
    }

    protected function loadTemplateBook(string $templatePath): Spreadsheet
    {
        $book = IOFactory::load($templatePath);
        // Chỉ giữ 1 sheet mẫu đầu — layout + gạch chéo + 3 ảnh chữ ký
        while ($book->getSheetCount() > 1) {
            $book->removeSheetByIndex($book->getSheetCount() - 1);
        }

        return $book;
    }

    /**
     * @param  array<string, mixed>  $classData
     */
    protected function fillSheet(Worksheet $sheet, array $classData): void
    {
        /** @var Carbon $start */
        $start = $classData['start'] instanceof Carbon
            ? $classData['start']->copy()->startOfDay()
            : Carbon::parse($classData['start'])->startOfDay();
        /** @var Carbon $end */
        $end = $classData['end'] instanceof Carbon
            ? $classData['end']->copy()->startOfDay()
            : Carbon::parse($classData['end'])->startOfDay();

        $className = (string) ($classData['class_name'] ?? '');
        $semesterLabel = (string) ($classData['semester_label'] ?? '');
        $academicYear = (string) ($classData['academic_year'] ?? '');
        /** @var Collection $cells */
        $cells = $classData['cells'] ?? collect();
        /** @var Collection $legend */
        $legend = $classData['legend'] ?? collect();

        $meta = $this->meta;
        $cfg = config('lhl_export', []);

        // —— Header (giữ merge/style mẫu, chỉ ghi value) ——
        $org = (string) ($meta['org_left'] ?? $cfg['org_left'] ?? "TỔNG CỤC HẬU CẦN - KỸ THUẬT\nTRƯỜNG CAO ĐẲNG HẬU CẦN 2");
        $title = (string) ($meta['title'] ?? $cfg['title'] ?? 'LỊCH HUẤN LUYỆN');
        $semesterLine = trim((string) ($meta['semester_line'] ?? ''));
        if ($semesterLine === '') {
            $semesterLine = trim($semesterLabel.' năm học '.$academicYear);
        }

        // Mẫu YS1B1: A1 org, H1 title, H2 semester, V/W meta lớp
        $sheet->setCellValue('A1', $org);
        // Title có thể ở H1 (mẫu) hoặc C1 — ghi cả hai nếu có
        if ($sheet->getCell('H1')->getValue() !== null || $this->cellLooksEmpty($sheet, 'C1')) {
            $sheet->setCellValue('H1', $title);
        }
        $sheet->setCellValue('C1', $title);
        $sheet->setCellValue('H2', $semesterLine);
        $sheet->setCellValue('C2', $semesterLine);

        $sheet->setCellValue('V1', 'Lớp: ');
        $sheet->setCellValue('W1', $className);
        $sheet->setCellValue('A3', 'Lớp: '.$className);

        $unit = (string) ($meta['unit_name'] ?? '');
        if ($unit !== '') {
            $sheet->setCellValue('V2', 'Đơn vị: '.$unit);
        }
        $size = (string) ($meta['class_size'] ?? '');
        $groups = (string) ($meta['groups'] ?? '');
        if ($size !== '') {
            $sheet->setCellValue('V3', 'Sĩ số: '.$size);
        }
        if ($groups !== '') {
            $sheet->setCellValue('X3', 'Số tổ : '.$groups);
        }
        $leader = (string) ($meta['class_leader'] ?? '');
        if ($leader !== '') {
            $sheet->setCellValue('V4', 'CN lớp: '.$leader);
        }
        $classroom = (string) ($meta['classroom'] ?? '');
        if ($classroom !== '') {
            $sheet->setCellValue('V5', 'Phòng học: '.$classroom);
        }

        $respect = (string) ($meta['respect_line'] ?? $cfg['respect_line'] ?? '');
        if ($respect !== '') {
            // Mẫu để ở J5
            $sheet->setCellValue('J5', $respect);
            $sheet->setCellValue('A5', $respect);
        }

        $sheet->setCellValue('A4', 'Từ '.$start->format('d/m/Y').' đến '.$end->format('d/m/Y'));

        // —— Tuần / tháng / ngày (giữ style ô C9… — gạch chéo bằng layout text gốc) ——
        $weeks = $this->buildWeeks($start, $end);
        $weekCount = min(count($weeks), $this->maxWeeks);

        // Xóa giá trị tuần cũ ngoài phạm vi (giữ style)
        for ($wi = 0; $wi < $this->maxWeeks; $wi++) {
            $col = Coordinate::stringFromColumnIndex($this->firstWeekCol + $wi);
            if ($wi >= $weekCount) {
                $sheet->setCellValue($col.'7', null);
                $sheet->setCellValue($col.'8', null);
                $sheet->setCellValue($col.'9', null);
            }
        }

        // Unmerge month headers trên hàng 7 (C7:F7 …) rồi set lại theo tháng thật
        $this->resetMonthMerges($sheet, $weekCount);

        $monthSpans = [];
        for ($wi = 0; $wi < $weekCount; $wi++) {
            $week = $weeks[$wi];
            $colIndex = $this->firstWeekCol + $wi;
            $col = Coordinate::stringFromColumnIndex($colIndex);

            $monthKey = $week['start']->format('Y-n');
            if (! isset($monthSpans[$monthKey])) {
                $monthSpans[$monthKey] = [$colIndex, $colIndex, (int) $week['start']->format('n')];
            } else {
                $monthSpans[$monthKey][1] = $colIndex;
            }

            $sheet->setCellValue($col.'8', $wi + 1);
            // Đúng format mẫu: "02\n            08" (ngày đầu + xuống dòng + spaces + ngày cuối)
            // → khi mở Excel trông như gạch chéo Ngày/Tiết
            $d1 = $week['start']->format('d');
            $d2 = $week['end']->format('d');
            $sheet->setCellValue($col.'9', $d1."\n            ".$d2);

            // Giữ wrap + alignment như mẫu (không đụng border)
            $sheet->getStyle($col.'9')->getAlignment()
                ->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_GENERAL)
                ->setVertical(Alignment::VERTICAL_BOTTOM);
        }

        foreach ($monthSpans as $span) {
            [$from, $to, $monthNum] = $span;
            $fromCol = Coordinate::stringFromColumnIndex($from);
            $toCol = Coordinate::stringFromColumnIndex($to);
            $sheet->setCellValue($fromCol.'7', $monthNum);
            if ($from !== $to) {
                try {
                    $sheet->mergeCells($fromCol.'7:'.$toCol.'7');
                } catch (\Throwable $e) {
                    // ignore merge conflicts
                }
            }
            $sheet->getStyle($fromCol.'7')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Góc header: Ngày trên-phải · Tiết dưới-trái (gạch chéo từ drawing mẫu, cắt giữa 2 nhãn)
        $sheet->setCellValue('A7', 'Thứ');
        $sheet->setCellValue('B7', 'Tháng');
        // Thứ tự chuẩn PDF: Tuần ở trên, Ngày/Tiết ở vùng chéo phía dưới.
        $sheet->setCellValue('B8', 'Tuần');
        $sheet->setCellValue('B9', "Ngày\nTiết");
        $sheet->getStyle('B8')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('B9')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_BOTTOM);

        // —— Clear vùng lịch mẫu (C10:…R19) giữ border ——
        $this->clearScheduleGrid($sheet, $weekCount);

        // —— Đổ lịch ——
        $this->fillScheduleBody($sheet, $cells, $weeks, $weekCount);

        // —— Ghi chú ——
        $note = (string) ($meta['note'] ?? $cfg['note'] ?? '');
        if ($note !== '') {
            $noteRow = $this->layoutRow(22);
            $sheet->setCellValue('A'.$noteRow, 'Ghi chú:');
            $sheet->setCellValue('C'.$noteRow, $note);
        }

        // —— Legend: clear rows mẫu rồi ghi môn thật ——
        $this->fillLegend($sheet, $legend);

        // —— Chữ ký: cập nhật tên (giữ ảnh drawing của template) ——
        $this->fillSigners($sheet, $meta);
    }

    /**
     * @param  Collection<int, object>  $cells
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     */
    protected function fillScheduleBody(
        Worksheet $sheet,
        Collection $cells,
        array $weeks,
        int $weekCount
    ): void {
        $grid = $this->buildGrid($cells, $weeks);
        for ($isoDow = 1; $isoDow <= 5; $isoDow++) {
            $amRow = $this->dataStartRow + ($isoDow - 1) * 2;
            $pmRow = $amRow + 1;
            // Thứ + buổi đã có trên mẫu (A/B) — không đụng
            for ($wi = 0; $wi < $weekCount; $wi++) {
                $col = Coordinate::stringFromColumnIndex($this->firstWeekCol + $wi);
                foreach (['am' => $amRow, 'pm' => $pmRow] as $session => $r) {
                    $key = $wi.'|'.$isoDow.'|'.$session;
                    $entry = $grid[$key] ?? null;
                    if (! $entry) {
                        continue;
                    }
                    $text = implode("\n", $entry['labels']);
                    $coord = $col.$r;
                    $sheet->setCellValue($coord, $text);
                    $sheet->getStyle($coord)->getFill()->setFillType(Fill::FILL_NONE);
                    $sheet->getStyle($coord)->getFont()
                        ->setBold(true)
                        ->setSize(9)
                        ->getColor()->setRGB('000000');
                    $sheet->getStyle($coord)->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            }
        }
    }

    protected function clearScheduleGrid(Worksheet $sheet, int $weekCount): void
    {
        // Clear toàn bộ vùng tuần trên mẫu (tránh sót lịch mẫu HK2) — style theo range cho nhanh
        $lastColIndex = $this->firstWeekCol + $this->maxWeeks - 1;
        $firstCol = Coordinate::stringFromColumnIndex($this->firstWeekCol);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
        $range = $firstCol.$this->dataStartRow.':'.$lastCol.$this->dataEndRow;

        for ($r = $this->dataStartRow; $r <= $this->dataEndRow; $r++) {
            for ($c = $this->firstWeekCol; $c <= $lastColIndex; $c++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c).$r, null);
            }
        }

        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_NONE);
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('000000');
    }

    /**
     * @param  Collection<int, object>  $legend
     */
    protected function fillLegend(Worksheet $sheet, Collection $legend): void
    {
        // Mẫu: header 24-25, data từ 26, tổng ~37
        $headerRow = $this->layoutRow(24);
        $subHeaderRow = $this->layoutRow(25);
        $startRow = $this->layoutRow(26);
        $endClear = $this->layoutRow(40);
        for ($r = $startRow; $r <= $endClear; $r++) {
            foreach (range(1, 10) as $c) {
                $coord = Coordinate::stringFromColumnIndex($c).$r;
                // Không xóa dòng chữ ký bên phải (L+)
                $sheet->setCellValue($coord, null);
                $sheet->getStyle($coord)->getFill()->setFillType(Fill::FILL_NONE);
            }
        }

        // Đảm bảo header legend
        $sheet->setCellValue('A'.$headerRow, 'Mã');
        $sheet->setCellValue('B'.$headerRow, 'TÊN MÔN HỌC');
        $sheet->setCellValue('E'.$headerRow, 'KHOA');
        $sheet->setCellValue('F'.$headerRow, 'TÍN CHỈ');
        $sheet->setCellValue('G'.$headerRow, 'Thời gian học tập (giờ)');
        $sheet->setCellValue('G'.$subHeaderRow, 'LT');
        $sheet->setCellValue('H'.$subHeaderRow, 'TH');
        $sheet->setCellValue('I'.$subHeaderRow, 'Thi');
        $sheet->setCellValue('J'.$subHeaderRow, 'Tổng');

        $r = $startRow;
        foreach ($legend as $subj) {
            $sheet->setCellValue('A'.$r, $subj->code);
            $sheet->setCellValue('B'.$r, $subj->name);
            $sheet->setCellValue('E'.$r, $subj->faculty);
            $sheet->setCellValue('F'.$r, $subj->credits);
            $sheet->setCellValue('G'.$r, $subj->theory);
            $sheet->setCellValue('H'.$r, $subj->practice);
            $sheet->setCellValue('I'.$r, $subj->exam);
            $sheet->setCellValue('J'.$r, '=SUM(G'.$r.':I'.$r.')');
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
                $sheet->getStyle($col.$r)->getFill()->setFillType(Fill::FILL_NONE);
                $sheet->getStyle($col.$r)->getFont()->getColor()->setRGB('000000');
            }
            $r++;
        }

        if ($legend->isNotEmpty()) {
            $dataEnd = $r - 1;
            $sheet->setCellValue('A'.$r, 'Tổng số');
            $sheet->setCellValue('F'.$r, '=SUM(F'.$startRow.':F'.$dataEnd.')');
            $sheet->setCellValue('G'.$r, '=SUM(G'.$startRow.':G'.$dataEnd.')');
            $sheet->setCellValue('H'.$r, '=SUM(H'.$startRow.':H'.$dataEnd.')');
            $sheet->setCellValue('I'.$r, '=SUM(I'.$startRow.':I'.$dataEnd.')');
            $sheet->setCellValue('J'.$r, '=SUM(J'.$startRow.':J'.$dataEnd.')');
            $sheet->getStyle('A'.$r.':J'.$r)->getFont()->setBold(true);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function fillSigners(Worksheet $sheet, array $meta): void
    {
        $signers = $meta['signers'] ?? config('lhl_export.signers', []);
        if (! is_array($signers)) {
            return;
        }

        // Vị trí trên mẫu: L28 role, L34 name | S28/S29 role, S34 name | X28/X29 role, X34 name
        $map = [
            0 => ['role' => 'L'.$this->layoutRow(28), 'role2' => null, 'name' => 'L'.$this->layoutRow(34)],
            1 => ['role' => 'S'.$this->layoutRow(28), 'role2' => 'S'.$this->layoutRow(29), 'name' => 'S'.$this->layoutRow(34)],
            2 => ['role' => 'X'.$this->layoutRow(28), 'role2' => 'X'.$this->layoutRow(29), 'name' => 'X'.$this->layoutRow(34)],
        ];

        foreach ($signers as $i => $signer) {
            if (! isset($map[$i])) {
                break;
            }
            if (isset($signer['enabled']) && ! $signer['enabled']) {
                // Ẩn tên nếu tắt
                $sheet->setCellValue($map[$i]['name'], '');

                continue;
            }
            $sheet->setCellValue($map[$i]['role'], (string) ($signer['role_line1'] ?? ''));
            if (! empty($map[$i]['role2'])) {
                $sheet->setCellValue($map[$i]['role2'], (string) ($signer['role_line2'] ?? ''));
            }
            $sheet->setCellValue($map[$i]['name'], (string) ($signer['name'] ?? ''));
        }

        $dateLine = (string) ($meta['date_line'] ?? '');
        if ($dateLine !== '') {
            $sheet->setCellValue('X'.$this->layoutRow(27), $dateLine);
        }
    }

    protected function bodyRowOffset(): int
    {
        return 0;
    }

    protected function layoutRow(int $classicRow): int
    {
        return $classicRow + $this->bodyRowOffset();
    }

    protected function resetMonthMerges(Worksheet $sheet, int $weekCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->firstWeekCol + $this->maxWeeks - 1);
        $rangePrefix = 'C7:'.$lastCol.'7';
        foreach ($sheet->getMergeCells() as $merge) {
            // Chỉ unmerge các merge nằm trên hàng 7, cột tuần
            if (preg_match('/^([A-Z]+)7:([A-Z]+)7$/', $merge, $m)) {
                $from = Coordinate::columnIndexFromString($m[1]);
                if ($from >= $this->firstWeekCol) {
                    try {
                        $sheet->unmergeCells($merge);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }
        // Clear month row values in week columns
        for ($wi = 0; $wi < $this->maxWeeks; $wi++) {
            $col = Coordinate::stringFromColumnIndex($this->firstWeekCol + $wi);
            $sheet->setCellValue($col.'7', null);
        }
    }

    protected function paintCellKeepBorder(Worksheet $sheet, string $coord, string $hex): void
    {
        $hex = strtoupper(ltrim($hex, '#'));
        if (! preg_match('/^[0-9A-F]{6}$/', $hex)) {
            $hex = '4EA1FF';
        }

        $style = $sheet->getStyle($coord);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($hex);
        $style->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $style->getFont()->setBold(true)->setSize(9);
        $style->getFont()->getColor()->setRGB($this->contrastText($hex));

        // Nếu ô template không còn border (fill none làm style lệch) — bổ sung thin
        $borders = $style->getBorders();
        if ($borders->getLeft()->getBorderStyle() === Border::BORDER_NONE
            && $borders->getRight()->getBorderStyle() === Border::BORDER_NONE
            && $borders->getTop()->getBorderStyle() === Border::BORDER_NONE
            && $borders->getBottom()->getBorderStyle() === Border::BORDER_NONE) {
            $borders->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
    }

    protected function contrastText(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luma < 0.48 ? 'FFFFFF' : '000000';
    }

    /**
     * @return list<array{start:Carbon,end:Carbon}>
     */
    protected function buildWeeks(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $guard = 0;
        while ($cursor->lte($end) && $guard < 40) {
            $weeks[] = [
                'start' => $cursor->copy(),
                'end' => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
            ];
            $cursor->addWeek();
            $guard++;
        }

        return $weeks ?: [[
            'start' => $start->copy()->startOfWeek(Carbon::MONDAY),
            'end' => $start->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ]];
    }

    /**
     * @param  Collection<int, object>  $cells
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return array<string, array{labels:list<string>}>
     */
    protected function buildGrid(Collection $cells, array $weeks): array
    {
        $weekIndexByMonday = [];
        foreach ($weeks as $wi => $week) {
            $weekIndexByMonday[$week['start']->format('Y-m-d')] = $wi;
        }
        $grid = [];
        foreach ($cells as $cell) {
            $date = Carbon::parse($cell->date)->startOfDay();
            $isoDow = (int) $date->dayOfWeekIso;
            if ($isoDow > 5) {
                continue;
            }
            $monday = $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            if (! isset($weekIndexByMonday[$monday])) {
                continue;
            }
            $wi = $weekIndexByMonday[$monday];
            if ($wi >= $this->maxWeeks) {
                continue;
            }
            $session = ((int) $cell->period) <= 5 ? 'am' : 'pm';
            $key = $wi.'|'.$isoDow.'|'.$session;
            if (! isset($grid[$key])) {
                $grid[$key] = ['labels' => []];
            }
            $label = trim((string) $cell->label);
            if ($label !== '' && ! in_array($label, $grid[$key]['labels'], true)) {
                $grid[$key]['labels'][] = $label;
            }
        }

        return $grid;
    }

    protected function resolveSignatureImage(string $relative): ?string
    {
        if ($relative === '') {
            return null;
        }
        $candidates = [
            storage_path('app/public/'.$relative),
            public_path('images/'.$relative),
            public_path($relative),
            public_path('images/signatures/lhl/'.basename($relative)),
            $relative,
        ];
        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function resolveTemplatePath(): string
    {
        $candidates = [
            config('lhl_export.template_xlsx'),
            resource_path('templates/lhl/Lich_Huan_Luyen_template.xlsx'),
            base_path('Lịch Huấn Luyện HK2 25-26.xlsx'),
        ];
        // Also try glob for mojibake filename
        foreach (glob(base_path('*.xlsx')) ?: [] as $f) {
            if (filesize($f) > 300000 && stripos(basename($f), 'HK2') !== false) {
                $candidates[] = $f;
            }
            if (filesize($f) > 300000 && (str_contains(basename($f), 'Hu') || str_contains(basename($f), 'Lich'))) {
                $candidates[] = $f;
            }
        }
        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Không tìm thấy file biểu mẫu LHL (Lịch Huấn Luyện HK2 25-26.xlsx).');
    }

    /**
     * @param  array<string, true>  $used
     */
    protected function uniqueTitle(string $base, array &$used): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]]/', '', $base) ?: 'LHL';
        $title = mb_substr($title, 0, 31);
        if (! isset($used[$title])) {
            $used[$title] = true;

            return $title;
        }
        $i = 2;
        while ($i < 100) {
            $suffix = '_'.$i;
            $candidate = mb_substr($title, 0, 31 - strlen($suffix)).$suffix;
            if (! isset($used[$candidate])) {
                $used[$candidate] = true;

                return $candidate;
            }
            $i++;
        }

        return mb_substr(uniqid('LHL'), 0, 31);
    }

    protected function cellLooksEmpty(Worksheet $sheet, string $coord): bool
    {
        $v = $sheet->getCell($coord)->getValue();

        return $v === null || trim((string) $v) === '';
    }
}
