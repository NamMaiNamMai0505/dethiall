<?php

namespace Tests\Unit;

use Modules\ExportTemplates\Data\GradeScoreSheetDataProvider;
use Modules\ExportTemplates\Services\TemplateDataRegistry;
use Tests\TestCase;

class GradeScoreSheetDataProviderTest extends TestCase
{
    public function test_grade_score_sheet_provider_exposes_safe_schema_and_mock_data(): void
    {
        $provider = app(GradeScoreSheetDataProvider::class);

        $this->assertSame('grades.score_sheet', $provider->featureKey());
        $this->assertSame('grades.score_sheet', $provider->schema()['feature_key']);
        $this->assertSame('Y54', $provider->mockData()['grade_book']['class']['code']);
        $this->assertCount(2, $provider->mockData()['students']);
    }

    public function test_grade_summary_and_transcript_providers_are_registered(): void
    {
        $registry = app(TemplateDataRegistry::class);

        $this->assertTrue($registry->has('grades.summary'));
        $this->assertTrue($registry->has('grades.transcript'));
        $this->assertSame('grades.summary', $registry->get('grades.summary')->featureKey());
        $this->assertSame('grades.transcript', $registry->get('grades.transcript')->featureKey());
    }
}
