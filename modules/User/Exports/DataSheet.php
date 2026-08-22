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

class DataSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return sample data for the template
     */
    public function array(): array
    {
        return [
            [
                'Nguyễn Văn A',
                'NV001',
                'nguyenvana@example.com',
                'Password123',
                'Phòng Đào Tạo',
                'Giảng viên',
                'instructor',
            ],
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Dữ liệu người dùng';
    }

    /**
     * Define headers
     */
    public function headings(): array
    {
        return [
            'Họ và tên',
            'Mã NV',
            'Email',
            'Mật khẩu',
            'Đơn vị',
            'Vai trò',
            'Loại user',
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Họ và tên
            'B' => 15,  // Mã NV
            'C' => 30,  // Email
            'D' => 20,  // Mật khẩu
            'E' => 25,  // Đơn vị
            'F' => 20,  // Vai trò
            'G' => 20,  // Loại user
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:G1')->applyFromArray([
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

        // Style sample data row
        $sheet->getStyle('A2:G2')->applyFromArray([
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
