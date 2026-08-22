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

class LessonsInstructionsSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['HƯỚNG DẪN NHẬP DỮ LIỆU BÀI HỌC'],
            [],
            ['CÁC TRƯỜNG DỮ LIỆU (sheet "Dữ liệu bài học"):'],
            [],
            ['STT', 'TÊN TRƯỜNG', 'BẮT BUỘC', 'GIÁ TRỊ HỢP LỆ', 'VÍ DỤ'],
            ['1', 'Mã môn học', '✓ BẮT BUỘC', 'Đúng mã môn học đã có trong hệ thống (mã đầy đủ hoặc mã ngắn nội bộ)', 'M009K2'],
            ['2', 'Mã bài học', '✓ BẮT BUỘC', 'Duy nhất trong cùng môn học', 'U1-B1'],
            ['3', 'Mã bài cha', 'Tùy chọn', 'Để trống nếu là bài gốc (không thuộc Unit/Chương nào). Nếu điền, phải khớp đúng "Mã bài học" của một dòng khác CÙNG môn', 'U1'],
            ['4', 'Tên bài học', '✓ BẮT BUỘC', 'Tên đầy đủ của bài/Unit/Chương', 'Khái niệm cơ bản'],
            ['5', 'Loại bài', 'Tùy chọn', 'Unit / Chương / Bài / Bài con / Thi (Mặc định: Bài)', 'Bài con'],
            ['6', 'Thứ tự', 'Tùy chọn', 'Số nguyên, quyết định thứ tự hiển thị (Mặc định: theo thứ tự dòng trong file)', '1'],
            ['7', 'Giờ lý thuyết', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '4'],
            ['8', 'Giờ thực hành', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '2'],
            ['9', 'Giờ thi', 'Tùy chọn', 'Số nguyên >= 0 (Mặc định: 0)', '0'],
            ['10', 'Học kỳ', 'Tùy chọn', 'Học kỳ 1 đến 6, hoặc Học kỳ hè / số 1-6. Để trống → lấy theo học kỳ của môn học', '1'],
            ['11', 'Mô tả', 'Tùy chọn', 'Ghi chú thêm', 'Thực hành tại phòng lab'],
            [],
            ['PHÂN CẤP UNIT / CHƯƠNG → BÀI CON:'],
            [],
            ['ℹ️ THÔNG TIN', 'Một dòng "Unit" hoặc "Chương" không có Mã bài cha sẽ là bài gốc'],
            ['ℹ️ THÔNG TIN', 'Các dòng "Bài con" điền đúng Mã bài cha = Mã bài học của dòng Unit/Chương đó sẽ tự động lồng vào bên dưới'],
            ['ℹ️ THÔNG TIN', 'Bài loại "Bài" hoặc "Thi" thường không cần Mã bài cha (để trống = bài gốc, ngang hàng Unit)'],
            [],
            ['LƯU Ý QUAN TRỌNG:'],
            [],
            ['⚠️ CẢNH BÁO', 'Chỉ nhận file Excel (.xlsx, .xls)'],
            ['⚠️ CẢNH BÁO', 'Xóa các dòng mẫu trước khi nhập dữ liệu thực tế'],
            ['⚠️ CẢNH BÁO', 'Trường "Mã môn học", "Mã bài học", "Tên bài học" là BẮT BUỘC'],
            ['⚠️ CẢNH BÁO', 'Một file có thể chứa bài học của NHIỀU môn học khác nhau - hệ thống tự nhận theo cột "Mã môn học" từng dòng'],
            ['ℹ️ THÔNG TIN', 'Mã bài học đã tồn tại (cùng môn) sẽ được cập nhật theo dòng import, không tạo trùng'],
            [],
            ['CÁCH SỬ DỤNG:'],
            [],
            ['Bước 1:', 'Tải template và điền sheet "Dữ liệu bài học"'],
            ['Bước 2:', 'Vào Bài học (khung CTĐT) → Import bài học'],
            ['Bước 3:', 'Chọn file Excel và bấm Import'],
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
            'D' => 55,
            'E' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D9488']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(25);

        $sheet->getStyle('A5:E5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Rows 6-16 = 11 data field rows (item 1 to 11)
        $sheet->getStyle('A6:E16')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Required fields highlighted: row 6 (Mã môn học), row 7 (Mã bài học), row 9 (Tên bài học)
        foreach (['C6', 'C7', 'C9'] as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            ]);
        }

        $sheet->mergeCells('A18:E18');
        $sheet->getStyle('A18')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        ]);
        $sheet->getRowDimension(18)->setRowHeight(25);

        foreach ([20, 21, 22] as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                'font' => ['color' => ['rgb' => '1E3A8A']],
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3B82F6']]],
            ]);
        }

        $sheet->mergeCells('A24:E24');
        $sheet->getStyle('A24')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        ]);
        $sheet->getRowDimension(24)->setRowHeight(25);

        foreach ([26, 27, 28, 29] as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'F59E0B']]],
            ]);
        }
        $sheet->mergeCells('B30:E30');
        $sheet->getStyle('A30:E30')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'font' => ['color' => ['rgb' => '1E3A8A']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3B82F6']]],
        ]);

        $sheet->mergeCells('A32:E32');
        $sheet->getStyle('A32')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        ]);
        $sheet->getRowDimension(32)->setRowHeight(25);

        foreach (range(34, 36) as $row) {
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '059669']],
            ]);
        }

        return $sheet;
    }
}
