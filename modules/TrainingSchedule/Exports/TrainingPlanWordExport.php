<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\TrainingSchedule\Services\LhlPeriodLayoutSelector;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất LHL ra Word — lưới tuần, viết tắt, header/footer/3 chữ ký (có ảnh nếu có).
 */
class TrainingPlanWordExport
{
    /**
     * @param  list<array{
     *   class_name:string,
     *   semester_label:string,
     *   academic_year:string,
     *   start:Carbon,
     *   end:Carbon,
     *   cells:Collection,
     *   legend:Collection
     * }>  $classes
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        protected array $classes,
        protected array $meta = [],
        protected string $layout = LhlPeriodLayoutSelector::CLASSIC,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $phpWord = $this->buildDocument();

        if (! str_ends_with(strtolower($filename), '.docx')) {
            $filename .= '.docx';
        }

        return response()->streamDownload(function () use ($phpWord) {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** Tạo DOCX trực tiếp trên đĩa cho luồng chuyển PDF, không đi qua HTTP stream. */
    public function save(string $path): string
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu Word LHL tạm.');
        }

        IOFactory::createWriter($this->buildDocument(), 'Word2007')->save($path);
        if (! is_file($path) || filesize($path) === 0) {
            throw new \RuntimeException('Không thể tạo file Word LHL trung gian.');
        }

        return $path;
    }

    protected function buildDocument(): PhpWord
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(10);

        $cfg = config('lhl_export', []);
        $meta = array_merge([
            'org_left' => $cfg['org_left'] ?? '',
            'title' => $cfg['title'] ?? 'LỊCH HUẤN LUYỆN',
            'respect_line' => $cfg['respect_line'] ?? '',
            'note' => $cfg['note'] ?? '',
            'signers' => $cfg['signers'] ?? [],
        ], $this->meta);

        $pages = collect($this->classes)
            ->flatMap(fn (array $classData): array => $this->classPages($classData))
            ->values();

        foreach ($pages as $idx => $classData) {
            // A3 ngang — in dán tường
            $section = $phpWord->addSection([
                'orientation' => 'landscape',
                'pageSizeW' => Converter::cmToTwip(42.0),
                'pageSizeH' => Converter::cmToTwip(29.7),
                'marginTop' => Converter::cmToTwip(0.9),
                'marginBottom' => Converter::cmToTwip(0.9),
                'marginLeft' => Converter::cmToTwip(1.0),
                'marginRight' => Converter::cmToTwip(1.0),
            ]);

            $this->renderClass($section, $classData, $meta, $idx === 0);
        }

        if ($pages->isEmpty()) {
            $phpWord->addSection()->addText('Chưa có dữ liệu lịch huấn luyện.');
        }

        return $phpWord;
    }

    /** Ô góc: Ngày (trên-phải) · Tiết (dưới-trái), không dùng ảnh/gạch chéo. */
    protected function addLabelDiagonalCell($table, int $width): void
    {
        $cell = $table->addCell($width, [
            'valign' => 'center',
            'borderSize' => 6,
            'borderColor' => '000000',
        ]);
        $cell->addText('Ngày', ['bold' => true, 'size' => 10], ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]);
        $cell->addText('Tiết', ['bold' => true, 'size' => 10], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
    }

    /** Ô tuần: hai dòng ngang, số đầu căn trái và số cuối căn phải. */
    protected function addWeekDateCell($table, int $width, string $dStart, string $dEnd): void
    {
        $cell = $table->addCell($width, [
            'valign' => 'center',
            'borderSize' => 6,
            'borderColor' => '000000',
        ]);
        $cell->addText($dStart, ['bold' => true, 'size' => 9], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
        $cell->addText($dEnd, ['bold' => true, 'size' => 9], ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]);
    }

    /**
     * PhpWord nhận kích thước ảnh theo pixel, còn cột theo twip (xấp xỉ 15
     * twip/pixel). Co ảnh theo đúng khung để không tràn khi lịch có nhiều tuần.
     *
     * @return array{int,int}
     */
    protected function headerImageSize(int $cellWidthTwip): array
    {
        $width = max(34, min(76, (int) floor($cellWidthTwip / 15) - 4));
        $height = max(18, min(36, (int) round($width / 2)));

        return [$width, $height];
    }

    /** Ngày↑phải · Tiết↓trái · gạch chéo giữa (không đè chữ). */
    protected function makeLabelDiagonalPng(int $w = 100, int $h = 50): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $path = sys_get_temp_dir().'/lhl_label_diag_v9_'.$w.'x'.$h.'.png';
        if (is_file($path)) {
            return $path;
        }

        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 30, 30, 30);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        // Gạch chéo full khung: chạm mép ô (trên-trái → dưới-phải)
        imageline($im, 0, 0, $w - 1, $h - 1, $black);
        imageline($im, 1, 0, $w - 1, $h - 2, $black); // đậm nhẹ cho rõ

        $font = $this->findUnicodeFont();
        // Không dùng imagestring cho chữ Việt vì GD bitmap font sẽ làm mất
        // dấu. Khi máy chưa có TTF Unicode, trả về null để PhpWord render chữ
        // “Ngày/Tiết” có dấu bằng text fallback.
        if (! $font) {
            imagedestroy($im);

            return null;
        }
        $pad = 4;
        // Ngày — trên-phải (lệch khỏi đường chéo)
        $bbox = imagettfbbox(16, 0, $font, 'Ngày');
        $tw = $bbox ? abs($bbox[2] - $bbox[0]) : 30;
        imagettftext($im, 16, 0, max($pad, $w - $tw - $pad), 21, $black, $font, 'Ngày');
        // Tiết — dưới-trái
        imagettftext($im, 16, 0, $pad, $h - 3, $black, $font, 'Tiết');

        imagepng($im, $path);
        imagedestroy($im);

        return $path;
    }

    /** dd đầu trên-trái · dd cuối dưới-phải — không gạch (như ô tuần Excel). */
    protected function makeWeekDatePng(string $dStart, string $dEnd, int $w = 100, int $h = 50): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $path = sys_get_temp_dir().'/lhl_week_v7_'.md5($dStart.'|'.$dEnd."|{$w}x{$h}").'.png';
        if (is_file($path)) {
            return $path;
        }

        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 30, 30, 30);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        $font = $this->findUnicodeFont();
        $pad = 4;
        if ($font) {
            imagettftext($im, 14, 0, $pad, 19, $black, $font, $dStart);
            $bbox = imagettfbbox(14, 0, $font, $dEnd);
            $tw = $bbox ? abs($bbox[2] - $bbox[0]) : 12;
            imagettftext($im, 14, 0, max($pad, $w - $tw - $pad), $h - 4, $black, $font, $dEnd);
        } else {
            imagestring($im, 5, $pad, 1, $dStart, $black);
            imagestring($im, 5, max($pad, $w - strlen($dEnd) * 9 - $pad), $h - 17, $dEnd, $black);
        }

        imagepng($im, $path);
        imagedestroy($im);

        return $path;
    }

    protected function findUnicodeFont(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\times.ttf',
            'C:\\Windows\\Fonts\\timesbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\tahoma.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/liberation/LiberationSerif-Regular.ttf',
        ];
        foreach ($candidates as $f) {
            if (is_file($f)) {
                return $f;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $classData
     * @param  array<string, mixed>  $meta
     */
    protected function renderClass($section, array $classData, array $meta, bool $first): void
    {
        /** @var Carbon $start */
        $start = $classData['start'];
        /** @var Carbon $end */
        $end = $classData['end'];
        $className = (string) $classData['class_name'];
        $semesterLabel = (string) $classData['semester_label'];
        $academicYear = (string) $classData['academic_year'];
        /** @var Collection $cells */
        $cells = $classData['cells'];
        /** @var Collection $legend */
        $legend = $classData['legend'];

        $semesterLine = trim((string) ($meta['semester_line'] ?? ''));
        if ($semesterLine === '') {
            $semesterLine = trim($semesterLabel.' năm học '.$academicYear);
        }

        // Header 2 cột org | title
        $header = $section->addTable([
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
            'cellMargin' => 40,
        ]);
        $header->addRow();
        // Chỉ kẻ đường chân dưới khối tên đơn vị; header không có khung bao.
        $left = $header->addCell(3500, [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        foreach (preg_split("/\r\n|\n|\r/", (string) $meta['org_left']) ?: [] as $line) {
            $left->addText($line, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
        $mid = $header->addCell(4500);
        $mid->addText((string) $meta['title'], ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        $mid->addText($semesterLine, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $right = $header->addCell(3000);
        $right->addText('Lớp: '.$className, ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
        if (! empty($meta['unit_name'])) {
            $right->addText('Đơn vị: '.$meta['unit_name'], ['size' => 9], ['spaceAfter' => 0]);
        }
        if (! empty($meta['class_size'])) {
            $right->addText('Sĩ số: '.$meta['class_size'], ['size' => 9], ['spaceAfter' => 0]);
        }
        if (! empty($meta['class_leader'])) {
            $right->addText('CN lớp: '.$meta['class_leader'], ['size' => 9], ['spaceAfter' => 0]);
        }
        if (! empty($meta['classroom'])) {
            $right->addText('Phòng học: '.$meta['classroom'], ['size' => 9], ['spaceAfter' => 0]);
        }

        $section->addText(
            'Từ '.$start->format('d/m/Y').' đến '.$end->format('d/m/Y'),
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        if (! empty($meta['respect_line'])) {
            $section->addText((string) $meta['respect_line'], ['size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);
        }

        // Build weeks
        $weeks = $this->buildWeeks($start, $end);
        $groupedLayout = $this->layout === LhlPeriodLayoutSelector::GROUPED_PERIODS;
        $grid = $groupedLayout
            ? $this->buildGroupedGrid($cells, $weeks)
            : $this->buildGrid($cells, $weeks);
        $weekCount = max(1, count($weeks));
        $monthSpans = $this->monthSpans($weeks);

        // Độ rộng cột (twip) — A3 ngang rộng
        $wThu = 700;
        $wBuoi = 900;
        $wWeek = (int) max(650, min(1100, (int) (18000 / $weekCount)));

        $border = ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'center'];

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
            'cellMargin' => 15,
        ]);

        // —— Hàng 1: Thứ (merge dọc 3 hàng header) | Tháng | tháng gộp 6·7·8 ——
        $table->addRow(300);
        $thuHead = $table->addCell($wThu, $border + ['vMerge' => 'restart']);
        $thuHead->addText('Thứ', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell($wBuoi, $border)->addText('Tháng', ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        foreach ($monthSpans as $span) {
            $spanW = $wWeek * $span['count'];
            $cell = $table->addCell($spanW, $border + ['gridSpan' => $span['count']]);
            $cell->addText((string) $span['month'], ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        // —— Hàng 2: (Thứ continue) | Tuần | 1 2 3 … ——
        $table->addRow(280);
        $table->addCell($wThu, $border + ['vMerge' => 'continue']);
        $table->addCell($wBuoi, $border)->addText('Tuần', ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);
        $weekNumberOffset = max(0, (int) ($classData['week_number_offset'] ?? 0));
        foreach ($weeks as $wi => $week) {
            $table->addCell($wWeek, $border)->addText(
                (string) ($weekNumberOffset + $wi + 1),
                ['bold' => true, 'size' => 8],
                ['alignment' => Jc::CENTER]
            );
        }

        // —— Hàng 3: (Thứ continue) | Tiết\Ngày | dd tuần ——
        // 600 twip ≈ 40 px: bằng khung ảnh Ngày/Tiết, không còn tràn ô.
        $table->addRow(600);
        $table->addCell($wThu, $border + ['vMerge' => 'continue']);
        $this->addLabelDiagonalCell($table, $wBuoi);
        foreach ($weeks as $week) {
            $this->addWeekDateCell(
                $table,
                $wWeek,
                $week['start']->format('d'),
                $week['end']->format('d')
            );
        }

        // —— Body: mẫu cũ 2 hàng/ngày; mẫu nhóm tiết có số hàng động theo
        // số lần đổi môn thực tế của từng thứ trong các tuần xuất. ——
        for ($isoDow = 1; $isoDow <= 5; $isoDow++) {
            $vnDay = (string) ($isoDow + 1); // 2..6
            if ($groupedLayout) {
                $rows = $this->groupedSegments($grid, $isoDow, count($weeks));
            } else {
                $rows = ['am' => '1÷5', 'pm' => '6÷9'];
            }
            $firstKey = array_key_first($rows);
            foreach ($rows as $session => $rowDefinition) {
                $sessionLabel = $groupedLayout
                    ? $this->periodRangeLabel($rowDefinition['period_start'], $rowDefinition['period_end'])
                    : $rowDefinition;
                $table->addRow(420);
                if ($session === $firstKey) {
                    $cellA = $table->addCell($wThu, $border + ['vMerge' => 'restart']);
                    $cellA->addText($vnDay, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
                } else {
                    $table->addCell($wThu, $border + ['vMerge' => 'continue']);
                }
                $table->addCell($wBuoi, $border)->addText($sessionLabel, ['size' => 8], ['alignment' => Jc::CENTER]);

                foreach ($weeks as $wi => $week) {
                    $cellStyle = $border;
                    if ($groupedLayout) {
                        $entry = $this->groupedEntryForRange($grid, $wi, $isoDow, $rowDefinition);
                        $text = $entry ? implode("\n", $entry['labels']) : '';
                    } else {
                        $key = $wi.'|'.$isoDow.'|'.$session;
                        $entry = $grid[$key] ?? null;
                        $text = $entry ? implode("\n", $entry['labels']) : '';
                    }
                    $cell = $table->addCell($wWeek, $cellStyle);
                    if ($text !== '') {
                        $cell->addText($text, ['bold' => true, 'size' => 8, 'color' => '000000'], ['alignment' => Jc::CENTER]);
                    } else {
                        $cell->addText('');
                    }
                }
            }
        }

        // Ghi chú ngắn
        if (! empty($meta['note'])) {
            $section->addText(
                'Ghi chú: '.$meta['note'],
                ['size' => 8, 'italic' => true],
                ['spaceBefore' => 80, 'spaceAfter' => 60]
            );
        }

        // —— Khối dưới lưới: bảng môn (trái) | KÍ HIỆU CHUNG (phải) ——
        $this->renderLegendAndNotes($section, $legend, $meta);

        // —— Chữ ký: đặt dưới bảng môn theo biểu mẫu giấy ——
        $section->addText(
            (string) ($meta['date_line'] ?? ('Ngày     tháng      năm '.$end->format('Y'))),
            ['size' => 9, 'italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceBefore' => 120, 'spaceAfter' => 40]
        );

        $signers = $meta['signers'] ?? [];
        if (is_array($signers) && $signers !== []) {
            $signTable = $section->addTable([
                'width' => 100 * 50,
                'unit' => TblWidth::PERCENT,
                'cellMargin' => 40,
            ]);
            $signTable->addRow();
            $activeSigners = array_values(array_filter($signers, function ($s) {
                return ! (isset($s['enabled']) && ! $s['enabled']);
            }));
            $n = max(1, count($activeSigners));
            $cellW = (int) (9000 / $n);
            foreach ($activeSigners as $signer) {
                $cell = $signTable->addCell($cellW);
                $cell->addText((string) ($signer['role_line1'] ?? ''), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                if (! empty($signer['role_line2'])) {
                    $cell->addText((string) $signer['role_line2'], ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                }
                $img = $this->resolveImage((string) ($signer['image'] ?? ''));
                if ($img) {
                    try {
                        $cell->addImage($img, [
                            'width' => 72,
                            'height' => 40,
                            'alignment' => Jc::CENTER,
                        ]);
                    } catch (\Throwable $e) {
                        $cell->addTextBreak(1);
                    }
                } else {
                    $cell->addTextBreak(1);
                }
                $cell->addText((string) ($signer['name'] ?? ''), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceBefore' => 40]);
            }
        }

    }

    /**
     * Khối cuối trang theo biểu mẫu giấy: bảng môn bên trái, KÍ HIỆU CHUNG bên
     * phải, cùng nằm trong một bảng hai cột để hai khối thẳng hàng nhau.
     *
     * @param  Collection<int, object>  $legend
     * @param  array<string, mixed>  $meta
     */
    protected function renderLegendAndNotes($section, Collection $legend, array $meta): void
    {
        $notes = array_values(array_filter(
            (array) ($meta['common_notes'] ?? []),
            fn ($n) => is_array($n) && trim((string) ($n['term'] ?? '')) !== ''
        ));

        if ($legend->isEmpty() && $notes === []) {
            return;
        }

        $wrapper = $section->addTable([
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
            'cellMargin' => 40,
        ]);
        $wrapper->addRow();

        $leftCell = $wrapper->addCell(6200, ['valign' => 'top']);
        if ($legend->isNotEmpty()) {
            $this->renderLegendTable($leftCell, $legend);
        }

        $rightCell = $wrapper->addCell(4800, ['valign' => 'top']);
        if ($notes !== []) {
            $this->renderCommonNotes($rightCell, $notes, (string) ($meta['common_notes_title'] ?? 'KÍ HIỆU CHUNG'));
        }
    }

    /**
     * Bảng môn 8 cột theo biểu mẫu, kèm hàng "Tổng số" cộng dồn.
     *
     * @param  Collection<int, object>  $legend
     */
    protected function renderLegendTable($container, Collection $legend): void
    {
        $columns = [
            ['label' => 'Kí hiệu', 'width' => 780],
            ['label' => 'Tên môn học', 'width' => 2300],
            ['label' => 'Tín chỉ', 'width' => 520],
            ['label' => 'Tổng số', 'width' => 560],
            ['label' => 'Lý thuyết', 'width' => 600],
            ['label' => 'TH/TL/TT', 'width' => 640],
            ['label' => 'Thi, KT', 'width' => 560],
            ['label' => 'Khoa', 'width' => 700],
        ];

        $table = $container->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'unit' => TblWidth::AUTO,
            'cellMargin' => 20,
        ]);

        $table->addRow(220);
        foreach ($columns as $column) {
            $table->addCell($column['width'], ['valign' => 'center'])
                ->addText($column['label'], ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        $totals = ['credits' => 0, 'total' => 0, 'theory' => 0, 'practice' => 0, 'exam' => 0];

        foreach ($legend as $subj) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += (int) ($subj->{$key} ?? 0);
            }

            $table->addRow(200);
            $values = [
                [(string) $subj->code, Jc::CENTER],
                [(string) $subj->name, Jc::START],
                [$this->numberOrBlank($subj->credits ?? null), Jc::CENTER],
                [$this->numberOrBlank($subj->total ?? null), Jc::CENTER],
                [$this->numberOrBlank($subj->theory ?? null), Jc::CENTER],
                [$this->numberOrBlank($subj->practice ?? null), Jc::CENTER],
                [$this->numberOrBlank($subj->exam ?? null), Jc::CENTER],
                [(string) ($subj->faculty ?? ''), Jc::CENTER],
            ];

            foreach ($values as $index => [$value, $align]) {
                $table->addCell($columns[$index]['width'], ['valign' => 'center'])
                    ->addText($value, ['size' => 7], ['alignment' => $align, 'spaceAfter' => 0]);
            }
        }

        // Hàng tổng: gộp hai cột đầu làm nhãn, các cột số cộng dồn.
        $table->addRow(210);
        $labelCell = $table->addCell($columns[0]['width'] + $columns[1]['width'], [
            'gridSpan' => 2,
            'valign' => 'center',
        ]);
        $labelCell->addText('Tổng số', ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        foreach (['credits', 'total', 'theory', 'practice', 'exam'] as $offset => $key) {
            $table->addCell($columns[$offset + 2]['width'], ['valign' => 'center'])
                ->addText((string) $totals[$key], ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        $table->addCell($columns[7]['width'], ['valign' => 'center'])
            ->addText('', ['size' => 7], ['spaceAfter' => 0]);
    }

    /**
     * Ô "KÍ HIỆU CHUNG": giải thích các ký hiệu không phải môn học.
     *
     * @param  list<array{term?: string, meaning?: string}>  $notes
     */
    protected function renderCommonNotes($container, array $notes, string $title): void
    {
        $box = $container->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
            'cellMargin' => 60,
        ]);

        $box->addRow(240);
        $box->addCell(4800, ['gridSpan' => 2, 'valign' => 'center'])
            ->addText($title, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        foreach ($notes as $note) {
            $box->addRow(200);
            $box->addCell(1500, ['valign' => 'top'])
                ->addText((string) ($note['term'] ?? ''), ['bold' => true, 'size' => 7], ['spaceAfter' => 0]);
            $box->addCell(3300, ['valign' => 'top'])
                ->addText((string) ($note['meaning'] ?? ''), ['size' => 7], ['spaceAfter' => 0]);
        }
    }

    /** Ô số: 0 để trống cho giống biểu mẫu giấy. */
    protected function numberOrBlank($value): string
    {
        $number = (int) $value;

        return $number === 0 ? '' : (string) $number;
    }

    /**
     * Gộp tuần liên tiếp cùng tháng: [[month=>6,count=>2], [month=>7,count=>4], ...]
     *
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return list<array{month:int,start:int,count:int}>
     */
    protected function monthSpans(array $weeks): array
    {
        $spans = [];
        foreach ($weeks as $wi => $week) {
            $m = (int) $week['start']->format('n');
            if ($spans === [] || $spans[array_key_last($spans)]['month'] !== $m) {
                $spans[] = ['month' => $m, 'start' => $wi, 'count' => 1];
            } else {
                $spans[array_key_last($spans)]['count']++;
            }
        }

        return $spans ?: [['month' => (int) now()->format('n'), 'start' => 0, 'count' => 1]];
    }

    protected function resolveImage(string $relative): ?string
    {
        if ($relative === '') {
            return null;
        }
        foreach ([
            storage_path('app/public/'.$relative),
            public_path('images/'.$relative),
            public_path($relative),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<array{start:Carbon,end:Carbon}>
     */
    protected function buildWeeks(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $guard = 0;
        while ($cursor->lte($end) && $guard < 40) {
            $weeks[] = [
                'start' => $cursor->copy(),
                'end' => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
            ];
            $cursor->addWeek();
            $guard++;
        }

        return $weeks ?: [[
            'start' => $start->copy()->startOfWeek(Carbon::MONDAY),
            'end' => $start->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ]];
    }

    /**
     * @param  Collection<int, object>  $cells
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return array<string, array{labels:list<string>}>
     */
    protected function buildGrid(Collection $cells, array $weeks): array
    {
        $weekIndexByMonday = [];
        foreach ($weeks as $wi => $week) {
            $weekIndexByMonday[$week['start']->format('Y-m-d')] = $wi;
        }
        $grid = [];
        foreach ($cells as $cell) {
            $date = Carbon::parse($cell->date)->startOfDay();
            $isoDow = (int) $date->dayOfWeekIso;
            if ($isoDow > 5) {
                continue;
            }
            $monday = $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            if (! isset($weekIndexByMonday[$monday])) {
                continue;
            }
            $wi = $weekIndexByMonday[$monday];
            $session = ((int) $cell->period) <= 5 ? 'am' : 'pm';
            $key = $wi.'|'.$isoDow.'|'.$session;
            if (! isset($grid[$key])) {
                $grid[$key] = ['labels' => []];
            }
            $label = trim((string) $cell->label);
            if ($label !== '' && ! in_array($label, $grid[$key]['labels'], true)) {
                $grid[$key]['labels'][] = $label;
            }
        }

        return $grid;
    }

    /**
     * @param  Collection<int, object>  $cells
     * @param  list<array{start:Carbon,end:Carbon}>  $weeks
     * @return array<string, list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>>
     */
    protected function buildGroupedGrid(Collection $cells, array $weeks): array
    {
        $weekIndexByMonday = [];
        foreach ($weeks as $weekIndex => $week) {
            $weekIndexByMonday[$week['start']->format('Y-m-d')] = $weekIndex;
        }

        $days = [];
        foreach ($cells as $cell) {
            $date = Carbon::parse($cell->date)->startOfDay();
            $isoDow = (int) $date->dayOfWeekIso;
            if ($isoDow > 5) {
                continue;
            }
            $monday = $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            if (! isset($weekIndexByMonday[$monday])) {
                continue;
            }

            $weekIndex = $weekIndexByMonday[$monday];
            $dayKey = $weekIndex.'|'.$isoDow;
            $days[$dayKey][] = $cell;
        }

        $grid = [];
        foreach ($days as $dayKey => $dayCells) {
            usort($dayCells, static fn ($left, $right): int => ((int) $left->period) <=> ((int) $right->period));
            foreach (array_values($dayCells) as $cell) {
                $periodStart = (int) $cell->period;
                $periodEnd = max($periodStart, (int) ($cell->period_end ?? $periodStart));
                $label = trim((string) ($cell->subject_label ?? $cell->label ?? ''));
                $grid[$dayKey][] = [
                    'labels' => $label === '' ? [] : [$label],
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'period_label' => trim((string) ($cell->period_label ?? $periodStart)),
                ];
            }
        }

        return $grid;
    }

    /**
     * Một cột Tiết dùng chung không thể đồng thời ghi 1-5 cho tuần A và
     * 1-3/4-5 cho tuần B. Vì vậy các tuần liên tiếp chỉ nằm chung một trang
     * khi toàn bộ Thứ 2-6 có cùng cách chia khoảng tiết. Khi layout đổi, tạo
     * trang mới; dữ liệu và số tuần vẫn giữ đúng thứ tự ban đầu.
     *
     * @param  array<string, mixed>  $classData
     * @return list<array<string, mixed>>
     */
    protected function classPages(array $classData): array
    {
        // Mẫu LHL hiện hành dùng một trục tiết cố định cho mọi tuần. Không
        // tách trang theo từng cách chia của dữ liệu; ô môn sẽ được điền vào
        // đúng slot 1-3, 4-5 hoặc 6-9.
        return [array_replace($classData, [
            'week_number_offset' => 0,
            'layout_page' => 1,
            'layout_page_count' => 1,
        ])];

        /** @var Carbon $start */
        $start = $classData['start'];
        /** @var Carbon $end */
        $end = $classData['end'];
        /** @var Collection<int, object> $cells */
        $cells = $classData['cells'];
        $weeks = $this->buildWeeks($start, $end);
        $grid = $this->buildGroupedGrid($cells, $weeks);

        $batches = [];
        foreach ($weeks as $weekIndex => $week) {
            $signature = [];
            for ($isoDay = 1; $isoDay <= 5; $isoDay++) {
                $signature[$isoDay] = $this->groupedSegmentsForWeek($grid, $weekIndex, $isoDay);
            }
            $signatureKey = json_encode($signature, JSON_THROW_ON_ERROR);
            $lastIndex = array_key_last($batches);
            if ($lastIndex === null || $batches[$lastIndex]['signature'] !== $signatureKey) {
                $batches[] = [
                    'signature' => $signatureKey,
                    'start_index' => $weekIndex,
                    'end_index' => $weekIndex,
                ];
            } else {
                $batches[$lastIndex]['end_index'] = $weekIndex;
            }
        }

        $pageCount = count($batches);

        return array_map(function (array $batch, int $pageIndex) use (
            $classData,
            $cells,
            $weeks,
            $start,
            $end,
            $pageCount
        ): array {
            $firstWeek = $weeks[$batch['start_index']];
            $lastWeek = $weeks[$batch['end_index']];
            $pageStart = $firstWeek['start']->lt($start) ? $start->copy() : $firstWeek['start']->copy();
            $pageEnd = $lastWeek['end']->gt($end) ? $end->copy() : $lastWeek['end']->copy();

            return array_replace($classData, [
                'start' => $pageStart,
                'end' => $pageEnd,
                'cells' => $cells->filter(function (object $cell) use ($pageStart, $pageEnd): bool {
                    $date = Carbon::parse($cell->date)->startOfDay();

                    return $date->betweenIncluded($pageStart, $pageEnd);
                })->values(),
                'week_number_offset' => $batch['start_index'],
                'layout_page' => $pageIndex + 1,
                'layout_page_count' => $pageCount,
            ]);
        }, $batches, array_keys($batches));
    }

    /**
     * Tạo một trục tiết chung, liên tục và không chồng lấn cho tất cả tuần.
     * Các mốc bắt đầu/kết thúc thực tế trở thành ranh giới hàng: nếu có nhóm
     * 1-3 và 4-5 thì cột Tiết chỉ sinh 1-3, 4-5 (không sinh thêm 1-2, 1-4...).
     * Khi các tuần chia nhóm khác nhau, trục chung được tách nhỏ vừa đủ để
     * mọi khoảng vẫn biểu diễn được mà không merge ô môn học.
     *
     * @param  array<string, list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>>  $grid
     * @return list<array{period_start:int,period_end:int}>
     */
    protected function groupedSegments(
        array $grid,
        int $isoDow,
        int $weekCount
    ): array {
        return [
            ['period_start' => 1, 'period_end' => 3],
            ['period_start' => 4, 'period_end' => 5],
            ['period_start' => 6, 'period_end' => 9],
        ];
    }

    /**
     * @param  array<string, list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>>  $grid
     * @return list<array{period_start:int,period_end:int}>
     */
    protected function groupedSegmentsForWeek(
        array $grid,
        int $weekIndex,
        int $isoDow
    ): array {
        return $this->segmentsFromEntries($grid[$weekIndex.'|'.$isoDow] ?? []);
    }

    /**
     * @param  list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>  $entries
     * @return list<array{period_start:int,period_end:int}>
     */
    protected function segmentsFromEntries(array $entries): array
    {
        // Luôn giữ ranh giới hai buổi để không gom xuyên từ tiết 5 sang 6.
        $boundaries = [1 => true, 6 => true, 10 => true];
        foreach ($entries as $entry) {
            $start = max(1, min(9, (int) $entry['period_start']));
            $end = max($start, min(9, (int) $entry['period_end']));
            $boundaries[$start] = true;
            $boundaries[$end + 1] = true;
        }

        $points = array_keys($boundaries);
        sort($points, SORT_NUMERIC);
        $segments = [];
        for ($index = 0, $last = count($points) - 1; $index < $last; $index++) {
            $start = (int) $points[$index];
            $end = (int) $points[$index + 1] - 1;
            if ($start <= 9 && $end >= $start) {
                $segments[] = [
                    'period_start' => $start,
                    'period_end' => min(9, $end),
                ];
            }
        }

        return $segments;
    }

    /**
     * @param  array<string, list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>>  $grid
     */
    protected function groupedRowCount(array $grid, int $isoDow, int $weekCount): int
    {
        return count($this->groupedSegments($grid, $isoDow, $weekCount));
    }

    /**
     * @param  array<string, list<array{labels:list<string>,period_start:int,period_end:int,period_label:string}>>  $grid
     * @param  array{period_start:int,period_end:int}  $segment
     * @return array{labels:list<string>,period_start:int,period_end:int,period_label:string}|null
     */
    protected function groupedEntryForRange(
        array $grid,
        int $weekIndex,
        int $isoDow,
        array $segment
    ): ?array {
        $entries = collect($grid[$weekIndex.'|'.$isoDow] ?? [])
            ->filter(
                static fn (array $candidate): bool => $candidate['period_start'] <= $segment['period_start']
                    && $candidate['period_end'] >= $segment['period_end']
            )
            ->values();
        if ($entries->isEmpty()) {
            return null;
        }

        $entry = $entries->first();
        $entry['labels'] = $entries
            ->flatMap(static fn (array $candidate): array => $candidate['labels'])
            ->filter(static fn (mixed $label): bool => trim((string) $label) !== '')
            ->unique()
            ->values()
            ->all();

        return $entry;
    }

    protected function periodRangeLabel(int $start, int $end): string
    {
        return $start === $end ? (string) $start : $start.'÷'.$end;
    }

    protected function contrastText(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '000000';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luma < 0.48 ? 'FFFFFF' : '000000';
    }
}
