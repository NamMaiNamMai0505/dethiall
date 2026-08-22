<?php

namespace Modules\User\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['HƯỚNG DẪN NHẬP DỮ LIỆU NGƯỜI DÙNG'],
            [],
            ['CÁC TRƯỜNG DỮ LIỆU (Người dùng nội bộ):'],
            [],
            ['STT', 'TÊN TRƯỜNG', 'BẮT BUỘC', 'GIÁ TRỊ HỢP LỆ', 'VÍ DỤ'],
            ['1', 'Họ và tên', '✓ BẮT BUỘC', 'Họ tên đầy đủ của người dùng', 'Nguyễn Văn A'],
            ['2', 'Email', '✓ BẮT BUỘC', 'Email hợp lệ, duy nhất trong hệ thống', 'nguyenvana@example.com'],
            ['3', 'Mật khẩu', '✓ BẮT BUỘC', 'Tối thiểu 8 ký tự', 'Password123'],
            ['4', 'Đơn vị', '✓ BẮT BUỘC', 'Tên đơn vị (xem sheet Đơn vị)', 'Phòng Đào Tạo'],
            ['5', 'Vai trò', '✓ BẮT BUỘC', 'Tên vai trò (xem sheet Phân quyền)', 'Giảng viên'],
            ['6', 'Loại user', '✓ BẮT BUỘC', 'instructor/internal_user (xem hướng dẫn bên dưới)', 'instructor'],
            [],
            ['CÁC TRƯỜNG DỮ LIỆU (Học viên):'],
            [],
            ['STT', 'TÊN TRƯỜNG', 'BẮT BUỘC', 'GIÁ TRỊ HỢP LỆ', 'VÍ DỤ'],
            ['1', 'Họ và tên', '✓ BẮT BUỘC', 'Họ tên đầy đủ của học viên', 'Nguyễn Văn B'],
            ['2', 'MSHV', 'Không bắt buộc', 'Mã số học viên', 'SV001'],
            ['3', 'Email', '✓ BẮT BUỘC', 'Email hợp lệ, duy nhất trong hệ thống', 'nguyenvanb@example.com'],
            ['4', 'Mật khẩu', '✓ BẮT BUỘC', 'Tối thiểu 8 ký tự', 'Password123'],
            ['5', 'Đơn vị', '✓ BẮT BUỘC', 'Tên đơn vị (xem sheet Đơn vị)', 'Phòng Đào Tạo'],
            ['6', 'Vai trò', '✓ BẮT BUỘC', 'Tên vai trò (xem sheet Phân quyền)', 'Học viên'],
            ['7', 'Loại user', '✓ BẮT BUỘC', 'student (xem hướng dẫn bên dưới)', 'student'],
            ['8', 'Mã lớp', '✓ BẮT BUỘC', 'Mã lớp học (xem sheet Lớp học)', 'LH001'],
            [],
            ['LƯU Ý QUAN TRỌNG:'],
            [],
            ['⚠️ CẢNH BÁO', 'Có 2 sheet dữ liệu: "Dữ liệu người dùng" (công, viên chức nhà trường) và "Dữ liệu học viên" (học viên)'],
            ['⚠️ CẢNH BÁO', 'Xóa dòng mẫu trong sheet tương ứng trước khi nhập dữ liệu thực tế'],
            ['⚠️ CẢNH BÁO', 'Tất cả các trường đều là BẮT BUỘC, không được để trống'],
            ['⚠️ CẢNH BÁO', 'Email phải là duy nhất, không trùng với người dùng đã tồn tại'],
            ['⚠️ CẢNH BÁO', 'Tên đơn vị, vai trò và mã lớp phải khớp chính xác với dữ liệu trong hệ thống'],
            ['⚠️ CẢNH BÁO', 'Loại user: instructor (giảng viên), student (học viên), internal_user (cán bộ nội bộ)'],
            ['ℹ️ THÔNG TIN', 'Kiểm tra sheet "Đơn vị" để xem danh sách đơn vị có sẵn'],
            ['ℹ️ THÔNG TIN', 'Kiểm tra sheet "Lớp học" để xem danh sách lớp có sẵn (cho học viên)'],
            ['ℹ️ THÔNG TIN', 'Kiểm tra sheet "Phân quyền" để xem danh sách vai trò có sẵn'],
            ['ℹ️ THÔNG TIN', 'Mật khẩu sẽ được mã hóa tự động khi nhập vào hệ thống'],
            [],
            ['CÁCH SỬ DỤNG:'],
            [],
            ['Bước 1:', 'Đọc kỹ hướng dẫn ở sheet này'],
            ['Bước 2:', 'Xem sheet "Đơn vị", "Lớp học", "Phân quyền" để tham khảo dữ liệu'],
            ['Bước 3:', 'Chọn sheet phù hợp: "Dữ liệu người dùng" (nội bộ) hoặc "Dữ liệu học viên"'],
            ['Bước 4:', 'Xóa dòng dữ liệu mẫu (dòng 2)'],
            ['Bước 5:', 'Nhập dữ liệu từ dòng 2'],
            ['Bước 6:', 'Đảm bảo tên đơn vị, vai trò, mã lớp khớp chính xác'],
            ['Bước 7:', 'Lưu file và tải lên hệ thống'],
        ];
    }

    public function title(): string
    {
        return 'Hướng dẫn sử dụng';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 30,
            'C' => 15,
            'D' => 45,
            'E' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title - Row 1
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Blue-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // Section header - Row 3
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '1F2937'], // Gray-800
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'], // Gray-200
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(25);

        // Table 1 header - Row 5 (Internal Users)
        $sheet->getStyle('A5:E5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Green-600
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

        // Table 1 data rows - Rows 6-10
        $sheet->getStyle('A6:E10')->applyFromArray([
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

        // Highlight required fields in table 1
        foreach (range(6, 10) as $row) {
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'DC2626'], // Red-600
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'], // Red-100
                ],
            ]);
        }

        // Section header - Row 11 (Students)
        $sheet->mergeCells('A11:E11');
        $sheet->getStyle('A11')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '1F2937'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);
        $sheet->getRowDimension(11)->setRowHeight(25);

        // Table 2 header - Row 13 (Students)
        $sheet->getStyle('A13:E13')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
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

        // Table 2 data rows - Rows 14-20
        $sheet->getStyle('A14:E20')->applyFromArray([
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

        // Highlight required fields in table 2
        foreach (range(14, 20) as $row) {
            // Skip row 15 (MSHV - not required)
            if ($row === 15) {
                continue;
            }
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'DC2626'], // Red-600
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'], // Red-100
                ],
            ]);
        }

        // Section header - Row 21 (Important Notes)
        $sheet->mergeCells('A21:E21');
        $sheet->getStyle('A21')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '1F2937'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);
        $sheet->getRowDimension(21)->setRowHeight(25);

        // Warning rows - Rows 23-27 (Yellow)
        foreach ([23, 24, 25, 26, 27] as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'], // Yellow-100
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '92400E'], // Yellow-900
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => 'F59E0B'], // Yellow-500
                    ],
                ],
            ]);
        }

        // Info rows - Rows 28-31 (Blue)
        foreach ([28, 29, 30, 31] as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'], // Blue-100
                ],
                'font' => [
                    'color' => ['rgb' => '1E3A8A'], // Blue-900
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '3B82F6'], // Blue-500
                    ],
                ],
            ]);
        }

        // Section header - Row 33 (How to use)
        $sheet->mergeCells('A33:E33');
        $sheet->getStyle('A33')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '1F2937'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);
        $sheet->getRowDimension(33)->setRowHeight(25);

        // Steps rows - Rows 35-41
        foreach (range(35, 41) as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '059669'], // Green-600
                ],
            ]);
        }

        return $sheet;
    }
}
