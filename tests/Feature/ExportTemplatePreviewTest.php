<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Data\LhlTemplateDataProvider;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Services\TemplateBindingService;
use Modules\ExportTemplates\Services\TemplatePreviewBuilder;
use Tests\TestCase;

class ExportTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_preview_applies_mock_binding_merge_and_style_override(): void
    {
        $version = $this->version(OutputFormat::EXCEL, $this->excelManifest());
        app(TemplateBindingService::class)->bind(
            $version,
            'excel:cell:LHL!A1',
            'class.name',
            null,
            [],
            null,
            ['bold' => true, 'align' => 'center', 'font_size' => 14]
        );

        $preview = app(TemplatePreviewBuilder::class)->build(
            $version,
            app(LhlTemplateDataProvider::class)
        );
        $cell = $preview['rows'][0]['cells'][0];

        $this->assertSame('excel', $preview['format']);
        $this->assertSame('Y54', $cell['value']);
        $this->assertSame(2, $cell['colspan']);
        $this->assertTrue($cell['bound']);
        $this->assertStringContainsString('font-weight:700', $cell['css']);
        $this->assertStringContainsString('text-align:center', $cell['css']);
    }

    public function test_word_preview_builds_page_parts_table_and_mock_placeholder(): void
    {
        $version = $this->version(OutputFormat::WORD, $this->wordManifest());
        app(TemplateBindingService::class)->bind(
            $version,
            'word:placeholder:document:p0:0',
            'class.name'
        );

        $preview = app(TemplatePreviewBuilder::class)->build(
            $version,
            app(LhlTemplateDataProvider::class)
        );

        $this->assertSame('word', $preview['format']);
        $this->assertSame('landscape', $preview['layout']['orientation']);
        $this->assertSame('Y54', $preview['parts'][0]['paragraphs'][0]['value']);
        $this->assertTrue($preview['parts'][0]['paragraphs'][0]['bound']);
        $this->assertSame('Nội dung', $preview['parts'][0]['tables'][0]['rows'][0]['cells'][0]['value']);
    }

    private function version(OutputFormat $format, array $manifest)
    {
        $extension = $format === OutputFormat::WORD ? 'docx' : 'xlsx';
        $code = 'preview-'.$format->value;
        $template = ExportTemplate::query()->create([
            'code' => $code,
            'name' => 'Preview LHL',
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan',
            'output_format' => $format,
            'file_path' => "export-templates/{$code}.{$extension}",
            'disk' => 'local',
            'mime' => 'application/octet-stream',
            'original_name' => "{$code}.{$extension}",
            'status' => TemplateStatus::DRAFT,
            'is_active' => false,
        ]);

        return $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => $template->file_path,
            'original_name' => $template->original_name,
            'mime' => $template->mime,
            'file_extension' => $extension,
            'status' => TemplateStatus::DRAFT,
            'manifest' => $manifest,
        ]);
    }

    private function excelManifest(): array
    {
        return [
            'document' => [
                'format' => 'excel',
                'sheets' => [[
                    'name' => 'LHL',
                    'dimension' => 'A1:B2',
                    'merged_ranges' => ['A1:B1'],
                    'row_dimensions' => [['row' => 1, 'height' => 30]],
                    'column_dimensions' => [
                        ['column' => 'A', 'width' => 24],
                        ['column' => 'B', 'width' => 12],
                    ],
                    'page_setup' => ['orientation' => 'landscape'],
                ]],
            ],
            'targets' => [[
                'ref' => 'excel:cell:LHL!A1',
                'kind' => 'cell',
                'sheet' => 'LHL',
                'address' => 'A1',
            ]],
            'elements' => [[
                'ref' => 'excel:cell:LHL!A1',
                'kind' => 'cell',
                'sheet' => 'LHL',
                'address' => 'A1',
                'value' => '{{class.name}}',
                'style' => [
                    'font' => ['name' => 'Arial', 'size' => 11, 'bold' => false, 'italic' => false],
                    'alignment' => ['horizontal' => 'left'],
                ],
            ]],
        ];
    }

    private function wordManifest(): array
    {
        return [
            'document' => [
                'format' => 'word',
                'layout' => ['orientation' => 'landscape'],
                'parts' => [[
                    'name' => 'word/document.xml',
                    'type' => 'document',
                ]],
            ],
            'targets' => [
                [
                    'ref' => 'word:placeholder:document:p0:0',
                    'kind' => 'placeholder',
                    'part' => 'word/document.xml',
                    'part_type' => 'document',
                    'paragraph_index' => 0,
                    'data_key' => 'class.name',
                ],
                [
                    'ref' => 'word:cell:document:t0:r0:c0',
                    'kind' => 'table_cell',
                    'part' => 'word/document.xml',
                ],
            ],
            'elements' => [
                [
                    'ref' => 'word:paragraph:document:0',
                    'kind' => 'paragraph',
                    'part' => 'word/document.xml',
                    'index' => 0,
                    'text' => '{{class.name}}',
                    'style' => ['alignment' => 'center'],
                ],
                [
                    'ref' => 'word:table:document:0',
                    'kind' => 'table',
                    'part' => 'word/document.xml',
                    'rows' => [[
                        'height' => 400,
                        'cells' => [[
                            'ref' => 'word:cell:document:t0:r0:c0',
                            'text' => 'Nội dung',
                            'style' => ['grid_span' => 1],
                        ]],
                    ]],
                ],
            ],
        ];
    }
}
