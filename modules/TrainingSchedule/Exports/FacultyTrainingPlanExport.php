<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất kế hoạch huấn luyện cấp Khoa theo lớp (mẫu Mau_Xuat lich_Khoa_Theo lop.docx).
 * Cột Ngày chỉ hiển thị ngày (dd), không tháng/năm.
 * Ưu tiên DOCX; fallback XLSX nếu thiếu PhpWord.
 */
class FacultyTrainingPlanExport
{
    /**
     * @param  Collection<int, object>  $rows
     *                                         object fields: day, weekday, period_label, classroom, subject_short, lesson_name, instructor, note
     */
    public function __construct(
        protected string $className,
        protected string $unitName,
        protected string $monthLabel,
        protected string $yearLabel,
        protected Collection $rows,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        if (class_exists(PhpWord::class)) {
            $docxName = preg_replace('/\.xlsx$/i', '.docx', $filename) ?: $filename;
            if (! str_ends_with(strtolower($docxName), '.docx')) {
                $docxName .= '.docx';
            }

            return $this->downloadDocx($docxName);
        }

        return $this->downloadXlsx($filename);
    }

    public function downloadDocx(string $filename): StreamedResponse
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 700,
            'marginRight' => 700,
            'orientation' => 'landscape',
        ]);

        // Header 2 cột (mẫu)
        $headerTable = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
        $headerTable->addRow();
        $left = $headerTable->addCell(4500);
        $left->addText('TRƯỜNG CAO ĐẲNG HẬU CẦN 2', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $left->addText('PHÒNG ĐÀO TẠO', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $right = $headerTable->addCell(5500);
        $right->addText(
            'LỊCH HUẤN LUYỆN THÁNG '.$this->monthLabel.' NĂM '.$this->yearLabel,
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER]
        );
        $right->addText(
            'LỚP: '.$this->className.($this->unitName ? ' – '.$this->unitName : ''),
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER]
        );

        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ];
        $table = $section->addTable($tableStyle);

        $headers = ['Ngày', 'Thứ', 'Tiết', 'Địa điểm', 'Môn học', 'Tên bài', 'Giảng viên', 'Ghi chú'];
        $widths = [800, 900, 900, 1400, 1400, 2800, 1800, 1200];
        $table->addRow(350);
        foreach ($headers as $i => $h) {
            $cell = $table->addCell($widths[$i], ['bgColor' => '4EA1FF', 'valign' => 'center']);
            $cell->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        $prevDay = null;
        foreach ($this->rows as $row) {
            $table->addRow();
            $showDay = $prevDay === null || $prevDay !== (string) $row->day;
            $prevDay = (string) $row->day;

            $values = [
                $showDay ? (string) $row->day : '',
                $showDay ? (string) $row->weekday : '',
                (string) $row->period_label,
                (string) $row->classroom,
                (string) $row->subject_short,
                (string) $row->lesson_name,
                (string) $row->instructor,
                (string) ($row->note ?? ''),
            ];
            foreach ($values as $i => $val) {
                $cell = $table->addCell($widths[$i], ['valign' => 'center']);
                $cell->addText($val, ['size' => 10], [
                    'alignment' => in_array($i, [0, 1, 2], true) ? Jc::CENTER : Jc::LEFT,
                ]);
            }
        }

        $section->addTextBreak(2);
        $sign = $section->addTable([
            'borderSize' => 0,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
        $sign->addRow();
        $sign->addCell(5000);
        $rightSign = $sign->addCell(5000);
        $rightSign->addText('TRƯỞNG PHÒNG / TRƯỞNG KHOA', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $rightSign->addText('(Ký, ghi rõ họ tên)', ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        return response()->streamDownload(function () use ($phpWord) {
            $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function downloadXlsx(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KHHL');

        $sheet->setCellValue('A1', 'TRƯỜNG CAO ĐẲNG HẬU CẦN 2');
        $sheet->setCellValue('A2', 'PHÒNG ĐÀO TẠO / KHOA: '.$this->unitName);
        $sheet->setCellValue('A3', 'LỊCH HUẤN LUYỆN THÁNG '.$this->monthLabel.' NĂM '.$this->yearLabel);
        $sheet->setCellValue('A4', 'LỚP: '.$this->className);
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');

        $headers = ['Ngày', 'Thứ', 'Tiết', 'Địa điểm', 'Môn học', 'Tên bài', 'Giảng viên', 'Ghi chú'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'6', $h);
            $col++;
        }

        $r = 7;
        $prevDay = null;
        foreach ($this->rows as $row) {
            $showDay = $prevDay === null || $prevDay !== (string) $row->day;
            $prevDay = (string) $row->day;
            $sheet->setCellValue('A'.$r, $showDay ? $row->day : '');
            $sheet->setCellValue('B'.$r, $showDay ? $row->weekday : '');
            $sheet->setCellValue('C'.$r, $row->period_label);
            $sheet->setCellValue('D'.$r, $row->classroom);
            $sheet->setCellValue('E'.$r, $row->subject_short);
            $sheet->setCellValue('F'.$r, $row->lesson_name);
            $sheet->setCellValue('G'.$r, $row->instructor);
            $sheet->setCellValue('H'.$r, $row->note ?? '');
            $r++;
        }
        $end = max(6, $r - 1);

        $sheet->getStyle('A1:H4')->getFont()->setBold(true);
        $sheet->getStyle('A1:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:H6')->getFont()->setBold(true);
        $sheet->getStyle('A6:H6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4EA1FF');
        $sheet->getStyle('A6:H6')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A6:H'.$end)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A7:H'.$end)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Build rows from schedule details — gộp tiết liên tiếp cùng môn/phòng/GV/bài.
     *
     * @param  \Illuminate\Support\Collection<int, \Modules\ScheduleDetail\Models\ScheduleDetail>  $details
     */
    public static function rowsFromDetails(Collection $details): Collection
    {
        $weekdayMap = [1 => 'Hai', 2 => 'Ba', 3 => 'Tư', 4 => 'Năm', 5 => 'Sáu', 6 => 'Bảy', 7 => 'CN'];

        $sorted = $details->sortBy(fn ($d) => $d->date.'|'.str_pad((string) $d->period, 2, '0', STR_PAD_LEFT))->values();

        $merged = [];
        foreach ($sorted as $d) {
            $date = Carbon::parse($d->date);
            $period = (int) $d->period;
            $subject = $d->subject ?? null;
            $lesson = null;
            if (method_exists($d, 'relationLoaded') && $d->relationLoaded('subjectLesson')) {
                $lesson = $d->subjectLesson;
            } elseif (isset($d->subjectLesson)) {
                $lesson = $d->subjectLesson;
            } elseif (isset($d->subject_lesson)) {
                $lesson = $d->subject_lesson;
            }
            $lessonName = $lesson
                ? trim((($lesson->code ?? '') !== '' ? $lesson->code.': ' : '').($lesson->name ?? ''))
                : '';
            $classroom = is_object($d->classroom ?? null) ? ($d->classroom->name ?? '') : (string) ($d->classroom ?? '');
            $instructor = is_object($d->instructor ?? null) ? ($d->instructor->name ?? '') : (string) ($d->instructor ?? '');
            $subjectShort = '';
            if ($subject) {
                $subjectShort = (string) ($subject->short_label
                    ?? (method_exists($subject, 'getShortLabelAttribute') ? $subject->short_label : null)
                    ?? $subject->abbreviation
                    ?? $subject->name
                    ?? '');
            }
            $signature = implode('|', [
                $date->format('Y-m-d'),
                $subjectShort,
                $lessonName,
                $classroom,
                $instructor,
                (string) ($d->subject_id ?? ''),
            ]);

            $last = end($merged);
            if ($last && $last['_sig'] === $signature && (int) $last['_end_period'] + 1 === $period) {
                $merged[array_key_last($merged)]['_end_period'] = $period;
                $start = (int) $merged[array_key_last($merged)]['_start_period'];
                $endP = $period;
                $merged[array_key_last($merged)]['period_label'] = $start === $endP
                    ? (string) $start
                    : $start.'-'.$endP;

                continue;
            }

            $merged[] = [
                '_sig' => $signature,
                '_start_period' => $period,
                '_end_period' => $period,
                'day' => $date->format('d'),
                'weekday' => $weekdayMap[$date->dayOfWeekIso] ?? '',
                'period_label' => (string) $period,
                'classroom' => $classroom,
                'subject_short' => $subjectShort,
                'lesson_name' => $lessonName,
                'instructor' => $instructor,
                'note' => '',
                'date' => $date->format('Y-m-d'),
                'period' => $period,
            ];
        }

        return collect($merged)->map(function ($row) {
            unset($row['_sig'], $row['_start_period'], $row['_end_period']);

            return (object) $row;
        })->values();
    }
}
