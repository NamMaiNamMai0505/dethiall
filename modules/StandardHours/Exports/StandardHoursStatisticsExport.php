<?php

namespace Modules\StandardHours\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\StandardHours\Services\ReportService;
use Modules\StandardHours\Support\ReportDocumentLayout;
use Modules\StandardHours\Support\YearlyResultFormatter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StandardHoursStatisticsExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    private const LAST_COLUMN = 'P';

    public function __construct(
        private readonly Collection $results,
        private readonly string $year = '',
        private readonly ?string $fromDate = null,
        private readonly ?string $toDate = null,
        private readonly string $exportLevel = ReportDocumentLayout::LEVEL_PERSONAL,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        $level = ReportDocumentLayout::normalizeLevel($this->exportLevel);

        if ($level === ReportDocumentLayout::LEVEL_SCHOOL) {
            return 'Tong hop truong';
        }

        if ($level === ReportDocumentLayout::LEVEL_UNIT) {
            return 'Tong hop khoa';
        }

        if ($this->results->count() === 1) {
            $name = $this->results->first()->instructor->name ?? 'GV';

            return "Thong ke {$name}";
        }

        return $this->year ? "Thong ke {$this->year}" : 'Thong ke gio chuan';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 26,
            'C' => 12,
            'D' => 16,
            'E' => 14,
            'F' => 10,
            'G' => 12,
            'H' => 10,
            'I' => 12,
            'J' => 12,
            'K' => 10,
            'L' => 12,
            'M' => 10,
            'N' => 12,
            'O' => 10,
            'P' => 28,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = self::LAST_COLUMN;
                $level = ReportDocumentLayout::normalizeLevel($this->exportLevel);

                $dateRange = YearlyResultFormatter::yearDateRange(
                    $this->year,
                    $this->fromDate,
                    $this->toDate
                );

                $row = ReportDocumentLayout::writeOfficialHeader($sheet, $lastCol);
                $row = ReportDocumentLayout::writeReportTitle(
                    $sheet,
                    'Thống kê giờ chuẩn, giờ nghiên cứu khoa học của giảng viên',
                    '(Thời gian từ ngày '.$dateRange['from'].' đến ngày '.$dateRange['to'].')'
                        .' — '.ReportDocumentLayout::levelLabel($level),
                    $lastCol,
                    $row
                );

                $reportService = app(ReportService::class);
                $unitSummaries = $reportService->summarizeByUnit($this->results);
                $schoolSummary = $reportService->summarizeSchool($unitSummaries);

                $lastContentRow = match ($level) {
                    ReportDocumentLayout::LEVEL_SCHOOL => $this->writeSchoolLevel($sheet, $row, $unitSummaries, $schoolSummary),
                    ReportDocumentLayout::LEVEL_UNIT => $this->writeUnitLevel($sheet, $row, $unitSummaries),
                    default => $this->writePersonalLevel($sheet, $row),
                };

                ReportDocumentLayout::writeSignatureFooter($sheet, $lastContentRow, $lastCol);
            },
        ];
    }

    /**
     * Cấp cá nhân: bảng chi tiết GV (mẫu cũ).
     */
    private function writePersonalLevel(Worksheet $sheet, int $startRow): int
    {
        [, $dataStartRow] = $this->writeDetailTableHeader($sheet, $startRow, 'BẢNG CHI TIẾT GIẢNG VIÊN');

        return $this->writeDetailRows($sheet, $dataStartRow, $this->results);
    }

    /**
     * Cấp khoa: chi tiết toàn bộ GV + bảng tổng từng khoa (tách riêng).
     */
    private function writeUnitLevel(Worksheet $sheet, int $startRow, Collection $unitSummaries): int
    {
        [, $dataStartRow] = $this->writeDetailTableHeader($sheet, $startRow, 'BẢNG CHI TIẾT THEO KHOA');
        $lastDetailRow = $this->writeDetailRows($sheet, $dataStartRow, $this->results);

        $summaryStart = $lastDetailRow + 3;
        $summaryStart = $this->writeSectionTitle($sheet, $summaryStart, 'BẢNG TỔNG HỢP THEO KHOA');

        return $this->writeUnitSummaryTable($sheet, $summaryStart, $unitSummaries, false);
    }

    /**
     * Cấp trường: chỉ tổng từng khoa + bảng tổng toàn trường (tách riêng).
     */
    private function writeSchoolLevel(
        Worksheet $sheet,
        int $startRow,
        Collection $unitSummaries,
        array $schoolSummary
    ): int {
        $row = $this->writeSectionTitle($sheet, $startRow, 'BẢNG TỔNG HỢP THEO KHOA');
        $lastUnitRow = $this->writeUnitSummaryTable($sheet, $row, $unitSummaries, false);

        $schoolStart = $lastUnitRow + 3;
        $schoolStart = $this->writeSectionTitle($sheet, $schoolStart, 'BẢNG TỔNG HỢP TOÀN TRƯỜNG');

        return $this->writeUnitSummaryTable(
            $sheet,
            $schoolStart,
            collect([$schoolSummary + [
                'unit_name' => 'TOÀN TRƯỜNG',
                'unit_code' => '',
            ]]),
            true
        );
    }

    private function writeSectionTitle(Worksheet $sheet, int $row, string $title): int
    {
        $lastCol = self::LAST_COLUMN;
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        return $row + 1;
    }

    /**
     * @return array{0: int, 1: int} [headerRow2, dataStartRow]
     */
    private function writeDetailTableHeader(Worksheet $sheet, int $startRow, string $sectionTitle): array
    {
        $row = $this->writeSectionTitle($sheet, $startRow, $sectionTitle);
        $headerRow1 = $row;
        $headerRow2 = $row + 1;
        $lastCol = self::LAST_COLUMN;

        $sheet->setCellValue("A{$headerRow1}", 'TT');
        $sheet->setCellValue("B{$headerRow1}", 'Họ tên giáo viên');
        $sheet->setCellValue("C{$headerRow1}", 'Cấp bậc');
        $sheet->setCellValue("D{$headerRow1}", 'Chức vụ');
        $sheet->setCellValue("E{$headerRow1}", 'Đơn vị');
        $sheet->setCellValue("F{$headerRow1}", 'Giờ chuẩn - Tổng số');
        $sheet->setCellValue("I{$headerRow1}", 'Giờ chuẩn - Trực tiếp giảng dạy');
        $sheet->setCellValue("L{$headerRow1}", 'Giờ chuẩn - Quy đổi');
        $sheet->setCellValue("M{$headerRow1}", 'Giờ NCKH');
        $sheet->setCellValue("P{$headerRow1}", 'Ghi chú');

        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}");
        $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}");
        $sheet->mergeCells("C{$headerRow1}:C{$headerRow2}");
        $sheet->mergeCells("D{$headerRow1}:D{$headerRow2}");
        $sheet->mergeCells("E{$headerRow1}:E{$headerRow2}");
        $sheet->mergeCells("F{$headerRow1}:H{$headerRow1}");
        $sheet->mergeCells("I{$headerRow1}:K{$headerRow1}");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}");
        $sheet->mergeCells("M{$headerRow1}:O{$headerRow1}");
        $sheet->mergeCells("P{$headerRow1}:P{$headerRow2}");

        $sheet->setCellValue("F{$headerRow2}", 'Định mức');
        $sheet->setCellValue("G{$headerRow2}", 'Cá nhân có');
        $sheet->setCellValue("H{$headerRow2}", 'So sánh');
        $sheet->setCellValue("I{$headerRow2}", 'ĐM tối thiểu');
        $sheet->setCellValue("J{$headerRow2}", 'Cá nhân có');
        $sheet->setCellValue("K{$headerRow2}", 'So sánh');
        $sheet->setCellValue("M{$headerRow2}", 'Định mức');
        $sheet->setCellValue("N{$headerRow2}", 'Cá nhân có');
        $sheet->setCellValue("O{$headerRow2}", 'So sánh');

        $this->applyHeaderStyle($sheet, $headerRow1, $headerRow2);
        $sheet->getRowDimension($headerRow1)->setRowHeight(28);
        $sheet->getRowDimension($headerRow2)->setRowHeight(22);

        return [$headerRow2, $headerRow2 + 1];
    }

    private function writeDetailRows(Worksheet $sheet, int $dataStartRow, Collection $results): int
    {
        $lastCol = self::LAST_COLUMN;

        if ($results->isEmpty()) {
            return $dataStartRow - 1;
        }

        $index = 0;
        foreach ($results as $result) {
            $index++;
            $dataRow = $dataStartRow + $index - 1;
            $values = YearlyResultFormatter::toStatisticsRow($result, $index);

            foreach (range('A', $lastCol) as $colIndex => $column) {
                $sheet->setCellValue($column.$dataRow, $values[$colIndex]);
            }
        }

        $lastDataRow = $dataStartRow + $results->count() - 1;
        $this->applyDataStyle($sheet, $dataStartRow, $lastDataRow);

        return $lastDataRow;
    }

    /**
     * Summary table: same numeric layout, row labels = khoa / toàn trường.
     *
     * @param  Collection<int, array<string, mixed>>  $summaries
     */
    private function writeUnitSummaryTable(
        Worksheet $sheet,
        int $startRow,
        Collection $summaries,
        bool $isSchoolTotal
    ): int {
        $headerRow1 = $startRow;
        $headerRow2 = $startRow + 1;
        $lastCol = self::LAST_COLUMN;

        $sheet->setCellValue("A{$headerRow1}", 'TT');
        $sheet->setCellValue("B{$headerRow1}", $isSchoolTotal ? 'Phạm vi' : 'Khoa / Đơn vị');
        $sheet->setCellValue("C{$headerRow1}", 'Số GV');
        $sheet->setCellValue("D{$headerRow1}", '');
        $sheet->setCellValue("E{$headerRow1}", 'Mã ĐV');
        $sheet->setCellValue("F{$headerRow1}", 'Giờ chuẩn - Tổng số');
        $sheet->setCellValue("I{$headerRow1}", 'Giờ chuẩn - Trực tiếp giảng dạy');
        $sheet->setCellValue("L{$headerRow1}", 'Giờ chuẩn - Quy đổi');
        $sheet->setCellValue("M{$headerRow1}", 'Giờ NCKH');
        $sheet->setCellValue("P{$headerRow1}", 'Ghi chú');

        $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}");
        $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}");
        $sheet->mergeCells("C{$headerRow1}:C{$headerRow2}");
        $sheet->mergeCells("D{$headerRow1}:D{$headerRow2}");
        $sheet->mergeCells("E{$headerRow1}:E{$headerRow2}");
        $sheet->mergeCells("F{$headerRow1}:H{$headerRow1}");
        $sheet->mergeCells("I{$headerRow1}:K{$headerRow1}");
        $sheet->mergeCells("L{$headerRow1}:L{$headerRow2}");
        $sheet->mergeCells("M{$headerRow1}:O{$headerRow1}");
        $sheet->mergeCells("P{$headerRow1}:P{$headerRow2}");

        $sheet->setCellValue("F{$headerRow2}", 'Định mức');
        $sheet->setCellValue("G{$headerRow2}", 'Tổng có');
        $sheet->setCellValue("H{$headerRow2}", 'So sánh');
        $sheet->setCellValue("I{$headerRow2}", 'ĐM tối thiểu');
        $sheet->setCellValue("J{$headerRow2}", 'Tổng có');
        $sheet->setCellValue("K{$headerRow2}", 'So sánh');
        $sheet->setCellValue("M{$headerRow2}", 'Định mức');
        $sheet->setCellValue("N{$headerRow2}", 'Tổng có');
        $sheet->setCellValue("O{$headerRow2}", 'So sánh');

        $this->applyHeaderStyle($sheet, $headerRow1, $headerRow2, $isSchoolTotal ? 'FEF3C7' : 'DCFCE7');
        $sheet->getRowDimension($headerRow1)->setRowHeight(28);
        $sheet->getRowDimension($headerRow2)->setRowHeight(22);

        if ($summaries->isEmpty()) {
            return $headerRow2;
        }

        $dataStartRow = $headerRow2 + 1;
        $index = 0;

        foreach ($summaries as $summary) {
            $index++;
            $dataRow = $dataStartRow + $index - 1;

            $values = $isSchoolTotal
                ? YearlyResultFormatter::toSchoolTotalRow($summary)
                : YearlyResultFormatter::toUnitSummaryRow($summary, $index);

            foreach (range('A', $lastCol) as $colIndex => $column) {
                $sheet->setCellValue($column.$dataRow, $values[$colIndex]);
            }

            if ($isSchoolTotal) {
                $sheet->getStyle("A{$dataRow}:{$lastCol}{$dataRow}")->getFont()->setBold(true);
            }
        }

        $lastDataRow = $dataStartRow + $summaries->count() - 1;
        $this->applyDataStyle($sheet, $dataStartRow, $lastDataRow);

        return $lastDataRow;
    }

    private function applyHeaderStyle(
        Worksheet $sheet,
        int $headerRow1,
        int $headerRow2,
        string $fillColor = 'E8EEF7'
    ): void {
        $lastCol = self::LAST_COLUMN;
        $sheet->getStyle("A{$headerRow1}:{$lastCol}{$headerRow2}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fillColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    private function applyDataStyle(Worksheet $sheet, int $dataStartRow, int $lastDataRow): void
    {
        $lastCol = self::LAST_COLUMN;

        $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$dataStartRow}:E{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$dataStartRow}:O{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
