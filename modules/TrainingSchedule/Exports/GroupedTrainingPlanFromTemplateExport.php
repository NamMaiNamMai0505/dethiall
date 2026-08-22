<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Biến thể được clone từ mẫu LHL chuẩn với ba slot cố định mỗi ngày:
 * 1-3, 4-5 và 6-9.
 */
class GroupedTrainingPlanFromTemplateExport extends TrainingPlanFromTemplateExport
{
    protected int $dataEndRow = 24;

    protected function bodyRowOffset(): int
    {
        return 5;
    }

    protected function resolveTemplatePath(): string
    {
        $path = config('lhl_export.template_xlsx_grouped');
        if (is_string($path) && is_file($path)) {
            return $path;
        }

        return parent::resolveTemplatePath();
    }

    protected function loadTemplateBook(string $templatePath): Spreadsheet
    {
        $book = parent::loadTemplateBook($templatePath);
        $this->prepareGroupedLayout($book->getSheet(0));

        return $book;
    }

    protected function prepareGroupedLayout(Worksheet $sheet): void
    {
        $styleSnapshots = [];
        $rowHeights = [];
        for ($day = 0; $day < 5; $day++) {
            foreach ([0, 1] as $sourceSlot) {
                $sourceRow = 10 + ($day * 2) + $sourceSlot;
                $rowHeights[$day][$sourceSlot] = $sheet->getRowDimension($sourceRow)->getRowHeight();
                for ($column = 1; $column <= 26; $column++) {
                    $coordinate = Coordinate::stringFromColumnIndex($column).$sourceRow;
                    $styleSnapshots[$day][$sourceSlot][$column] = $sheet->getStyle($coordinate)->exportArray();
                }
            }
        }

        // Dịch nguyên phần ghi chú, danh mục môn và chữ ký xuống 5 hàng.
        $sheet->insertNewRowBefore(20, 5);

        // Bỏ toàn bộ merge lịch mẫu cũ; mẫu mới chỉ merge cột Thứ theo 3 hàng/ngày.
        foreach ($sheet->getMergeCells() as $merge) {
            if ($this->mergeIntersectsBody($merge)) {
                $sheet->unmergeCells($merge);
            }
        }

        for ($day = 0; $day < 5; $day++) {
            $firstRow = 10 + ($day * 3);
            foreach ([0, 1, 2] as $slot) {
                $targetRow = $firstRow + $slot;
                $sourceSlot = $slot === 2 ? 1 : 0;
                $height = $rowHeights[$day][$sourceSlot] ?? -1;
                $sheet->getRowDimension($targetRow)->setRowHeight($height);

                for ($column = 1; $column <= 26; $column++) {
                    $coordinate = Coordinate::stringFromColumnIndex($column).$targetRow;
                    $sheet->setCellValue($coordinate, null);
                    $sheet->getStyle($coordinate)->applyFromArray(
                        $styleSnapshots[$day][$sourceSlot][$column]
                    );
                }
            }

            $sheet->mergeCells('A'.$firstRow.':A'.($firstRow + 2));
            $sheet->setCellValue('A'.$firstRow, (string) ($day + 2));
            $sheet->getStyle('A'.$firstRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            foreach (['1 ÷ 3', '4 ÷ 5', '6 ÷ 9'] as $slot => $label) {
                $sheet->setCellValue('B'.($firstRow + $slot), $label);
            }
        }
    }

    protected function mergeIntersectsBody(string $merge): bool
    {
        if (! preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $merge, $matches)) {
            return false;
        }

        $firstColumn = Coordinate::columnIndexFromString($matches[1]);
        $lastColumn = Coordinate::columnIndexFromString($matches[3]);
        $firstRow = (int) $matches[2];
        $lastRow = (int) $matches[4];

        return $firstColumn <= 26
            && $lastColumn >= 1
            && $firstRow <= $this->dataEndRow
            && $lastRow >= $this->dataStartRow;
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
        $grid = $this->buildGroupedGrid($cells, $weeks);
        $defaults = ['1 ÷ 3', '4 ÷ 5', '6 ÷ 9'];

        for ($isoDow = 1; $isoDow <= 5; $isoDow++) {
            $firstRow = $this->dataStartRow + (($isoDow - 1) * 3);
            for ($slot = 0; $slot < 3; $slot++) {
                $row = $firstRow + $slot;
                $ranges = [];
                for ($wi = 0; $wi < $weekCount; $wi++) {
                    foreach (($grid[$wi.'|'.$isoDow.'|'.$slot]['range_labels'] ?? []) as $range) {
                        $ranges[$range] = true;
                    }
                }
                $rangeLabels = array_keys($ranges);
                $sheet->setCellValue('B'.$row, $defaults[$slot]);

                for ($wi = 0; $wi < $weekCount; $wi++) {
                    $entry = $grid[$wi.'|'.$isoDow.'|'.$slot] ?? null;
                    if (! $entry) {
                        continue;
                    }

                    $coordinate = Coordinate::stringFromColumnIndex($this->firstWeekCol + $wi).$row;
                    $sheet->setCellValue($coordinate, implode("\n", $entry['labels']));
                    $sheet->getStyle($coordinate)->getFill()->setFillType(Fill::FILL_NONE);
                    $sheet->getStyle($coordinate)->getFont()
                        ->setBold(true)
                        ->setSize(9)
                        ->getColor()->setRGB('000000');
                    $sheet->getStyle($coordinate)->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            }
        }
    }

    /**
     * @param  Collection<int, object>  $cells
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return array<string, array{labels:list<string>,range_labels:list<string>}>
     */
    protected function buildGroupedGrid(Collection $cells, array $weeks): array
    {
        $weekIndexByMonday = [];
        foreach ($weeks as $weekIndex => $week) {
            $weekIndexByMonday[$week['start']->format('Y-m-d')] = $weekIndex;
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
            $weekIndex = $weekIndexByMonday[$monday];
            if ($weekIndex >= $this->maxWeeks) {
                continue;
            }

            $label = trim((string) ($cell->subject_label ?? $cell->label ?? ''));
            $periodStart = (int) $cell->period;
            $periodEnd = max($periodStart, (int) ($cell->period_end ?? $periodStart));
            $range = trim((string) ($cell->period_label ?? $periodStart));
            foreach ([[1, 3], [4, 5], [6, 9]] as $slot => [$slotStart, $slotEnd]) {
                if ($periodStart > $slotEnd || $periodEnd < $slotStart) {
                    continue;
                }
                $key = $weekIndex.'|'.$isoDow.'|'.$slot;
                $grid[$key] ??= ['labels' => [], 'range_labels' => []];
                if ($label !== '' && ! in_array($label, $grid[$key]['labels'], true)) {
                    $grid[$key]['labels'][] = $label;
                }
                if ($range !== '' && ! in_array($range, $grid[$key]['range_labels'], true)) {
                    $grid[$key]['range_labels'][] = $range;
                }
            }
        }

        return $grid;
    }

    protected function transformTemplateDrawingXml(string $xml): string
    {
        $xml = parent::transformTemplateDrawingXml($xml);

        return preg_replace_callback(
            '/<xdr:row>(\d+)<\/xdr:row>/',
            static function (array $matches): string {
                $row = (int) $matches[1];
                if ($row >= 19) {
                    $row += 5;
                }

                return '<xdr:row>'.$row.'</xdr:row>';
            },
            $xml
        ) ?? $xml;
    }
}
