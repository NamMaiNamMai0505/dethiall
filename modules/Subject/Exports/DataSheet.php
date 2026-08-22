<?php

namespace Modules\Subject\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return sample data for the template
     */
    public function array(): array
    {
        return [
            [
                'M009K2',                                   // Mã môn học
                'K2',                                       // Mã khoa
                'Lý luận chính trị',                       // Tên môn học
                'LLCT',                                    // Viết tắt
                '#0070C0',                                 // Màu
                'Môn học lý luận chính trị cơ bản',        // Mô tả
                4,                                         // Số tín chỉ
                78,                                        // Số Tiết lý thuyết
                32,                                        // Số Tiết thực hành
                16,                                        // Số Tiết tự học
                10,                                        // Số Tiết thi
                'Cơ bản',                                  // Cấp độ
                'Học kỳ 1',                                // Học kỳ
                'Thi viết',                                // Phương pháp đánh giá
            ],
            [
                'M010K2',
                'K2',
                'Thuốc thông thường',
                'TTT',
                '#00B050',
                'Kiến thức thuốc thông thường',
                2,
                30,
                15,
                10,
                5,
                'Cơ bản',
                'Học kỳ 1',
                'Thi viết',
            ],
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Dữ liệu môn học';
    }

    /**
     * Define headers
     */
    public function headings(): array
    {
        return [
            'Mã môn học',
            'Mã khoa',
            'Tên môn học',
            'Viết tắt',
            'Màu',
            'Mô tả',
            'Số tín chỉ',
            'Số Tiết lý thuyết',
            'Số Tiết thực hành',
            'Số Tiết tự học',
            'Số Tiết thi',
            'Cấp độ',
            'Học kỳ',
            'Phương pháp đánh giá',
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Mã môn học
            'B' => 12,  // Mã khoa
            'C' => 30,  // Tên môn học
            'D' => 12,  // Viết tắt
            'E' => 12,  // Màu
            'F' => 40,  // Mô tả
            'G' => 12,  // Số tín chỉ
            'H' => 15,  // Số Tiết lý thuyết
            'I' => 15,  // Số Tiết thực hành
            'J' => 15,  // Số Tiết tự học
            'K' => 15,  // Số Tiết thi
            'L' => 15,  // Cấp độ
            'M' => 15,  // Học kỳ
            'N' => 25,  // Phương pháp đánh giá
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Blue-600
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

        // Style sample data rows
        $sheet->getStyle('A2:N3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'], // Gray-100
            ],
        ]);

        // Set row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        return $sheet;
    }
}
