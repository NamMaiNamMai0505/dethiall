<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet Lịch huấn luyện — bám mẫu HK2 25-26:
 * header org/title/meta lớp, lưới tuần, gạch chéo tiết/ngày, màu môn, chú thích, 3 chữ ký.
 */
class TrainingPlanExport implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    /** @var list<array{start:Carbon,end:Carbon}> */
    protected array $weeks = [];

    /** @var array<string, mixed> */
    protected array $meta;

    /**
     * @param  Collection<int, object{date:string,period:int,label:string,color:string,subject_id:?int}>  $cells
     * @param  Collection<int, object{id:int,code:string,name:string,faculty:string,credits:int,theory:int,practice:int,exam:int,color:string}>  $legendSubjects
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        protected string $sheetTitle,
        protected string $className,
        protected string $semesterLabel,
        protected string $academicYear,
        protected Carbon $startDate,
        protected Carbon $endDate,
        protected Collection $cells,
        protected Collection $legendSubjects,
        array $meta = [],
    ) {
        $this->weeks = $this->buildWeeks();
        $this->meta = $this->normalizeMeta($meta);
    }

    public function title(): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]]/', '', $this->sheetTitle) ?: 'LHL';

        return mb_substr((string) $title, 0, 31);
    }

    public function array(): array
    {
        return [];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 8, 'B' => 11];
        $weekCount = max(1, count($this->weeks));
        for ($i = 3; $i <= $weekCount + 2 + 6; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 11;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        return $sheet;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->render($event->sheet->getDelegate());
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function normalizeMeta(array $meta): array
    {
        $cfg = config('lhl_export', []);
        $defaultSigners = collect($cfg['signers'] ?? [])->map(fn ($s) => [
            'key' => $s['key'] ?? '',
            'role_line1' => $s['role_line1'] ?? '',
            'role_line2' => $s['role_line2'] ?? '',
            'name' => $s['name'] ?? '',
            'image' => $s['image'] ?? '',
            'enabled' => true,
        ])->values()->all();

        $signers = $meta['signers'] ?? $defaultSigners;
        if (! is_array($signers) || $signers === []) {
            $signers = $defaultSigners;
        }

        return [
            'org_left' => (string) ($meta['org_left'] ?? $meta['header_left'] ?? ($cfg['org_left'] ?? '')),
            'title' => (string) ($meta['title'] ?? ($cfg['title'] ?? 'LỊCH HUẤN LUYỆN')),
            'semester_line' => (string) ($meta['semester_line'] ?? ''),
            'respect_line' => (string) ($meta['respect_line'] ?? ($cfg['respect_line'] ?? '')),
            'unit_name' => (string) ($meta['unit_name'] ?? ''),
            'class_size' => (string) ($meta['class_size'] ?? ''),
            'groups' => (string) ($meta['groups'] ?? ''),
            'class_leader' => (string) ($meta['class_leader'] ?? ''),
            'classroom' => (string) ($meta['classroom'] ?? ''),
            'note' => (string) ($meta['note'] ?? ($cfg['note'] ?? '')),
            'date_line' => (string) ($meta['date_line'] ?? ('Ngày     tháng      năm '.$this->endDate->format('Y'))),
            'signers' => array_values($signers),
        ];
    }

    protected function render(Worksheet $sheet): void
    {
        $weeks = $this->weeks;
        $weekCount = max(1, count($weeks));
        $firstDataCol = 3; // C
        $lastColIndex = max($firstDataCol + $weekCount - 1, 24);
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
        $gridLastColIndex = $firstDataCol + $weekCount - 1;
        $gridLastCol = Coordinate::stringFromColumnIndex($gridLastColIndex);

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getDefaultRowDimension()->setRowHeight(18);

        $org = $this->meta['org_left'];
        $title = $this->meta['title'] ?: 'LỊCH HUẤN LUYỆN';
        $semesterLine = trim((string) $this->meta['semester_line']);
        if ($semesterLine === '') {
            $semesterLine = trim($this->semesterLabel.' năm học '.$this->academicYear);
        }

        // —— Header ——
        $sheet->setCellValue('A1', $org);
        $sheet->mergeCells('A1:B2');
        $sheet->setCellValue('C1', $title);
        $sheet->mergeCells('C1:'.$gridLastCol.'1');
        $sheet->setCellValue('C2', $semesterLine);
        $sheet->mergeCells('C2:'.$gridLastCol.'2');

        // Meta lớp (cột phải — giống mẫu V/W)
        $metaCol = Coordinate::stringFromColumnIndex(min($gridLastColIndex + 1, $lastColIndex));
        $metaCol2 = Coordinate::stringFromColumnIndex(min($gridLastColIndex + 2, $lastColIndex));
        $sheet->setCellValue($metaCol.'1', 'Lớp: '.$this->className);
        if ($this->meta['unit_name'] !== '') {
            $sheet->setCellValue($metaCol.'2', 'Đơn vị: '.$this->meta['unit_name']);
        }
        if ($this->meta['class_size'] !== '' || $this->meta['groups'] !== '') {
            $sheet->setCellValue($metaCol.'3', 'Sĩ số: '.$this->meta['class_size']);
            if ($this->meta['groups'] !== '') {
                $sheet->setCellValue($metaCol2.'3', 'Số tổ: '.$this->meta['groups']);
            }
        }
        if ($this->meta['class_leader'] !== '') {
            $sheet->setCellValue($metaCol.'4', 'CN lớp: '.$this->meta['class_leader']);
        }
        if ($this->meta['classroom'] !== '') {
            $sheet->setCellValue($metaCol.'5', 'Phòng học: '.$this->meta['classroom']);
        }

        $sheet->setCellValue('A3', 'Lớp: '.$this->className);
        $sheet->mergeCells('A3:'.Coordinate::stringFromColumnIndex(max(3, $gridLastColIndex - 4)).'3');
        $sheet->setCellValue('A4', 'Từ '.$this->startDate->format('d/m/Y').' đến '.$this->endDate->format('d/m/Y'));
        $sheet->mergeCells('A4:'.Coordinate::stringFromColumnIndex(max(3, $gridLastColIndex - 4)).'4');
        $sheet->setCellValue('A5', $this->meta['respect_line']);
        $sheet->mergeCells('A5:'.$gridLastCol.'5');

        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle('A1:'.$gridLastCol.'2')->getFont()->setBold(true)->setName('Times New Roman');
        $sheet->getStyle('A1:'.$gridLastCol.'2')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C1')->getFont()->setSize(16);
        $sheet->getStyle('A3:A5')->getFont()->setBold(true)->setName('Times New Roman');

        // —— Grid header rows 7–9 ——
        $sheet->setCellValue('A7', 'Thứ');
        $sheet->mergeCells('A7:A9');
        $sheet->setCellValue('B7', 'Tháng');
        $sheet->setCellValue('B8', 'Tuần');
        // Góc Tiết / Ngày — gạch chéo
        $sheet->setCellValue('B9', "Ngày\nTiết");
        $this->applyDiagonal($sheet, 'B9');

        $monthSpans = [];
        foreach ($weeks as $wi => $week) {
            $colIndex = $firstDataCol + $wi;
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $monthKey = $week['start']->format('Y-n');
            if (! isset($monthSpans[$monthKey])) {
                $monthSpans[$monthKey] = [$colIndex, $colIndex, (int) $week['start']->format('n')];
            } else {
                $monthSpans[$monthKey][1] = $colIndex;
            }

            $sheet->setCellValue($col.'8', $wi + 1);
            // Hai dòng ngày + gạch chéo (mẫu: 02 ↘ 08)
            $sheet->setCellValue($col.'9', $week['start']->format('d')."\n".$week['end']->format('d'));
            $this->applyDiagonal($sheet, $col.'9');
            $sheet->getStyle($col.'9')->getAlignment()
                ->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach ($monthSpans as $span) {
            [$from, $to, $monthNum] = $span;
            $fromCol = Coordinate::stringFromColumnIndex($from);
            $toCol = Coordinate::stringFromColumnIndex($to);
            $sheet->setCellValue($fromCol.'7', $monthNum);
            if ($from !== $to) {
                $sheet->mergeCells($fromCol.'7:'.$toCol.'7');
            }
        }

        $sheet->getStyle('A7:'.$gridLastCol.'9')->getFont()->setBold(true)->setName('Times New Roman');
        $sheet->getStyle('A7:'.$gridLastCol.'9')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(7)->setRowHeight(22);
        $sheet->getRowDimension(8)->setRowHeight(18);
        $sheet->getRowDimension(9)->setRowHeight(32);

        $grid = $this->buildGrid($weeks);

        $row = 10;
        for ($isoDow = 1; $isoDow <= 5; $isoDow++) {
            $amRow = $row;
            $pmRow = $row + 1;
            $vnDay = $isoDow + 1;
            $sheet->setCellValue('A'.$amRow, (string) $vnDay);
            $sheet->mergeCells('A'.$amRow.':A'.$pmRow);
            $sheet->setCellValue('B'.$amRow, '1 ÷ 5');
            $sheet->setCellValue('B'.$pmRow, '6 ÷ 9');
            $sheet->getStyle('A'.$amRow.':B'.$pmRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A'.$amRow)->getFont()->setBold(true);

            foreach ($weeks as $wi => $week) {
                $col = Coordinate::stringFromColumnIndex($firstDataCol + $wi);
                foreach (['am' => $amRow, 'pm' => $pmRow] as $session => $r) {
                    $key = $wi.'|'.$isoDow.'|'.$session;
                    $entry = $grid[$key] ?? null;
                    if (! $entry) {
                        continue;
                    }
                    $text = implode("\n", $entry['labels']);
                    $sheet->setCellValue($col.$r, $text);
                    $this->paintCell($sheet, $col.$r, $entry['color'], $text !== '');
                }
            }

            $sheet->getRowDimension($amRow)->setRowHeight(36);
            $sheet->getRowDimension($pmRow)->setRowHeight(36);
            $row += 2;
        }

        $gridEndRow = $row - 1;
        $sheet->getStyle('A7:'.$gridLastCol.$gridEndRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        // re-apply diagonal after allBorders (allBorders can reset diagonal)
        $this->applyDiagonal($sheet, 'B9');
        foreach ($weeks as $wi => $week) {
            $col = Coordinate::stringFromColumnIndex($firstDataCol + $wi);
            $this->applyDiagonal($sheet, $col.'9');
        }
        $sheet->getStyle('A7:'.$gridLastCol.'9')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F2F2F2');

        // —— Ghi chú + legend ——
        $noteRow = $gridEndRow + 2;
        $sheet->setCellValue('A'.$noteRow, 'Ghi chú:');
        $sheet->setCellValue('C'.$noteRow, $this->meta['note']);
        $sheet->mergeCells('C'.$noteRow.':'.$gridLastCol.$noteRow);
        $sheet->getStyle('A'.$noteRow)->getFont()->setBold(true);

        $legendHeader = $noteRow + 2;
        $sheet->setCellValue('A'.$legendHeader, 'Mã');
        $sheet->setCellValue('B'.$legendHeader, 'TÊN MÔN HỌC');
        $sheet->mergeCells('B'.$legendHeader.':D'.$legendHeader);
        $sheet->setCellValue('E'.$legendHeader, 'KHOA');
        $sheet->setCellValue('F'.$legendHeader, 'TÍN CHỈ');
        $sheet->setCellValue('G'.$legendHeader, 'Thời gian học tập (giờ)');
        $sheet->mergeCells('G'.$legendHeader.':J'.$legendHeader);
        $sheet->setCellValue('K'.$legendHeader, 'Ghi chú');
        $sheet->setCellValue('M'.$legendHeader, 'KÝ HIỆU CHUNG:');
        $sheet->setCellValue('N'.($legendHeader + 1), 'SHCB: Sinh hoạt chi bộ');

        $subHeader = $legendHeader + 1;
        $sheet->setCellValue('G'.$subHeader, 'LT');
        $sheet->setCellValue('H'.$subHeader, 'TH');
        $sheet->setCellValue('I'.$subHeader, 'Thi');
        $sheet->setCellValue('J'.$subHeader, 'Tổng');
        $sheet->getStyle('A'.$legendHeader.':J'.$subHeader)->getFont()->setBold(true);
        $sheet->getStyle('A'.$legendHeader.':J'.$subHeader)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->mergeCells('A'.$legendHeader.':A'.$subHeader);
        $sheet->mergeCells('B'.$legendHeader.':D'.$subHeader);
        $sheet->mergeCells('E'.$legendHeader.':E'.$subHeader);
        $sheet->mergeCells('F'.$legendHeader.':F'.$subHeader);
        $sheet->mergeCells('K'.$legendHeader.':K'.$subHeader);

        $dataStart = $subHeader + 1;
        $r = $dataStart;
        foreach ($this->legendSubjects as $subj) {
            $hex = ltrim((string) $subj->color, '#') ?: '4EA1FF';
            $sheet->setCellValue('A'.$r, $subj->code);
            $sheet->setCellValue('B'.$r, $subj->name);
            $sheet->mergeCells('B'.$r.':D'.$r);
            $sheet->setCellValue('E'.$r, $subj->faculty);
            $sheet->setCellValue('F'.$r, $subj->credits);
            $sheet->setCellValue('G'.$r, $subj->theory);
            $sheet->setCellValue('H'.$r, $subj->practice);
            $sheet->setCellValue('I'.$r, $subj->exam);
            $sheet->setCellValue('J'.$r, '=SUM(G'.$r.':I'.$r.')');
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $c) {
                $this->paintCell($sheet, $c.$r, $hex, true);
            }
            $r++;
        }
        $dataEnd = max($dataStart, $r - 1);

        if ($this->legendSubjects->isNotEmpty()) {
            $sheet->setCellValue('A'.$r, 'Tổng số');
            $sheet->setCellValue('F'.$r, '=SUM(F'.$dataStart.':F'.$dataEnd.')');
            $sheet->setCellValue('G'.$r, '=SUM(G'.$dataStart.':G'.$dataEnd.')');
            $sheet->setCellValue('H'.$r, '=SUM(H'.$dataStart.':H'.$dataEnd.')');
            $sheet->setCellValue('I'.$r, '=SUM(I'.$dataStart.':I'.$dataEnd.')');
            $sheet->setCellValue('J'.$r, '=SUM(J'.$dataStart.':J'.$dataEnd.')');
            $sheet->getStyle('A'.$r.':J'.$r)->getFont()->setBold(true);
            $totalRow = $r;
        } else {
            $totalRow = $dataEnd;
        }

        $sheet->getStyle('A'.$legendHeader.':J'.$totalRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // —— 3 chữ ký (mẫu: NGƯỜI LÀM LỊCH | KT. TRƯỞNG PHÒNG | KT. HIỆU TRƯỞNG) ——
        $signTitleRow = $dataStart + 2;
        $signNameRow = $dataStart + 8;
        $dateRow = $dataStart + 1;
        $sheet->setCellValue('X'.$dateRow, $this->meta['date_line']);

        $signCols = [
            0 => ['title' => 'L', 'name' => 'L', 'img' => 'L'],
            1 => ['title' => 'S', 'name' => 'S', 'img' => 'P'],
            2 => ['title' => 'X', 'name' => 'X', 'img' => 'V'],
        ];
        // Prefer columns after legend if many weeks
        if ($gridLastColIndex >= 18) {
            $signCols = [
                0 => ['title' => 'L', 'name' => 'L', 'img' => 'L'],
                1 => ['title' => 'R', 'name' => 'R', 'img' => 'R'],
                2 => ['title' => 'X', 'name' => 'X', 'img' => 'X'],
            ];
        }

        $signers = $this->meta['signers'];
        foreach ($signers as $i => $signer) {
            if (! empty($signer['enabled']) === false && array_key_exists('enabled', $signer) && ! $signer['enabled']) {
                continue;
            }
            $cols = $signCols[$i] ?? null;
            if (! $cols) {
                continue;
            }
            $role = trim(($signer['role_line1'] ?? '')."\n".($signer['role_line2'] ?? ''));
            $sheet->setCellValue($cols['title'].$signTitleRow, $signer['role_line1'] ?? '');
            if (! empty($signer['role_line2'])) {
                $sheet->setCellValue($cols['title'].($signTitleRow + 1), $signer['role_line2']);
            }
            $sheet->setCellValue($cols['name'].$signNameRow, $signer['name'] ?? '');
            $sheet->getStyle($cols['title'].$signTitleRow.':'.$cols['name'].$signNameRow)
                ->getFont()->setBold(true)->setName('Times New Roman');
            $sheet->getStyle($cols['title'].$signTitleRow.':'.$cols['name'].$signNameRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

            $imgRel = (string) ($signer['image'] ?? '');
            $imgPath = $this->resolveSignatureImage($imgRel);
            if ($imgPath) {
                try {
                    $drawing = new Drawing;
                    $drawing->setName($signer['key'] ?? ('signer'.$i));
                    $drawing->setPath($imgPath);
                    $drawing->setCoordinates($cols['img'].($signTitleRow + 2));
                    $drawing->setHeight(70);
                    $drawing->setWorksheet($sheet);
                } catch (\Throwable $e) {
                    // skip broken image
                }
            }
        }
    }

    protected function applyDiagonal(Worksheet $sheet, string $coord): void
    {
        $borders = $sheet->getStyle($coord)->getBorders();
        $borders->getDiagonal()->setBorderStyle(Border::BORDER_THIN);
        $borders->setDiagonalDirection(Borders::DIAGONAL_DOWN);
        $sheet->getStyle($coord)->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
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
            $relative,
        ];
        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return array<string, array{labels:list<string>,color:string}>
     */
    protected function buildGrid(array $weeks): array
    {
        $weekIndexByMonday = [];
        foreach ($weeks as $wi => $week) {
            $weekIndexByMonday[$week['start']->format('Y-m-d')] = $wi;
        }

        $grid = [];
        foreach ($this->cells as $cell) {
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
            $session = ((int) $cell->period) <= 5 ? 'am' : 'pm';
            $key = $wi.'|'.$isoDow.'|'.$session;
            if (! isset($grid[$key])) {
                $grid[$key] = ['labels' => [], 'color' => ltrim((string) $cell->color, '#') ?: '4EA1FF'];
            }
            $label = trim((string) $cell->label);
            if ($label !== '' && ! in_array($label, $grid[$key]['labels'], true)) {
                $grid[$key]['labels'][] = $label;
            }
            if (! empty($cell->color)) {
                $grid[$key]['color'] = ltrim((string) $cell->color, '#');
            }
        }

        return $grid;
    }

    /**
     * @return list<array{start:Carbon,end:Carbon}>
     */
    protected function buildWeeks(): array
    {
        $start = $this->startDate->copy()->startOfDay();
        $end = $this->endDate->copy()->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $guard = 0;
        while ($cursor->lte($end) && $guard < 40) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $weeks[] = [
                'start' => $cursor->copy(),
                'end' => $weekEnd,
            ];
            $cursor->addWeek();
            $guard++;
        }

        return $weeks ?: [[
            'start' => $start->copy()->startOfWeek(Carbon::MONDAY),
            'end' => $start->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ]];
    }

    protected function paintCell(Worksheet $sheet, string $coord, string $hex, bool $bold): void
    {
        $hex = strtoupper(ltrim($hex, '#'));
        if (! preg_match('/^[0-9A-F]{6}$/', $hex)) {
            $hex = '4EA1FF';
        }

        $sheet->getStyle($coord)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($hex);

        $sheet->getStyle($coord)->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $font = $sheet->getStyle($coord)->getFont();
        $font->setSize(9)->setName('Times New Roman');
        if ($bold) {
            $font->setBold(true);
        }
        $font->getColor()->setRGB($this->contrastText($hex));
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
}
