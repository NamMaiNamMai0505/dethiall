<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Modules\ExportTemplates\Services\BuilderTemplateSchema;
use Tests\TestCase;

class BuilderTemplateSchemaTest extends TestCase
{
    public function test_schema_is_normalized_for_builder_documents(): void
    {
        $schema = BuilderTemplateSchema::normalize([
            'blocks' => [['type' => 'text', 'props' => ['text' => 'Y54']]],
        ], 'word');

        $this->assertSame('1.0', $schema['schema_version']);
        $this->assertSame('word', $schema['format']);
        $this->assertNotEmpty($schema['blocks'][0]['id']);
        $this->assertSame('header_pair', $schema['blocks'][0]['type']);
        $this->assertSame('text', $schema['blocks'][1]['type']);
        $this->assertStringContainsString(
            'TỔNG CỤC HẬU CẦN - KỸ THUẬT',
            $schema['blocks'][0]['props']['left_text']
        );
        $this->assertStringContainsString(
            'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM',
            $schema['blocks'][0]['props']['right_text']
        );
    }

    public function test_invalid_blocks_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BuilderTemplateSchema::validate(['blocks' => [['props' => []]]]);
    }
}
