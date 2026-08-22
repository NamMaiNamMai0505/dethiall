<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Data\LhlTemplateDataProvider;
use Modules\ExportTemplates\Services\TemplateDataExplorer;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use Tests\TestCase;

class ExportTemplateDataExplorerTest extends TestCase
{
    public function test_lhl_provider_is_registered_with_allowlisted_schema(): void
    {
        $registry = app(TemplateDataRegistry::class);

        $this->assertTrue($registry->has('lhl.training_plan'));
        $this->assertInstanceOf(
            LhlTemplateDataProvider::class,
            $registry->get('lhl.training_plan')
        );
    }

    public function test_mock_data_matches_schema_and_contains_pdf_reference_values(): void
    {
        $provider = app(LhlTemplateDataProvider::class);
        $explorer = app(TemplateDataExplorer::class);
        $mock = $provider->mockData();

        $this->assertSame([], $explorer->validateDataShape($provider->schema(), $mock));
        $this->assertSame('Y54', $mock['class']['name']);
        $this->assertSame(60, $mock['class']['enrollment']);
        $this->assertSame('101/H3', $mock['class']['room']);
        $this->assertSame(['1-3', '4-5', '6-9'], array_column($mock['schedule_groups'], 'period_label'));
        $this->assertSame(
            ['TTT', 'GPSL', 'TTT'],
            array_column($mock['schedule_groups'], 'subject_short_name')
        );
        $this->assertSame('1-3', $mock['schedule_days'][0]['group_1']['period_label']);
        $this->assertSame('GPSL', $mock['schedule_days'][0]['group_2']['subject_short_name']);
        $this->assertSame('TTT', $mock['schedule_days'][0]['period_3']['subject_short_name']);
        $this->assertSame('GPSL', $mock['schedule_days'][0]['period_4']['subject_short_name']);
        $this->assertSame('TTT', $mock['schedule_days'][0]['period_9']['subject_short_name']);
        $this->assertSame('TTT', $mock['schedule_days'][0]['slot_1_3']['subject_short_name']);
        $this->assertSame('GPSL', $mock['schedule_days'][0]['slot_4_5']['subject_short_name']);
    }

    public function test_explorer_searches_labels_and_never_exposes_sensitive_paths(): void
    {
        $provider = app(LhlTemplateDataProvider::class);
        $explorer = app(TemplateDataExplorer::class);
        $fields = $explorer->fields($provider->schema());
        $keys = array_column($fields, 'key');

        $this->assertContains('class.name', $keys);
        $this->assertContains('schedule_groups', $keys);
        $this->assertContains('schedule_days[].group_1.period_label', $keys);
        $this->assertContains('schedule_days[].period_4.subject_short_name', $keys);
        $this->assertContains('schedule_days[].slot_4_5.subject_short_name', $keys);
        $this->assertContains('signers[].role_line1', $keys);
        $this->assertNotContains('instructor.email', $keys);
        $this->assertNotContains('user.password', $keys);
        $this->assertSame(
            ['class.name'],
            array_column($explorer->fields($provider->schema(), 'Tên lớp'), 'key')
        );
    }

    public function test_explorer_reads_collection_preview_value(): void
    {
        $provider = app(LhlTemplateDataProvider::class);
        $explorer = app(TemplateDataExplorer::class);

        $this->assertSame(
            'TTT',
            $explorer->value($provider->mockData(), 'schedule_groups[].subject_short_name')
        );
        $this->assertSame(
            'GPSL',
            $explorer->value($provider->mockData(), 'schedule_days[].period_4.subject_short_name')
        );
    }
}
