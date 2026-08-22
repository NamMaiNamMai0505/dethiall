<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Models\ExportTemplateBuilderDocument;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\Engines\ExcelTemplateEngine;
use Modules\ExportTemplates\Services\Engines\WordTemplateEngine;
use Modules\ExportTemplates\Services\TemplateValueFormatter;
use Modules\ExportTemplates\Services\TemplateValueResolver;
use Tests\TestCase;

class BuilderTemplateRenderTest extends TestCase
{
    public function test_builder_schema_renders_excel_and_word_without_source_file(): void
    {
        $version = new ExportTemplateVersion();
        $version->setRelation('builderDocument', new ExportTemplateBuilderDocument([
            'schema' => [
                'page' => ['orientation' => 'landscape'],
                'blocks' => [
                    ['type' => 'heading', 'props' => ['text' => 'Lớp {{class.name}}']],
                    ['type' => 'table', 'props' => ['rows' => 2, 'columns' => 2, 'collection_key' => 'items[]', 'header_0' => 'Môn', 'header_1' => 'Điểm', 'column_bindings' => ['items[].name', 'items[].score']]],
                ],
            ],
        ]));
        $data = ['class' => ['name' => 'Y54'], 'items' => [['name' => 'TTT', 'score' => 9]]];
        $resolver = new TemplateValueResolver();
        $formatter = new TemplateValueFormatter();

        $excel = app(ExcelTemplateEngine::class)->render($version, $data);
        $word = app(WordTemplateEngine::class)->render($version, $data);
        $this->assertFileExists($excel);
        $this->assertFileExists($word);
        @unlink($excel);
        @unlink($word);
    }
}
