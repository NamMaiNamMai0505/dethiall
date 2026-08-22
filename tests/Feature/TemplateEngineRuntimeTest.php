<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\ExportTemplates\Data\LhlTemplateDataProvider;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\TemplateActivationService;
use Modules\ExportTemplates\Services\TemplateBindingService;
use Modules\ExportTemplates\Services\TemplateEngineRegistry;
use Modules\ExportTemplates\Services\TemplateRenderService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TemplateEngineRuntimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_active_excel_engine_renders_scalar_repeating_table_and_style(): void
    {
        Storage::fake('local');
        $source = $this->excelSource();
        $version = $this->version(
            OutputFormat::EXCEL,
            'runtime-excel',
            'runtime.xlsx',
            $source,
            $this->excelManifest()
        );
        $binding = app(TemplateBindingService::class);
        $binding->bind(
            $version,
            'excel:cell:LHL!A1',
            'class.name',
            null,
            [],
            null,
            ['bold' => true, 'align' => 'center']
        );
        $binding->bind($version, 'excel:table:LHL:ScheduleRows', 'schedule_groups');
        $binding->bind($version, 'excel:cell:LHL!A3', 'schedule_groups[].period_label');
        $binding->bind($version, 'excel:cell:LHL!B3', 'schedule_groups[].subject_short_name');
        app(TemplateActivationService::class)->activate($version);

        $output = app(TemplateRenderService::class)->renderActiveWithMockData(
            'lhl.training_plan',
            OutputFormat::EXCEL
        );
        $this->temporaryFiles[] = $output;
        $book = IOFactory::load($output);
        $sheet = $book->getSheetByName('LHL');

        $this->assertSame('Y54', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertSame('1-3', $sheet->getCell('A3')->getValue());
        $this->assertSame('TTT', $sheet->getCell('B3')->getValue());
        $this->assertSame('4-5', $sheet->getCell('A4')->getValue());
        $this->assertSame('GPSL', $sheet->getCell('B4')->getValue());
        $this->assertSame('6-9', $sheet->getCell('A5')->getValue());
        $this->assertSame('TTT', $sheet->getCell('B5')->getValue());
        $this->assertSame('A2:B5', $sheet->getTableByName('ScheduleRows')->getRange());
        $this->assertDatabaseHas('export_template_audit_logs', [
            'template_version_id' => $version->id,
            'action' => ExportTemplateAuditLog::ACTION_RENDERED,
        ]);
        $audit = ExportTemplateAuditLog::query()
            ->where('template_version_id', $version->id)
            ->where('action', ExportTemplateAuditLog::ACTION_RENDERED)
            ->latest('id')
            ->firstOrFail();
        $this->assertIsNumeric($audit->metadata['duration_ms'] ?? null);
        $this->assertIsNumeric($audit->metadata['memory_peak_mb'] ?? null);
        $book->disconnectWorksheets();

        $unsafeData = app(LhlTemplateDataProvider::class)->mockData();
        $unsafeData['class']['name'] = '=HYPERLINK("https://invalid.test","X")';
        $unsafeOutput = app(TemplateEngineRegistry::class)
            ->get(OutputFormat::EXCEL)
            ->render($version, $unsafeData);
        $this->temporaryFiles[] = $unsafeOutput;
        $unsafeBook = IOFactory::load($unsafeOutput);
        $this->assertSame('s', $unsafeBook->getSheetByName('LHL')->getCell('A1')->getDataType());
        $unsafeBook->disconnectWorksheets();

        $user = User::factory()->create();
        Permission::findOrCreate('export-templates.index', 'web');
        $user->givePermissionTo('export-templates.index');
        $this->actingAs($user)
            ->get(route('export-templates.portal.test-export', [
                'portal' => 'lms',
                'exportTemplate' => $version->template,
            ]))
            ->assertOk()
            ->assertDownload('runtime-excel-mock.xlsx');
    }

    public function test_active_builder_excel_template_renders_without_uploaded_file(): void
    {
        $template = ExportTemplate::query()->create([
            'code' => 'builder-runtime',
            'name' => 'Builder Runtime',
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan',
            'output_format' => OutputFormat::EXCEL,
            'status' => TemplateStatus::DRAFT,
            'is_active' => false,
        ]);
        $version = $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => null,
            'original_name' => 'builder.xlsx',
            'file_extension' => 'xlsx',
            'status' => TemplateStatus::DRAFT,
        ]);
        $version->builderDocument()->create([
            'schema' => ['blocks' => [
                ['type' => 'text', 'props' => ['text' => 'Lớp {{class.name}}']],
            ]],
        ]);
        app(TemplateActivationService::class)->activate($version);
        $output = app(TemplateRenderService::class)->renderActiveWithMockData('lhl.training_plan', OutputFormat::EXCEL);
        $this->temporaryFiles[] = $output;
        $book = IOFactory::load($output);
        $this->assertSame('Lớp Y54', $book->getActiveSheet()->getCell('A1')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_active_builder_word_template_renders_without_uploaded_file(): void
    {
        $template = ExportTemplate::query()->create([
            'code' => 'builder-runtime-word', 'name' => 'Builder Runtime Word',
            'scope' => ExportTemplate::SCOPE_LMS, 'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan', 'output_format' => OutputFormat::WORD,
            'status' => TemplateStatus::DRAFT, 'is_active' => false,
        ]);
        $version = $template->versions()->create([
            'version_number' => 1, 'disk' => 'local', 'file_path' => null,
            'original_name' => 'builder.docx', 'file_extension' => 'docx', 'status' => TemplateStatus::DRAFT,
        ]);
        $version->builderDocument()->create(['schema' => ['blocks' => [
            ['type' => 'heading', 'props' => ['text' => 'Lớp {{class.name}}']],
        ]]]);
        app(TemplateActivationService::class)->activate($version);
        $output = app(TemplateRenderService::class)->renderActiveWithMockData('lhl.training_plan', OutputFormat::WORD);
        $this->temporaryFiles[] = $output;
        $this->assertFileExists($output);
        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($output));
        $this->assertStringContainsString('Lớp Y54', $zip->getFromName('word/document.xml'));
        $zip->close();
    }

    public function test_active_word_engine_renders_split_placeholder_and_repeating_rows(): void
    {
        Storage::fake('local');
        $source = $this->wordSource();
        $version = $this->version(
            OutputFormat::WORD,
            'runtime-word',
            'runtime.docx',
            $source,
            $this->wordManifest()
        );
        $binding = app(TemplateBindingService::class);
        $binding->bind(
            $version,
            'word:placeholder:document:p0:0',
            'class.name',
            null,
            [],
            null,
            ['bold' => true, 'align' => 'center']
        );
        $binding->bind($version, 'word:bookmark:document:class_code', 'class.code');
        $binding->bind(
            $version,
            'word:sdt:document:management_unit:0',
            'class.management_unit'
        );
        $binding->bind($version, 'word:table:document:0', 'schedule_groups');
        $binding->bind(
            $version,
            'word:cell:document:t0:r1:c0',
            'schedule_groups[].period_label'
        );
        $binding->bind(
            $version,
            'word:cell:document:t0:r1:c1',
            'schedule_groups[].subject_short_name'
        );
        app(TemplateActivationService::class)->activate($version);

        $output = app(TemplateRenderService::class)->renderActiveWithMockData(
            'lhl.training_plan',
            OutputFormat::WORD
        );
        $this->temporaryFiles[] = $output;
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($output));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringNotContainsString('{{class.name}}', $xml);
        $this->assertStringContainsString('Y54', $xml);
        $this->assertStringContainsString('Đại đội 4/Tiểu đoàn 2', $xml);
        $this->assertStringContainsString('1-3', $xml);
        $this->assertStringContainsString('GPSL', $xml);
        $this->assertStringContainsString('6-9', $xml);

        $dom = new \DOMDocument;
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace(
            'w',
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
        );
        $this->assertSame(4, $xpath->query('//w:tbl[1]/w:tr')->length);
        $this->assertSame(
            'center',
            $xpath->query('//w:p[1]/w:pPr/w:jc')->item(0)
                ?->getAttributeNS(
                    'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                    'val'
                )
        );
    }

    private function version(
        OutputFormat $format,
        string $code,
        string $fileName,
        string $source,
        array $manifest
    ): ExportTemplateVersion {
        $path = 'export-templates/tests/'.$fileName;
        Storage::disk('local')->put($path, file_get_contents($source));
        $template = ExportTemplate::query()->create([
            'code' => $code,
            'name' => 'Runtime '.$format->label(),
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan',
            'output_format' => $format,
            'file_path' => $path,
            'disk' => 'local',
            'mime' => 'application/octet-stream',
            'original_name' => $fileName,
            'status' => TemplateStatus::DRAFT,
            'is_active' => false,
        ]);

        return $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => $fileName,
            'mime' => 'application/octet-stream',
            'file_extension' => pathinfo($fileName, PATHINFO_EXTENSION),
            'status' => TemplateStatus::DRAFT,
            'manifest' => $manifest,
        ]);
    }

    private function excelSource(): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('LHL');
        $sheet->setCellValue('A1', '{{class.name}}');
        $sheet->fromArray([
            ['Tiết', 'Môn'],
            ['{{period}}', '{{subject}}'],
        ], null, 'A2');
        $sheet->addTable(new Table('A2:B3', 'ScheduleRows'));
        $path = $this->temporaryPath('xlsx');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    private function wordSource(): string
    {
        $path = $this->temporaryPath('docx');
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
 <w:body>
  <w:p><w:r><w:t>{{class</w:t></w:r><w:r><w:t>.name}}</w:t></w:r></w:p>
  <w:p><w:bookmarkStart w:id="1" w:name="class_code"/><w:r><w:t>OLD-CODE</w:t></w:r><w:bookmarkEnd w:id="1"/></w:p>
  <w:sdt><w:sdtPr><w:tag w:val="management_unit"/></w:sdtPr><w:sdtContent><w:p><w:r><w:t>OLD-UNIT</w:t></w:r></w:p></w:sdtContent></w:sdt>
  <w:tbl>
   <w:tr><w:tc><w:p><w:r><w:t>Tiết</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Môn</w:t></w:r></w:p></w:tc></w:tr>
   <w:tr><w:tc><w:p><w:r><w:t>{{period}}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{{subject}}</w:t></w:r></w:p></w:tc></w:tr>
  </w:tbl>
 </w:body>
</w:document>
XML);
        $zip->close();

        return $path;
    }

    private function excelManifest(): array
    {
        return [
            'document' => ['format' => 'excel'],
            'targets' => [
                ['ref' => 'excel:cell:LHL!A1', 'kind' => 'cell', 'sheet' => 'LHL', 'address' => 'A1'],
                [
                    'ref' => 'excel:table:LHL:ScheduleRows',
                    'kind' => 'table',
                    'sheet' => 'LHL',
                    'name' => 'ScheduleRows',
                    'range' => 'A2:B3',
                    'show_header' => true,
                ],
                ['ref' => 'excel:cell:LHL!A3', 'kind' => 'cell', 'sheet' => 'LHL', 'address' => 'A3'],
                ['ref' => 'excel:cell:LHL!B3', 'kind' => 'cell', 'sheet' => 'LHL', 'address' => 'B3'],
            ],
        ];
    }

    private function wordManifest(): array
    {
        return [
            'document' => ['format' => 'word'],
            'targets' => [
                [
                    'ref' => 'word:placeholder:document:p0:0',
                    'kind' => 'placeholder',
                    'part' => 'word/document.xml',
                    'paragraph_index' => 0,
                    'data_key' => 'class.name',
                ],
                [
                    'ref' => 'word:table:document:0',
                    'kind' => 'table',
                    'part' => 'word/document.xml',
                    'table_index' => 0,
                ],
                [
                    'ref' => 'word:bookmark:document:class_code',
                    'kind' => 'bookmark',
                    'part' => 'word/document.xml',
                    'name' => 'class_code',
                ],
                [
                    'ref' => 'word:sdt:document:management_unit:0',
                    'kind' => 'content_control',
                    'part' => 'word/document.xml',
                    'tag' => 'management_unit',
                ],
                [
                    'ref' => 'word:cell:document:t0:r1:c0',
                    'kind' => 'table_cell',
                    'part' => 'word/document.xml',
                    'table_index' => 0,
                    'row_index' => 1,
                    'cell_index' => 0,
                ],
                [
                    'ref' => 'word:cell:document:t0:r1:c1',
                    'kind' => 'table_cell',
                    'part' => 'word/document.xml',
                    'table_index' => 0,
                    'row_index' => 1,
                    'cell_index' => 1,
                ],
            ],
        ];
    }

    private function temporaryPath(string $extension): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .uniqid('template-engine-', true).'.'.$extension;
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
