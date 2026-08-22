<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Class\Models\ClassModel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassesSheet implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return list of all classes in the system
     */
    public function collection()
    {
        return ClassModel::query()
            ->with(['specialization'])
            ->orderBy('code')
            ->get()
            ->map(function ($class, $index) {
                return [
                    'stt' => $index + 1,
                    'code' => $class->code,
                    'name' => $class->name,
                    'specialization' => $class->specialization->name ?? 'N/A',
                    'level' => $class->level_text ?? 'N/A',
                    'status' => $class->is_active ? 'Hoạt động' : 'Tạm ngừng',
                    'created_at' => $class->created_at?->format('d/m/Y H:i') ?? 'N/A',
                ];
            });
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Lớp học';
    }

    /**
     * Define headers
     */
    public function headings(): array
    {
        return [
            'STT',
            'Mã lớp',
            'Tên lớp',
            'Ngành đào tạo',
            'Cấp độ',
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
            'B' => 15,  // Mã lớp
            'C' => 30,  // Tên lớp
            'D' => 25,  // Ngành đào tạo
            'E' => 15,  // Cấp độ
            'F' => 15,  // Trạng thái
            'G' => 20,  // Ngày tạo
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Add title above the table
        $sheet->insertNewRowBefore(1, 2);
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'DANH SÁCH LỚP HỌC TRONG HỆ THỐNG');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'], // Green-500
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Add note
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Lưu ý: Sử dụng chính xác mã lớp từ cột "Mã lớp" khi nhập dữ liệu học viên');
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
        $sheet->getStyle('A3:G3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'], // Green-500
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
            $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
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

            // Center align STT, Status columns
            $sheet->getStyle("A4:A{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $sheet->getStyle("F4:F{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Alternate row colors
            for ($row = 4; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
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
