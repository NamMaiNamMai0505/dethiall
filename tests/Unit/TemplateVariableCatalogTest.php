<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Services\TemplateVariableCatalog;
use Tests\TestCase;

class TemplateVariableCatalogTest extends TestCase
{
    public function test_catalog_reads_provider_children_and_exposes_explanations(): void
    {
        $variables = app(TemplateVariableCatalog::class)->forFeature('lhl.training_plan');

        $this->assertNotEmpty($variables);
        $this->assertContains('class.name', array_column($variables, 'key'));
        $this->assertContains('schedule_groups[].subject_short_name', array_column($variables, 'key'));
        $this->assertContains('schedule_days[].group_2.subject_short_name', array_column($variables, 'key'));
        $this->assertContains('schedule_days[].period_4.subject_short_name', array_column($variables, 'key'));
        $this->assertContains('schedule_days[].slot_4_5.subject_short_name', array_column($variables, 'key'));

        $className = collect($variables)->firstWhere('key', 'class.name');
        $this->assertSame('Thông tin lớp', $className['group']);
        $this->assertNotEmpty($className['description']);
        $this->assertSame('Y54', $className['example']);

        $periodFour = collect($variables)->firstWhere('key', 'schedule_days[].period_4.subject_short_name');
        $this->assertSame('GPSL', $periodFour['example']);
        $this->assertStringContainsString('tiết 4 thực tế', $periodFour['description']);
        $slotFourFive = collect($variables)->firstWhere('key', 'schedule_days[].slot_4_5.subject_short_name');
        $this->assertSame('GPSL', $slotFourFive['example']);
    }

    public function test_grouped_lhl_feature_exposes_the_same_period_specific_placeholders(): void
    {
        $variables = app(TemplateVariableCatalog::class)
            ->forFeature('lhl.training_plan.grouped_periods');
        $keys = array_column($variables, 'key');

        $this->assertContains('schedule_days[].group_1.period_label', $keys);
        $this->assertContains('schedule_days[].group_2.subject_short_name', $keys);
        $this->assertContains('schedule_days[].period_1.subject_short_name', $keys);
        $this->assertContains('schedule_days[].period_9.subject_short_name', $keys);
        $this->assertContains('schedule_days[].slot_1_3.subject_short_name', $keys);
    }
}
