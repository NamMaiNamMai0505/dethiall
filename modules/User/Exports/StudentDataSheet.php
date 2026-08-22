<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentDataSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return sample data for the template
     */
    public function array(): array
    {
        return [
            [
                'Nguyễn Văn B',
                'SV001',
                'nguyenvanb@example.com',
                'Password123',
                'Phòng Đào Tạo',
                'Học viên',
                'student',
                'LH001',
            ],
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Dữ liệu học viên';
    }

    /**
     * Define headers
     */
    public function headings(): array
    {
        return [
            'Họ và tên',
            'MSHV',
            'Email',
            'Mật khẩu',
            'Đơn vị',
            'Vai trò',
            'Loại user',
            'Mã lớp',
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Họ và tên
            'B' => 15,  // MSHV
            'C' => 30,  // Email
            'D' => 20,  // Mật khẩu
            'E' => 25,  // Đơn vị
            'F' => 20,  // Vai trò
            'G' => 20,  // Loại user
            'H' => 15,  // Mã lớp
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'], // Purple-600
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

        // Style sample data row
        $sheet->getStyle('A2:H2')->applyFromArray([
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
