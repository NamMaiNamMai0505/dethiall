<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Services\TemplateValueFormatter;
use Modules\ExportTemplates\Services\TemplateValueResolver;
use PHPUnit\Framework\TestCase;

class TemplateValueResolverTest extends TestCase
{
    public function test_it_resolves_scalar_collection_and_collection_item_paths(): void
    {
        $data = [
            'class' => ['name' => 'Y54'],
            'rows' => [
                ['subject' => 'TTT'],
                ['subject' => 'GPSL'],
            ],
        ];
        $resolver = new TemplateValueResolver;

        $this->assertSame('Y54', $resolver->resolve($data, 'class.name'));
        $this->assertSame(['TTT', 'GPSL'], $resolver->resolve($data, 'rows[].subject'));
        $this->assertSame('GPSL', $resolver->resolve($data, 'rows[].subject', $data['rows'][1]));
        $this->assertCount(2, $resolver->collection($data, 'rows'));
    }

    public function test_formatter_supports_date_number_and_case_without_eval(): void
    {
        $formatter = new TemplateValueFormatter;

        $this->assertSame('Y54', $formatter->format('y54', 'uppercase'));
        $this->assertSame('02/03/2026', $formatter->format('2026-03-02', 'd/m/Y'));
        $this->assertSame('1.234,50', $formatter->format(1234.5, 'number:2'));
        $this->assertSame('raw', $formatter->format('raw', 'unknown formatter'));
    }
}
