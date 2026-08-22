<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\TrainingSchedule\Exports\GroupedTrainingPlanFromTemplateExport;
use Modules\TrainingSchedule\Exports\TrainingPlanFromTemplateExport;
use Modules\TrainingSchedule\Exports\TrainingPlanPdfExport;
use Modules\TrainingSchedule\Exports\TrainingPlanWordExport;
use Tests\TestCase;

class LhlGroupedTemplateExportTest extends TestCase
{
    public function test_grouped_template_has_three_rows_per_day_and_keeps_subject_groups(): void
    {
        $export = new GroupedTrainingPlanFromTemplateExport([
            $this->classData(collect([
                $this->cell('2026-08-17', 2, 3, 'TTT'),
                $this->cell('2026-08-17', 4, 5, 'GPSL'),
                $this->cell('2026-08-17', 6, 9, 'TTT'),
            ])),
        ]);

        $sheet = $export->build()->getSheet(0);

        $this->assertContains('A10:A12', $sheet->getMergeCells());
        $this->assertSame('1 ÷ 3', $sheet->getCell('B10')->getValue());
        $this->assertSame('4 ÷ 5', $sheet->getCell('B11')->getValue());
        $this->assertSame('6 ÷ 9', $sheet->getCell('B12')->getValue());
        $this->assertSame('TTT', $sheet->getCell('C10')->getValue());
        $this->assertSame('GPSL', $sheet->getCell('C11')->getValue());
        $this->assertSame('TTT', $sheet->getCell('C12')->getValue());
        $this->assertSame('Ghi chú:', $sheet->getCell('A27')->getValue());
    }

    public function test_grouped_excel_repeats_a_full_1_to_5_subject_into_both_fixed_morning_slots(): void
    {
        $export = new GroupedTrainingPlanFromTemplateExport([
            $this->classData(collect([
                $this->cell('2026-08-17', 1, 5, 'TTT'),
                $this->cell('2026-08-17', 6, 9, 'GPSL'),
            ])),
        ]);

        $sheet = $export->build()->getSheet(0);

        $this->assertSame('1 ÷ 3', $sheet->getCell('B10')->getValue());
        $this->assertSame('4 ÷ 5', $sheet->getCell('B11')->getValue());
        $this->assertSame('TTT', $sheet->getCell('C10')->getValue());
        $this->assertSame('TTT', $sheet->getCell('C11')->getValue());
        $this->assertSame('GPSL', $sheet->getCell('C12')->getValue());
    }

    public function test_classic_template_still_has_two_rows_per_day(): void
    {
        $export = new TrainingPlanFromTemplateExport([
            $this->classData(collect([
                $this->cell('2026-08-17', 1, 5, 'TTT'),
                $this->cell('2026-08-17', 6, 9, 'GPSL'),
            ])),
        ]);

        $sheet = $export->build()->getSheet(0);

        $this->assertContains('A10:A11', $sheet->getMergeCells());
        $this->assertSame('1 ÷ 5', $sheet->getCell('B10')->getValue());
        $this->assertSame('6 ÷ 9', $sheet->getCell('B11')->getValue());
        $this->assertSame("1-5\nTTT", $sheet->getCell('C10')->getValue());
        $this->assertSame("6-9\nGPSL", $sheet->getCell('C11')->getValue());
    }

    public function test_excel_diagonal_is_deduplicated_and_anchored_to_header_frame(): void
    {
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open(config('lhl_export.template_xlsx')) === true);
        $sourceXml = $zip->getFromName('xl/drawings/drawing1.xml');
        $zip->close();
        $this->assertIsString($sourceXml);

        $export = new TrainingPlanFromTemplateExport([]);
        $method = new \ReflectionMethod($export, 'transformTemplateDrawingXml');
        $xml = $method->invoke($export, $sourceXml);

        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace(
            'xdr',
            'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing'
        );
        $diagonals = $xpath->query(
            '/xdr:wsDr/*[xdr:from/xdr:col="1" and xdr:from/xdr:row="7"'
            .' and xdr:to/xdr:col="2" and xdr:to/xdr:row="9" and .//xdr:cxnSp]'
        );

        $this->assertSame(1, $diagonals?->length);
        $this->assertSame(
            0,
            $xpath->query(
                '/xdr:wsDr/*[xdr:from/xdr:col="1" and xdr:from/xdr:row="7"'
                .' and xdr:to/xdr:col="1" and xdr:to/xdr:row="9" and .//xdr:cxnSp]'
            )?->length
        );
    }

    public function test_word_header_images_are_scaled_inside_their_cells(): void
    {
        $export = new TrainingPlanWordExport([]);
        $method = new \ReflectionMethod($export, 'headerImageSize');

        [$labelWidth, $labelHeight] = $method->invoke($export, 900);
        [$weekWidth, $weekHeight] = $method->invoke($export, 750);

        $this->assertLessThanOrEqual(60, $labelWidth);
        $this->assertLessThanOrEqual(36, $labelHeight);
        $this->assertLessThanOrEqual(50, $weekWidth);
        $this->assertLessThanOrEqual(36, $weekHeight);
    }

    public function test_word_grouped_layout_uses_one_row_for_each_real_consecutive_subject_group(): void
    {
        $export = new TrainingPlanWordExport([], [], 'grouped_periods');
        $cells = collect([
            $this->cell('2026-08-17', 1, 2, 'A'),
            $this->cell('2026-08-17', 3, 3, 'B'),
            $this->cell('2026-08-17', 4, 5, 'C'),
            $this->cell('2026-08-17', 6, 7, 'D'),
            $this->cell('2026-08-17', 8, 9, 'E'),
        ]);

        $weeksMethod = new \ReflectionMethod($export, 'buildWeeks');
        $weeks = $weeksMethod->invoke($export, Carbon::parse('2026-08-17'), Carbon::parse('2026-08-23'));
        $gridMethod = new \ReflectionMethod($export, 'buildGroupedGrid');
        $grid = $gridMethod->invoke($export, $cells, $weeks);
        $countMethod = new \ReflectionMethod($export, 'groupedRowCount');

        $this->assertSame(3, $countMethod->invoke($export, $grid, 1, 1));
        $this->assertSame(['A'], $grid['0|1'][0]['labels']);
        $this->assertSame(4, $grid['0|1'][2]['period_start']);
        $this->assertSame(5, $grid['0|1'][2]['period_end']);
    }

    public function test_word_and_pdf_period_axis_matches_the_actual_consecutive_subject_groups(): void
    {
        $export = new TrainingPlanWordExport([], [], 'grouped_periods');
        $cells = collect([
            $this->cell('2026-08-17', 1, 3, 'TTT'),
            $this->cell('2026-08-17', 4, 5, 'GPSL'),
            $this->cell('2026-08-17', 6, 9, 'TTT'),
        ]);

        $weeksMethod = new \ReflectionMethod($export, 'buildWeeks');
        $weeks = $weeksMethod->invoke($export, Carbon::parse('2026-08-17'), Carbon::parse('2026-08-23'));
        $gridMethod = new \ReflectionMethod($export, 'buildGroupedGrid');
        $grid = $gridMethod->invoke($export, $cells, $weeks);
        $segmentsMethod = new \ReflectionMethod($export, 'groupedSegments');
        $segments = $segmentsMethod->invoke($export, $grid, 1, 1);
        $entryMethod = new \ReflectionMethod($export, 'groupedEntryForRange');

        $this->assertSame([
            ['period_start' => 1, 'period_end' => 3],
            ['period_start' => 4, 'period_end' => 5],
            ['period_start' => 6, 'period_end' => 9],
        ], $segments);
        $this->assertSame(['TTT'], $entryMethod->invoke($export, $grid, 0, 1, $segments[0])['labels']);
        $this->assertSame(['GPSL'], $entryMethod->invoke($export, $grid, 0, 1, $segments[1])['labels']);
        $this->assertSame(['TTT'], $entryMethod->invoke($export, $grid, 0, 1, $segments[2])['labels']);
    }

    public function test_word_grouped_layout_builds_continuous_non_overlapping_period_rows(): void
    {
        $export = new TrainingPlanWordExport([], [], 'grouped_periods');
        $cells = collect([
            $this->cell('2026-08-17', 1, 5, 'FULL'),
            $this->cell('2026-08-24', 1, 3, 'FIRST'),
            $this->cell('2026-08-24', 4, 5, 'SECOND'),
        ]);

        $weeksMethod = new \ReflectionMethod($export, 'buildWeeks');
        $weeks = $weeksMethod->invoke($export, Carbon::parse('2026-08-17'), Carbon::parse('2026-08-30'));
        $gridMethod = new \ReflectionMethod($export, 'buildGroupedGrid');
        $grid = $gridMethod->invoke($export, $cells, $weeks);
        $segmentsMethod = new \ReflectionMethod($export, 'groupedSegments');
        $segments = $segmentsMethod->invoke($export, $grid, 1, 2);
        $entryMethod = new \ReflectionMethod($export, 'groupedEntryForRange');

        $this->assertSame([
            ['period_start' => 1, 'period_end' => 3],
            ['period_start' => 4, 'period_end' => 5],
            ['period_start' => 6, 'period_end' => 9],
        ], $segments);
        $this->assertSame(['FULL'], $entryMethod->invoke($export, $grid, 0, 1, $segments[0])['labels']);
        $this->assertSame(['FULL'], $entryMethod->invoke($export, $grid, 0, 1, $segments[1])['labels']);
        $this->assertSame(['FIRST'], $entryMethod->invoke($export, $grid, 1, 1, $segments[0])['labels']);
        $this->assertSame(['SECOND'], $entryMethod->invoke($export, $grid, 1, 1, $segments[1])['labels']);
        $this->assertNull($entryMethod->invoke($export, $grid, 1, 1, $segments[2]));
    }

    public function test_word_grouped_layout_never_emits_overlapping_rows_from_mixed_week_patterns(): void
    {
        $export = new TrainingPlanWordExport([], [], 'grouped_periods');
        $cells = collect([
            $this->cell('2026-08-17', 1, 2, 'A'),
            $this->cell('2026-08-17', 3, 5, 'B'),
            $this->cell('2026-08-24', 1, 3, 'C'),
            $this->cell('2026-08-24', 4, 5, 'D'),
        ]);

        $weeksMethod = new \ReflectionMethod($export, 'buildWeeks');
        $weeks = $weeksMethod->invoke($export, Carbon::parse('2026-08-17'), Carbon::parse('2026-08-30'));
        $gridMethod = new \ReflectionMethod($export, 'buildGroupedGrid');
        $grid = $gridMethod->invoke($export, $cells, $weeks);
        $segmentsMethod = new \ReflectionMethod($export, 'groupedSegments');
        $segments = $segmentsMethod->invoke($export, $grid, 1, 2);

        $this->assertSame([
            ['period_start' => 1, 'period_end' => 3],
            ['period_start' => 4, 'period_end' => 5],
            ['period_start' => 6, 'period_end' => 9],
        ], $segments);

        $this->assertSame(3, $segments[0]['period_end']);
        $this->assertSame(4, $segments[1]['period_start']);
        $this->assertSame(5, $segments[1]['period_end']);
    }

    public function test_word_and_pdf_keep_one_fixed_three_slot_grid_across_weeks(): void
    {
        $classData = $this->classData(collect([
            $this->cell('2026-08-17', 1, 5, 'FULL'),
            $this->cell('2026-08-17', 6, 9, 'PM'),
            $this->cell('2026-08-24', 1, 3, 'FIRST'),
            $this->cell('2026-08-24', 4, 5, 'SECOND'),
            $this->cell('2026-08-24', 6, 9, 'PM'),
        ]));
        $classData['end'] = Carbon::parse('2026-08-30');

        $word = new TrainingPlanWordExport([$classData], [], 'grouped_periods');
        $pagesMethod = new \ReflectionMethod($word, 'classPages');
        $pages = $pagesMethod->invoke($word, $classData);

        $this->assertCount(1, $pages);
        $this->assertSame([0], array_column($pages, 'week_number_offset'));
        $this->assertSame('2026-08-17', $pages[0]['start']->format('Y-m-d'));
        $this->assertSame('2026-08-30', $pages[0]['end']->format('Y-m-d'));

        $pdf = new TrainingPlanPdfExport([$classData], [], 'grouped_periods');
        $documentsMethod = new \ReflectionMethod($pdf, 'documents');
        $documents = $documentsMethod->invoke($pdf);

        $this->assertCount(1, $documents);
        $this->assertSame([1, 2], array_column($documents[0]['weeks'], 'number'));
        $this->assertSame(
            ['1–3', '4–5', '6–9'],
            array_column($documents[0]['days'][0]['rows'], 'period')
        );
        $this->assertSame('FULL', $documents[0]['days'][0]['rows'][0]['cells'][0]);
        $this->assertSame('FULL', $documents[0]['days'][0]['rows'][1]['cells'][0]);
    }

    private function classData($cells): array
    {
        return [
            'sheet_title' => 'DEMO',
            'class_name' => 'Y54',
            'semester_label' => 'Học kỳ 2',
            'academic_year' => '2026-2027',
            'start' => Carbon::parse('2026-08-17'),
            'end' => Carbon::parse('2026-08-23'),
            'cells' => $cells,
            'legend' => collect(),
        ];
    }

    private function cell(
        string $date,
        int $start,
        int $end,
        string $subject
    ): object {
        $periodLabel = $start === $end ? (string) $start : $start.'-'.$end;

        return (object) [
            'date' => $date,
            'period' => $start,
            'period_end' => $end,
            'period_label' => $periodLabel,
            'subject_label' => $subject,
            'label' => $periodLabel."\n".$subject,
            'subject_id' => 1,
        ];
    }
}
