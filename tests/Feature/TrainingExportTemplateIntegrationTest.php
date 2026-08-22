<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Services\TemplateActivationService;
use Modules\ExportTemplates\Services\TemplateBindingService;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Services\TrainingExportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TrainingExportTemplateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lhl_export_uses_active_excel_template(): void
    {
        Storage::fake('local');
        $detail = ScheduleDetail::factory()->create([
            'date' => '2026-03-02',
            'period' => 1,
        ]);
        $path = 'export-templates/tests/lhl-active.xlsx';
        Storage::disk('local')->makeDirectory('export-templates/tests');
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('LHL');
        $sheet->setCellValue('A1', 'TEMPLATE ACTIVE');
        (new Xlsx($book))->save(Storage::disk('local')->path($path));
        $book->disconnectWorksheets();

        $template = ExportTemplate::query()->create([
            'code' => 'lhl-active-integration',
            'name' => 'LHL Active Integration',
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            // Một tiết đơn lẻ cần layout 3 hàng/ngày, nên Active đúng feature
            // grouped để kiểm tra bộ chọn mẫu không dùng nhầm mẫu classic.
            'feature_key' => 'lhl.training_plan.grouped_periods',
            'output_format' => OutputFormat::EXCEL,
            'file_path' => $path,
            'disk' => 'local',
            'original_name' => 'lhl-active.xlsx',
            'status' => TemplateStatus::DRAFT,
        ]);
        $version = $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => 'lhl-active.xlsx',
            'file_extension' => 'xlsx',
            'status' => TemplateStatus::DRAFT,
            'manifest' => [
                'targets' => [[
                    'ref' => 'excel:cell:LHL!A1',
                    'kind' => 'cell',
                    'sheet' => 'LHL',
                    'address' => 'A1',
                ]],
            ],
        ]);
        app(TemplateBindingService::class)->bind(
            $version,
            'excel:cell:LHL!A1',
            'class.name'
        );
        app(TemplateActivationService::class)->activate($version);

        $response = app(TrainingExportService::class)->exportTrainingCalendar(
            [$detail->training_schedule_id],
            '2026-03-02',
            '2026-03-08',
            [],
            'xlsx'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('active', $response->headers->get('X-Template-Engine'));
        $this->assertSame('grouped_periods', $response->headers->get('X-LHL-Layout'));
        $output = $response->getFile()->getPathname();
        $rendered = IOFactory::load($output);
        $this->assertNotSame('TEMPLATE ACTIVE', $rendered->getActiveSheet()->getCell('A1')->getValue());
        $rendered->disconnectWorksheets();
        $this->assertDatabaseHas('export_template_audit_logs', [
            'template_version_id' => $version->id,
            'action' => ExportTemplateAuditLog::ACTION_RENDERED,
        ]);

        if (is_file($output)) {
            unlink($output);
        }
    }

    public function test_lhl_word_automatically_uses_grouped_layout_and_keeps_periods_four_to_five(): void
    {
        config()->set('lhl_export.template_engine_enabled', false);
        $first = ScheduleDetail::factory()->create([
            'date' => '2026-08-17',
            'period' => 1,
            'lesson_type' => 'theory',
        ]);
        $ttt = Subject::query()->findOrFail($first->subject_id);
        $ttt->update([
            'name' => 'Thể thao tổng hợp',
            'abbreviation' => 'TTT',
            'theory_hours' => 20,
        ]);
        $gpsl = $ttt->replicate();
        $gpsl->forceFill([
            'name' => 'Giáo dục pháp luật',
            'code' => $ttt->code.'-GPSL',
            'abbreviation' => 'GPSL',
        ])->save();

        foreach (range(2, 9) as $period) {
            ScheduleDetail::query()->create([
                'training_schedule_id' => $first->training_schedule_id,
                'date' => '2026-08-17',
                'period' => $period,
                'subject_id' => in_array($period, [4, 5], true) ? $gpsl->id : $ttt->id,
                'subject_lesson_id' => null,
                'instructor_id' => $first->instructor_id,
                'classroom_id' => $first->classroom_id,
                'lesson_type' => 'theory',
            ]);
        }

        $response = app(TrainingExportService::class)->exportTrainingCalendar(
            [$first->training_schedule_id],
            '2026-08-17',
            '2026-08-23',
            [],
            'docx'
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('grouped_periods', $response->headers->get('X-LHL-Layout'));

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();
        $this->assertIsString($binary);
        $path = tempnam(sys_get_temp_dir(), 'lhl_s23_').'.docx';
        file_put_contents($path, $binary);

        try {
            $archive = new \ZipArchive;
            $this->assertTrue($archive->open($path) === true);
            $xml = $archive->getFromName('word/document.xml');
            $archive->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('1÷3', $xml);
            $this->assertStringContainsString('4÷5', $xml);
            $this->assertStringContainsString('6÷9', $xml);
            $this->assertStringContainsString('TTT', $xml);
            $this->assertStringContainsString('GPSL', $xml);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_lhl_pdf_is_rendered_directly_by_mpdf_without_word_converter(): void
    {
        $pdfTemplate = file_get_contents(
            base_path('modules/TrainingSchedule/Views/exports/training-plan-pdf.blade.php')
        );
        $this->assertIsString($pdfTemplate);
        $this->assertDoesNotMatchRegularExpression('/@page\s*\{[^}]*\bsize\s*:/i', $pdfTemplate);

        config()->set('lhl_export.template_engine_enabled', false);
        $detail = ScheduleDetail::factory()->create([
            'date' => '2026-08-17',
            'period' => 4,
            'lesson_type' => 'theory',
        ]);

        $response = app(TrainingExportService::class)->exportTrainingCalendar(
            [$detail->training_schedule_id],
            '2026-08-17',
            '2026-08-23',
            [],
            'pdf'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('mpdf', $response->headers->get('X-PDF-Source'));
        $this->assertSame('mpdf-native', $response->headers->get('X-Template-Engine'));
        $this->assertSame('grouped_periods', $response->headers->get('X-LHL-Layout'));
        $this->assertStringStartsWith('%PDF-', file_get_contents($response->getFile()->getPathname()));

        $output = $response->getFile()->getPathname();
        if (is_file($output)) {
            unlink($output);
        }
    }
}
