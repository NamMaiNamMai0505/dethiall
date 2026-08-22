<?php

namespace Modules\Subject\Exports;

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
            ['HƯỚNG DẪN NHẬP DỮ LIỆU MÔN HỌC'],
            [],
            ['CÁC TRƯỜNG DỮ LIỆU (sheet "Dữ liệu môn học"):'],
            [],
            ['STT', 'TÊN TRƯỜNG', 'BẮT BUỘC', 'GIÁ TRỊ HỢP LỆ', 'VÍ DỤ'],
            ['1', 'Mã môn học', 'Tùy chọn', 'Mã ngắn trong ngành (hệ thống ghép mã ngành)', 'M009K2'],
            ['2', 'Mã khoa', 'Tùy chọn', 'Đúng mã Khoa chuyên môn đang hoạt động (không còn suy ra từ hậu tố mã môn nữa)', 'K2'],
            ['3', 'Tên môn học', '✓ BẮT BUỘC', 'Tên đầy đủ của môn học', 'Thuốc thông thường'],
            ['4', 'Viết tắt', 'Tùy chọn', 'Tên ngắn khi xuất lịch đào tạo (vd TTT)', 'TTT'],
            ['5', 'Mô tả', 'Tùy chọn', 'Mô tả ngắn gọn', 'Kiến thức cơ sở ngành'],
            ['6', 'Số tín chỉ', 'Tùy chọn', 'Số nguyên (Mặc định: 1)', '1'],
            ['7', 'Số Tiết lý thuyết', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '63'],
            ['8', 'Số Tiết thực hành', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '13'],
            ['9', 'Số Tiết tự học', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '12'],
            ['10', 'Số Tiết thi', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '5'],
            ['11', 'Cấp độ', 'Tùy chọn', 'Cơ bản / Trung cấp / Nâng cao', 'Cơ bản'],
            ['12', 'Học kỳ', 'Tùy chọn', 'Học kỳ 1 đến 6, hoặc Học kỳ hè / số 1-6', '1'],
            ['13', 'Phương pháp đánh giá', 'Tùy chọn', 'Thi viết / Thi trắc nghiệm / Bài tập / Đồ án / Tổng hợp', 'Thi viết'],
            [],
            ['MÃ CHUẨN SAU KHI IMPORT:'],
            [],
            ['ℹ️ THÔNG TIN', 'Trên form import: chọn chương trình; tiền tố mã môn nội bộ được tự sinh'],
            ['ℹ️ THÔNG TIN', 'Ví dụ chương trình B.6720301 → tiền tố B_6720301 → mã môn B_6720301_M009K2'],
            ['ℹ️ THÔNG TIN', 'Mã ngành chính thức vẫn hiển thị riêng trên giao diện và báo cáo'],
            [],
            ['LƯU Ý QUAN TRỌNG:'],
            [],
            ['⚠️ CẢNH BÁO', 'Chỉ nhận file Excel (.xlsx, .xls)'],
            ['⚠️ CẢNH BÁO', 'Xóa dòng mẫu trước khi nhập dữ liệu thực tế (nếu cần)'],
            ['⚠️ CẢNH BÁO', 'Trường "Tên môn học" là BẮT BUỘC'],
            ['ℹ️ THÔNG TIN', 'Có thể để trống "Mã môn học" để hệ thống tự tạo mã ngắn'],
            ['ℹ️ THÔNG TIN', 'Cột "Viết tắt" dùng khi xuất lịch (ưu tiên). Để trống → tự lấy chữ cái đầu tên (Thuốc thông thường → TTT)'],
            ['ℹ️ THÔNG TIN', 'Mã đã tồn tại sẽ được cập nhật theo dòng import'],
            [],
            ['CÁCH SỬ DỤNG:'],
            [],
            ['Bước 1:', 'Tải template và điền sheet "Dữ liệu môn học"'],
            ['Bước 2:', 'Vào /subjects/create → khối Import Excel'],
            ['Bước 3:', 'Chọn ngành, kiểm tra/chỉnh mã ngành'],
            ['Bước 4:', 'Chọn file Excel và bấm Import'],
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
            'B' => 25,
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

        // Table header - Row 5
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

        // Table data rows - Rows 6-15
        $sheet->getStyle('A6:E15')->applyFromArray([
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

        // Highlight required field (Row 8 - Tên môn học)
        $sheet->getStyle('C8')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'DC2626'], // Red-600
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEE2E2'], // Red-100
            ],
        ]);

        // Section header - Row 17
        $sheet->mergeCells('A17:E17');
        $sheet->getStyle('A17')->applyFromArray([
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
        $sheet->getRowDimension(17)->setRowHeight(25);

        // Warning rows - Rows 19-20 (Yellow)
        foreach ([19, 20] as $row) {
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

        // Info rows - Rows 21-23 (Blue)
        foreach ([21, 22, 23] as $row) {
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

        // Section header - Row 25
        $sheet->mergeCells('A25:E25');
        $sheet->getStyle('A25')->applyFromArray([
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
        $sheet->getRowDimension(25)->setRowHeight(25);

        // Steps rows - Rows 27-31
        foreach (range(27, 31) as $row) {
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
