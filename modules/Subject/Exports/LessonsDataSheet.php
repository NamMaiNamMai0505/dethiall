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

class LessonsDataSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Sample data for the template - minh hoạ một Unit chứa 2 bài con,
     * cộng một bài lẻ không phân cấp và một tiết thi.
     */
    public function array(): array
    {
        return [
            ['M009K2', 'U1', '', 'Unit 1: Đại cương', 'Unit', 1, 0, 0, 0, 'Học kỳ 1', ''],
            ['M009K2', 'U1-B1', 'U1', 'Khái niệm cơ bản', 'Bài con', 1, 4, 0, 0, 'Học kỳ 1', ''],
            ['M009K2', 'U1-B2', 'U1', 'Phân loại và ứng dụng', 'Bài con', 2, 4, 2, 0, 'Học kỳ 1', ''],
            ['M009K2', 'B02', '', 'Bài 2: Thực hành cơ bản', 'Bài', 2, 2, 6, 0, 'Học kỳ 1', 'Thực hành tại phòng lab'],
            ['M009K2', 'THI01', '', 'Thi kết thúc học phần', 'Thi', 3, 0, 0, 10, 'Học kỳ 1', ''],
        ];
    }

    public function title(): string
    {
        return 'Dữ liệu bài học';
    }

    public function headings(): array
    {
        return [
            'Mã môn học',
            'Mã bài học',
            'Mã bài cha',
            'Tên bài học',
            'Loại bài',
            'Thứ tự',
            'Giờ lý thuyết',
            'Giờ thực hành',
            'Giờ thi',
            'Học kỳ',
            'Mô tả',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Mã môn học
            'B' => 15,  // Mã bài học
            'C' => 15,  // Mã bài cha
            'D' => 35,  // Tên bài học
            'E' => 12,  // Loại bài
            'F' => 10,  // Thứ tự
            'G' => 14,  // Giờ lý thuyết
            'H' => 14,  // Giờ thực hành
            'I' => 10,  // Giờ thi
            'J' => 14,  // Học kỳ
            'K' => 35,  // Mô tả
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D9488'], // Teal-600
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

        $sheet->getStyle('A2:K6')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(25);

        return $sheet;
    }
}
