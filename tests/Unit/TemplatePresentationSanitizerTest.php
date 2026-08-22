<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Exceptions\InvalidTemplateBindingException;
use Modules\ExportTemplates\Services\TemplatePresentationSanitizer;
use PHPUnit\Framework\TestCase;

class TemplatePresentationSanitizerTest extends TestCase
{
    public function test_it_allowlists_and_normalizes_presentation_values(): void
    {
        $result = (new TemplatePresentationSanitizer)->sanitize(
            [
                'font_name' => 'Times New Roman',
                'font_size' => '13.5',
                'bold' => '1',
                'align' => 'center',
                'border_color' => '#abcdef',
                'unknown_css' => 'position:fixed',
            ],
            [
                'row_height' => '30',
                'cell_action' => 'merge',
                'merge_range' => 'A1:B2',
                'unknown_option' => true,
            ],
            'cell'
        );

        $this->assertSame(13.5, $result['style']['font_size']);
        $this->assertTrue($result['style']['bold']);
        $this->assertSame('#ABCDEF', $result['style']['border_color']);
        $this->assertArrayNotHasKey('unknown_css', $result['style']);
        $this->assertSame('A1:B2', $result['options']['merge_range']);
        $this->assertArrayNotHasKey('unknown_option', $result['options']);
    }

    public function test_it_rejects_invalid_or_incompatible_cell_changes(): void
    {
        try {
            (new TemplatePresentationSanitizer)->sanitize(
                ['font_size' => 100, 'border_color' => 'red'],
                ['cell_action' => 'merge', 'merge_range' => 'not-a-range'],
                'bookmark'
            );
            $this->fail('Presentation không hợp lệ phải bị từ chối.');
        } catch (InvalidTemplateBindingException $exception) {
            $message = implode(' ', $exception->errors());
            $this->assertStringContainsString('font_size', $message);
            $this->assertStringContainsString('border_color', $message);
            $this->assertStringContainsString('Merge/Split', $message);
        }
    }
}
