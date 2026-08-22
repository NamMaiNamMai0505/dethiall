<?php

namespace Modules\StandardHours\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\StandardHours\Services\ResearchReportService;
use Modules\StandardHours\Support\ReportDocumentLayout;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResearchHoursStatisticsExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** Detail table: A–I (thêm Giờ quy đổi) */
    private const LAST_COLUMN = 'I';

    /** Summary table: A–F */
    private const SUMMARY_LAST_COLUMN = 'F';

    public function __construct(
        private readonly Collection $rows,
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

        return match ($level) {
            ReportDocumentLayout::LEVEL_UNIT => 'NCKH - Khoa',
            ReportDocumentLayout::LEVEL_SCHOOL => 'NCKH - Truong',
            default => 'Thong ke NCKH',
        };
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 24,
            'C' => 40,
            'D' => 18,
            'E' => 12,
            'F' => 14,
            'G' => 22,
            'H' => 20,
            'I' => 14,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = self::LAST_COLUMN;
                $level = ReportDocumentLayout::normalizeLevel($this->exportLevel);

                $from = $this->fromDate ? date('d/m/Y', strtotime($this->fromDate)) : '__/__/____';
                $to = $this->toDate ? date('d/m/Y', strtotime($this->toDate)) : '__/__/____';

                $row = ReportDocumentLayout::writeOfficialHeader($sheet, $lastCol);
                $row = ReportDocumentLayout::writeReportTitle(
                    $sheet,
                    'Thống kê giờ nghiên cứu khoa học của Giảng Viên',
                    "(Thời gian từ ngày {$from} đến ngày {$to}) — ".ReportDocumentLayout::levelLabel($level),
                    $lastCol,
                    $row
                );

                $service = app(ResearchReportService::class);
                $unitSummaries = $service->summarizeByUnit($this->rows);
                $schoolSummary = $service->summarizeSchool($unitSummaries, $this->rows);

                $lastContentRow = match ($level) {
                    ReportDocumentLayout::LEVEL_SCHOOL => $this->writeSchoolLevel($sheet, $row, $unitSummaries, $schoolSummary),
                    ReportDocumentLayout::LEVEL_UNIT => $this->writeUnitLevel($sheet, $row, $unitSummaries),
                    default => $this->writePersonalLevel($sheet, $row),
                };

                ReportDocumentLayout::writeSignatureFooter($sheet, $lastContentRow, $lastCol);
            },
        ];
    }

    private function writePersonalLevel(Worksheet $sheet, int $startRow): int
    {
        $dataStart = $this->writeDetailHeader($sheet, $startRow, 'BẢNG CHI TIẾT NCKH');

        return $this->writeDetailRows($sheet, $dataStart, $this->rows);
    }

    private function writeUnitLevel(Worksheet $sheet, int $startRow, Collection $unitSummaries): int
    {
        $dataStart = $this->writeDetailHeader($sheet, $startRow, 'BẢNG CHI TIẾT THEO KHOA');
        $lastDetail = $this->writeDetailRows($sheet, $dataStart, $this->rows);

        $summaryStart = $this->writeSectionTitle(
            $sheet,
            $lastDetail + 3,
            'BẢNG TỔNG HỢP THEO KHOA',
            self::SUMMARY_LAST_COLUMN
        );

        return $this->writeSummaryTable($sheet, $summaryStart, $unitSummaries, false);
    }

    private function writeSchoolLevel(
        Worksheet $sheet,
        int $startRow,
        Collection $unitSummaries,
        array $schoolSummary
    ): int {
        $row = $this->writeSectionTitle($sheet, $startRow, 'BẢNG TỔNG HỢP THEO KHOA', self::SUMMARY_LAST_COLUMN);
        $lastUnit = $this->writeSummaryTable($sheet, $row, $unitSummaries, false);

        $schoolStart = $this->writeSectionTitle(
            $sheet,
            $lastUnit + 3,
            'BẢNG TỔNG HỢP TOÀN TRƯỜNG',
            self::SUMMARY_LAST_COLUMN
        );

        return $this->writeSummaryTable($sheet, $schoolStart, collect([$schoolSummary]), true);
    }

    private function writeSectionTitle(Worksheet $sheet, int $row, string $title, string $lastCol): int
    {
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

    private function writeDetailHeader(Worksheet $sheet, int $startRow, string $sectionTitle): int
    {
        $row = $this->writeSectionTitle($sheet, $startRow, $sectionTitle, self::LAST_COLUMN);
        $lastCol = self::LAST_COLUMN;

        $headers = [
            'STT',
            'Họ tên GV',
            'Tên sản phẩm nghiên cứu khoa học',
            'Vai trò',
            'Tỷ lệ đóng góp (%)',
            'Ngày nghiệm thu',
            'Nơi xuất bản',
            'Danh mục',
            'Giờ quy đổi',
        ];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).$row, $header);
        }

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);

        return $row + 1;
    }

    private function writeDetailRows(Worksheet $sheet, int $dataStartRow, Collection $rows): int
    {
        $lastCol = self::LAST_COLUMN;

        if ($rows->isEmpty()) {
            return $dataStartRow - 1;
        }

        $dataRow = $dataStartRow;
        $stt = 1;
        $lastRecordId = null;

        foreach ($rows as $member) {
            $record = $member->researchRecord;
            $showProduct = $lastRecordId !== $record?->id;
            $lastRecordId = $record?->id;

            $convertedHours = $member->converted_hours;
            if ($convertedHours === null && $record) {
                $convertedHours = $record->converted_hours;
            }

            $sheet->setCellValue("A{$dataRow}", $stt++);
            $sheet->setCellValue("B{$dataRow}", $member->instructor->name ?? '');
            $sheet->setCellValue("C{$dataRow}", $showProduct ? ($record->product_name ?? '') : '');
            $sheet->setCellValue("D{$dataRow}", $member->role ?? '');
            $sheet->setCellValue(
                "E{$dataRow}",
                $member->contribution_percent !== null ? (float) $member->contribution_percent : ''
            );
            $sheet->setCellValue("F{$dataRow}", $record?->acceptance_date?->format('d/m/Y') ?? '');
            $sheet->setCellValue("G{$dataRow}", $record->publication_place ?? '');
            $sheet->setCellValue("H{$dataRow}", $record->researchCategory->name ?? '');
            $sheet->setCellValue(
                "I{$dataRow}",
                $convertedHours !== null && $convertedHours !== '' ? (float) $convertedHours : 0
            );
            $dataRow++;
        }

        $lastDataRow = $dataRow - 1;
        $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
            'font' => ['size' => 10],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);
        $sheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$dataStartRow}:F{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$dataStartRow}:I{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $lastDataRow;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $summaries
     */
    private function writeSummaryTable(
        Worksheet $sheet,
        int $startRow,
        Collection $summaries,
        bool $isSchoolTotal
    ): int {
        $lastCol = self::SUMMARY_LAST_COLUMN;
        $headerRow = $startRow;

        $headers = [
            'STT',
            $isSchoolTotal ? 'Phạm vi' : 'Khoa / Đơn vị',
            'Số sản phẩm',
            'Số thành viên',
            'Tổng thời gian thực hiện (năm)',
            'Tổng giờ quy đổi',
        ];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).$headerRow, $header);
        }

        $fill = $isSchoolTotal ? 'FEF3C7' : 'DBEAFE';
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        if ($summaries->isEmpty()) {
            return $headerRow;
        }

        $dataRow = $headerRow + 1;
        $index = 0;

        foreach ($summaries as $summary) {
            $index++;
            $sheet->setCellValue("A{$dataRow}", $index);
            $sheet->setCellValue("B{$dataRow}", $summary['unit_name'] ?? 'Chưa phân khoa');
            $sheet->setCellValue("C{$dataRow}", (int) ($summary['product_count'] ?? 0));
            $sheet->setCellValue("D{$dataRow}", (int) ($summary['member_count'] ?? 0));
            $sheet->setCellValue("E{$dataRow}", round((float) ($summary['total_duration_years'] ?? 0), 2));
            $sheet->setCellValue("F{$dataRow}", round((float) ($summary['total_converted_hours'] ?? 0), 2));

            if ($isSchoolTotal) {
                $sheet->getStyle("A{$dataRow}:{$lastCol}{$dataRow}")->getFont()->setBold(true);
            }

            $dataRow++;
        }

        $lastDataRow = $dataRow - 1;
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.($headerRow + 1).":B{$lastDataRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return $lastDataRow;
    }
}
