<?php

namespace Modules\ExportTemplates\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * "AI light": quét file Excel tìm placeholder {{snake_case}} và ô có nhãn gợi ý map.
 */
class TemplateScanner
{
    /**
     * @return array{placeholders: list<string>, cell_map: array<string, string>, hints: list<array{cell:string,label:string,suggest:?string}>}
     */
    public function scan(string $absolutePath): array
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xls', 'xlsm'], true)) {
            return $this->scanSpreadsheet($absolutePath);
        }

        // docx: plain text scan for {{var}}
        if ($ext === 'docx') {
            return $this->scanDocxZip($absolutePath);
        }

        return ['placeholders' => [], 'cell_map' => [], 'hints' => []];
    }

    protected function scanSpreadsheet(string $path): array
    {
        $book = IOFactory::load($path);
        $placeholders = [];
        $cellMap = [];
        $hints = [];

        $labelHints = [
            'họ tên' => 'student_name',
            'ho ten' => 'student_name',
            'mssv' => 'student_code',
            'mã sv' => 'student_code',
            'mã hv' => 'student_code',
            '15 phút' => 'score_oral_15',
            '15 phut' => 'score_oral_15',
            '1 tiết' => 'score_period_1',
            '1 tiet' => 'score_period_1',
            'giữa kỳ' => 'score_midterm',
            'giua ky' => 'score_midterm',
            'điểm thi' => 'score_final',
            'diem thi' => 'score_final',
            'lớp' => 'class_name',
            'lop' => 'class_name',
            'môn' => 'subject_name',
            'mon' => 'subject_name',
            'năm học' => 'academic_year',
            'nam hoc' => 'academic_year',
        ];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $title = $sheet->getTitle();
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $val = trim((string) $cell->getValue());
                    if ($val === '') {
                        continue;
                    }
                    $coord = $title.'!'.$cell->getCoordinate();

                    if (preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $val, $m)) {
                        foreach ($m[1] as $ph) {
                            $placeholders[] = $ph;
                            $cellMap[$coord] = $ph;
                        }
                    }

                    $low = mb_strtolower($val);
                    foreach ($labelHints as $needle => $suggest) {
                        if (str_contains($low, $needle)) {
                            $hints[] = [
                                'cell' => $coord,
                                'label' => $val,
                                'suggest' => $suggest,
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return [
            'placeholders' => array_values(array_unique($placeholders)),
            'cell_map' => $cellMap,
            'hints' => $hints,
        ];
    }

    protected function scanDocxZip(string $path): array
    {
        $placeholders = [];
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return ['placeholders' => [], 'cell_map' => [], 'hints' => []];
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        if (preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $xml, $m)) {
            $placeholders = array_values(array_unique($m[1]));
        }

        return ['placeholders' => $placeholders, 'cell_map' => [], 'hints' => []];
    }
}
