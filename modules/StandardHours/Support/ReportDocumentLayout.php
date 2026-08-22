<?php

namespace Modules\StandardHours\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared official letterhead + signature footer for Standard Hours Excel reports.
 */
class ReportDocumentLayout
{
    public const LEVEL_PERSONAL = 'personal';

    public const LEVEL_UNIT = 'unit';

    public const LEVEL_SCHOOL = 'school';

    public const LEVELS = [
        self::LEVEL_PERSONAL => 'Cấp cá nhân',
        self::LEVEL_UNIT => 'Cấp khoa',
        self::LEVEL_SCHOOL => 'Cấp trường',
    ];

    public static function normalizeLevel(?string $level): string
    {
        $level = $level ?: self::LEVEL_PERSONAL;

        return array_key_exists($level, self::LEVELS) ? $level : self::LEVEL_PERSONAL;
    }

    public static function levelLabel(?string $level): string
    {
        return self::LEVELS[self::normalizeLevel($level)];
    }

    /**
     * @return list<int>
     */
    public static function resolveUnitIds(array $filters): array
    {
        $ids = $filters['unit_ids'] ?? null;

        if ($ids === null || $ids === '' || $ids === []) {
            if (! empty($filters['unit_id'])) {
                return [(int) $filters['unit_id']];
            }

            return [];
        }

        if (! is_array($ids)) {
            $ids = [$ids];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($id) => (int) $id,
            $ids
        ), static fn (int $id) => $id > 0)));
    }

    /**
     * Write official letterhead (org left / national motto right).
     *
     * @return int Next free row index for content
     */
    public static function writeOfficialHeader(Worksheet $sheet, string $lastColumn = 'H'): int
    {
        [$leftEnd, $rightStart] = self::splitColumns($lastColumn);

        $sheet->mergeCells("A1:{$leftEnd}1");
        $sheet->setCellValue('A1', 'TỔNG CỤC HẬU CẦN - KỸ THUẬT');

        $sheet->mergeCells("A2:{$leftEnd}2");
        $sheet->setCellValue('A2', 'TRƯỜNG CAO ĐẲNG HẬU CẦN 2');

        $sheet->mergeCells("{$rightStart}1:{$lastColumn}1");
        $sheet->setCellValue("{$rightStart}1", 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM');

        $sheet->mergeCells("{$rightStart}2:{$lastColumn}2");
        $sheet->setCellValue("{$rightStart}2", 'Độc lập - Tự do - Hạnh phúc');

        $centerBold = [
            'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $sheet->getStyle("A1:{$leftEnd}2")->applyFromArray($centerBold);
        $sheet->getStyle("{$rightStart}1:{$lastColumn}1")->applyFromArray($centerBold);
        $sheet->getStyle("{$rightStart}2:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman', 'underline' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Subtle underline under national motto (classic VN document style)
        $sheet->getStyle("{$rightStart}2:{$lastColumn}2")->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Row 3 left blank as separator
        return 4;
    }

    /**
     * Write centered report title + subtitle. Returns next free row (after blank gap).
     */
    public static function writeReportTitle(
        Worksheet $sheet,
        string $title,
        string $subtitle,
        string $lastColumn,
        int $startRow
    ): int {
        $sheet->mergeCells("A{$startRow}:{$lastColumn}{$startRow}");
        $sheet->setCellValue("A{$startRow}", $title);
        $sheet->getStyle("A{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'name' => 'Times New Roman'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $subRow = $startRow + 1;
        $sheet->mergeCells("A{$subRow}:{$lastColumn}{$subRow}");
        $sheet->setCellValue("A{$subRow}", $subtitle);
        $sheet->getStyle("A{$subRow}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 11, 'name' => 'Times New Roman'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($subRow)->setRowHeight(20);

        // One blank row after subtitle
        return $subRow + 2;
    }

    /**
     * Write right-aligned signature block ("HIỆU TRƯỞNG").
     *
     * @return int Last reserved row (includes signature space)
     */
    public static function writeSignatureFooter(Worksheet $sheet, int $afterContentRow, string $lastColumn): int
    {
        $labelRow = $afterContentRow + 3;
        [, $rightStart] = self::splitColumns($lastColumn);

        $sheet->mergeCells("{$rightStart}{$labelRow}:{$lastColumn}{$labelRow}");
        $sheet->setCellValue("{$rightStart}{$labelRow}", 'HIỆU TRƯỞNG');
        $sheet->getStyle("{$rightStart}{$labelRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Reserve empty rows for handwritten signature / stamp
        return $labelRow + 5;
    }

    /**
     * @return array{0: string, 1: string} [leftEndColumn, rightStartColumn]
     */
    public static function splitColumns(string $lastColumn): array
    {
        $lastIndex = Coordinate::columnIndexFromString($lastColumn);
        $mid = max(1, (int) ceil($lastIndex / 2));
        $leftEnd = Coordinate::stringFromColumnIndex($mid);
        $rightStart = Coordinate::stringFromColumnIndex(min($lastIndex, $mid + 1));

        return [$leftEnd, $rightStart];
    }
}
