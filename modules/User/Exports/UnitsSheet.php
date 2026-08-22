<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Unit\Models\Unit;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitsSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return list of all units in the system
     */
    public function collection()
    {
        return Unit::query()
            ->orderBy('name')
            ->get()
            ->map(function ($unit, $index) {
                return [
                    'stt' => $index + 1,
                    'name' => $unit->name,
                    'code' => $unit->code ?? 'N/A',
                    'status' => $unit->status === 'active' ? 'Hoạt động' : 'Tạm ngừng',
                    'created_at' => $unit->created_at?->format('d/m/Y H:i') ?? 'N/A',
                ];
            });
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Đơn vị';
    }

    /**
     * Define headers
     */
    public function headings(): array
    {
        return [
            'STT',
            'Tên đơn vị',
            'Mã đơn vị',
            'Trạng thái',
            'Ngày tạo',
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // STT
            'B' => 35,  // Tên đơn vị
            'C' => 15,  // Mã đơn vị
            'D' => 15,  // Trạng thái
            'E' => 20,  // Ngày tạo
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Add title above the table
        $sheet->insertNewRowBefore(1, 2);
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'DANH SÁCH ĐỌN VỊ TRONG HỆ THỐNG');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F97316'], // Orange-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Add note
        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Lưu ý: Sử dụng chính xác tên đơn vị từ cột "Tên đơn vị" khi nhập dữ liệu người dùng');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '1F2937'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'], // Yellow-100
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style header row (now row 3)
        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F97316'], // Orange-600
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Get the last row with data
        $lastRow = $sheet->getHighestRow();

        // Style data rows
        if ($lastRow > 3) {
            $sheet->getStyle("A4:E{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center align STT column and status column
            $sheet->getStyle("A4:A{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $sheet->getStyle("D4:D{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Alternate row colors
            for ($row = 4; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9FAFB'], // Gray-50
                        ],
                    ]);
                }
            }
        }

        // Set row height
        $sheet->getRowDimension(3)->setRowHeight(25);

        return $sheet;
    }
}
