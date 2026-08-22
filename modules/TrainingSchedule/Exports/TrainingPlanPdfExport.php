<?php

namespace Modules\TrainingSchedule\Exports;

use Illuminate\Support\Collection;
use Modules\TrainingSchedule\Services\LhlPeriodLayoutSelector;
use Mpdf\Mpdf;

/** Xuất LHL PDF trực tiếp từ payload, không chuyển đổi qua DOCX/LibreOffice. */
class TrainingPlanPdfExport extends TrainingPlanWordExport
{
    public function savePdf(string $path): string
    {
        $tempDirectory = storage_path('app/tmp/mpdf-lhl');
        foreach ([dirname($path), $tempDirectory] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Không thể tạo thư mục tạm để xuất LHL PDF.');
            }
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A3-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 7,
            'margin_bottom' => 7,
            'tempDir' => $tempDirectory,
            'default_font' => 'dejavuserif',
        ]);
        $mpdf->SetTitle('Lịch huấn luyện');
        $mpdf->SetAuthor('CDHC2 LMS');
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->WriteHTML(view('training-schedule::exports.training-plan-pdf', [
            'documents' => $this->documents(),
        ])->render());
        $mpdf->Output($path, 'F');

        if (! is_file($path) || filesize($path) < 100) {
            throw new \RuntimeException('mPDF không tạo được file LHL PDF hợp lệ.');
        }

        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function documents(): array
    {
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

        return $pages->map(function (array $classData, int $index) use ($meta): array {
            $weeks = $this->buildWeeks($classData['start'], $classData['end']);
            /** @var Collection<int, object> $cells */
            $cells = $classData['cells'];
            $grouped = $this->layout === LhlPeriodLayoutSelector::GROUPED_PERIODS;
            $grid = $grouped
                ? $this->buildGroupedGrid($cells, $weeks)
                : $this->buildGrid($cells, $weeks);

            $days = [];
            for ($isoDay = 1; $isoDay <= 5; $isoDay++) {
                $ranges = $grouped
                    ? $this->groupedSegments($grid, $isoDay, count($weeks))
                    : [
                        ['key' => 'am', 'period_start' => 1, 'period_end' => 5],
                        ['key' => 'pm', 'period_start' => 6, 'period_end' => 9],
                    ];
                $rows = [];
                foreach ($ranges as $range) {
                    $weekCells = [];
                    foreach ($weeks as $weekIndex => $week) {
                        if ($grouped) {
                            $entry = $this->groupedEntryForRange($grid, $weekIndex, $isoDay, $range);
                        } else {
                            $entry = $grid[$weekIndex.'|'.$isoDay.'|'.$range['key']] ?? null;
                        }
                        $weekCells[] = $entry ? implode("\n", $entry['labels']) : '';
                    }
                    $rows[] = [
                        'period' => $this->pdfPeriodLabel($range['period_start'], $range['period_end']),
                        'cells' => $weekCells,
                    ];
                }
                $days[] = [
                    'number' => $isoDay + 1,
                    'rows' => $rows,
                ];
            }

            $semesterLine = trim((string) ($meta['semester_line'] ?? ''));
            if ($semesterLine === '') {
                $semesterLine = trim(
                    (string) $classData['semester_label'].' năm học '.(string) $classData['academic_year']
                );
            }

            return [
                'new_page' => $index > 0,
                'class' => $classData,
                'meta' => $meta,
                'semester_line' => $semesterLine,
                'weeks' => collect($weeks)->map(fn (array $week, int $weekIndex): array => [
                    'number' => (int) ($classData['week_number_offset'] ?? 0) + $weekIndex + 1,
                    'start_day' => $week['start']->format('d'),
                    'end_day' => $week['end']->format('d'),
                    'date_image' => $this->imageSource(
                        $this->makeWeekDatePng($week['start']->format('d'), $week['end']->format('d'), 100, 50)
                    ),
                ])->all(),
                'month_spans' => $this->monthSpans($weeks),
                'days' => $days,
                'label_image' => $this->imageSource($this->makeLabelDiagonalPng(100, 50)),
                'signers' => collect(is_array($meta['signers'] ?? null) ? $meta['signers'] : [])
                    ->filter(fn (array $signer): bool => ! isset($signer['enabled']) || (bool) $signer['enabled'])
                    ->map(function (array $signer): array {
                        $signer['image_src'] = $this->imageSource(
                            $this->resolveImage((string) ($signer['image'] ?? ''))
                        );

                        return $signer;
                    })->values()->all(),
            ];
        })->all();
    }

    protected function pdfPeriodLabel(int $start, int $end): string
    {
        return $start === $end ? (string) $start : $start.'–'.$end;
    }

    protected function imageSource(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }
        $normalized = str_replace('\\', '/', $path);

        return preg_match('/^[A-Za-z]:\//', $normalized) === 1
            ? 'file:///'.ltrim($normalized, '/')
            : 'file://'.$normalized;
    }
}
