<?php

namespace Modules\TrainingSchedule\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleDetailsExport implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    protected $scheduleData;

    protected $startDate;

    protected $endDate;

    protected $user;

    public function __construct($scheduleData, $startDate, $endDate, $user)
    {
        $this->scheduleData = $scheduleData;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->user = $user;
    }

    /**
     * Return data as array (only the data rows, header/footer handled separately)
     */
    public function array(): array
    {
        return [];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Lịch học';
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Ngày
            'B' => 12,  // Lớp
            'C' => 25,  // Tiết 1
            'D' => 25,  // Tiết 2
            'E' => 25,  // Tiết 3
            'F' => 25,  // Tiết 4
            'G' => 25,  // Tiết 5
            'H' => 25,  // Tiết 6
            'I' => 25,  // Tiết 7
            'J' => 25,  // Tiết 8
            'K' => 25,  // Tiết 9
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return $sheet;
    }

    /**
     * Get organization/unit name based on user role
     */
    protected function getOrganizationName(): string
    {
        // Super admin: School name
        if ($this->user->hasRole('super-admin')) {
            return 'Trường Cao Đẳng Hậu Cần 2';
        }

        // Student: Show class name
        if ($this->user->isStudent() && $this->user->class) {
            return $this->user->class->name;
        }

        // Instructor: Show instructor name
        if ($this->user->isInstructor() && $this->user->instructor) {
            return 'Giáo viên ' . $this->user->instructor->name;
        }

        // Default: School name
        return 'Trường Cao Đẳng Hậu Cần 2';
    }

    /**
     * Register events for building the complete template
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $data = $this->scheduleData->toArray();

                // Set organization name based on user role
                $organizationName = $this->getOrganizationName();

                // === HEADER SECTION ===
                // Rows 1-2: Title block (merged A1:C2)
                $sheet->setCellValue('A1', "TỔNG CỤC HẬU - CẦN KỸ THUẬT\nTRƯỜNG CAO ĐẲNG HẬU CẦN 2");
                $sheet->mergeCells('A1:C2');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

                // Note: D1 and D2 are inside the A1:C1 and C2:C2 merged ranges,
                // so we must not merge D1:D2 (overlapping merges cause errors).
                // If spacing is needed, consider adjusting the A1:C1/C2:C2 ranges or
                // placing content in another column.

                // Main title: merge across D1:G2 to match template layout
                $sheet->setCellValue('D1', 'LỊCH ĐÀO TẠO');
                $sheet->mergeCells('D1:G2');
                $sheet->getStyle('D1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 3: Organization/Unit
                $sheet->setCellValue('D3', 'Đơn vị/Cá nhân: ' . $organizationName);
                $sheet->mergeCells('D3:G3');
                $sheet->getStyle('D3')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 4: Date range
                $startDateFormatted = date('d/m/Y', strtotime($this->startDate));
                $endDateFormatted = date('d/m/Y', strtotime($this->endDate));
                $sheet->setCellValue('D4', "Thời gian: Từ ngày {$startDateFormatted} đến ngày {$endDateFormatted}");
                $sheet->mergeCells('D4:G4');
                $sheet->getStyle('D4')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // === DATA SECTION ===
                $dataStartRow = 5;

                // Header row for data table
                $headers = ['Ngày', 'Lớp', 'Tiết 1', 'Tiết 2', 'Tiết 3', 'Tiết 4', 'Tiết 5', 'Tiết 6', 'Tiết 7', 'Tiết 8', 'Tiết 9'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $dataStartRow, $header);
                    $col++;
                }

                // Style header row
                $sheet->getStyle("A{$dataStartRow}:K{$dataStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6'],
                    ],
                ]);
                $sheet->getRowDimension($dataStartRow)->setRowHeight(25);

                // Data rows
                if (!empty($data)) {
                    $rowIndex = $dataStartRow + 1;
                    $dateGroups = [];

                    foreach ($data as $row) {
                        $sheet->setCellValue("A{$rowIndex}", $row['date']);
                        $sheet->setCellValue("B{$rowIndex}", $row['class_name']);
                        $sheet->setCellValue("C{$rowIndex}", $row['period_1']);
                        $sheet->setCellValue("D{$rowIndex}", $row['period_2']);
                        $sheet->setCellValue("E{$rowIndex}", $row['period_3']);
                        $sheet->setCellValue("F{$rowIndex}", $row['period_4']);
                        $sheet->setCellValue("G{$rowIndex}", $row['period_5']);
                        $sheet->setCellValue("H{$rowIndex}", $row['period_6']);
                        $sheet->setCellValue("I{$rowIndex}", $row['period_7']);
                        $sheet->setCellValue("J{$rowIndex}", $row['period_8']);
                        $sheet->setCellValue("K{$rowIndex}", $row['period_9']);

                        // Track date groups for merging
                        if (!isset($dateGroups[$row['date']])) {
                            $dateGroups[$row['date']] = ['start' => $rowIndex, 'end' => $rowIndex];
                        } else {
                            $dateGroups[$row['date']]['end'] = $rowIndex;
                        }

                        // Style data row
                        $sheet->getStyle("A{$rowIndex}:K{$rowIndex}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_TOP,
                                'wrapText' => true,
                            ],
                        ]);
                        $sheet->getRowDimension($rowIndex)->setRowHeight(40);

                        $rowIndex++;
                    }

                    // Merge date cells for same dates
                    foreach ($dateGroups as $dateGroup) {
                        if ($dateGroup['end'] > $dateGroup['start']) {
                            $sheet->mergeCells("A{$dateGroup['start']}:A{$dateGroup['end']}");
                            $sheet->getStyle("A{$dateGroup['start']}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        }
                    }

                    $dataEndRow = $rowIndex - 1;
                } else {
                    $dataEndRow = $dataStartRow;
                }

                // === FOOTER SECTION ===
                $footerStartRow = $dataEndRow + 2;

                // Row: Notes title
                $sheet->setCellValue("A{$footerStartRow}", 'Nội nhận:');
                $sheet->getStyle("A{$footerStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);

                // Row+1: Note 1
                $sheet->setCellValue('A' . ($footerStartRow + 1), '- TT.BGH;');

                // Row+2: Note 2
                $sheet->setCellValue('A' . ($footerStartRow + 2), '- CQ, ĐV');

                // Row+3: Note 3
                $sheet->setCellValue('A' . ($footerStartRow + 3), '- https://tkb.cdhc2.edu.vn');

                // Right side: Signature section
                $signatureRow = $footerStartRow;
                $currentDate = date('d');
                $currentMonth = date('m');
                $currentYear = date('Y');

                $sheet->setCellValue("H{$signatureRow}", "Thành phố Hồ Chí Minh, ngày {$currentDate} tháng {$currentMonth} năm {$currentYear}");
                $sheet->mergeCells("H{$signatureRow}:K{$signatureRow}");
                $sheet->getStyle("H{$signatureRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->setCellValue('H' . ($signatureRow + 1), 'HIỆU TRƯỞNG');
                $sheet->mergeCells('H' . ($signatureRow + 1) . ':K' . ($signatureRow + 1));
                $sheet->getStyle('H' . ($signatureRow + 1))->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }
}
