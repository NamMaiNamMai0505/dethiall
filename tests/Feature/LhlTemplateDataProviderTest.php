<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Data\LhlTemplateDataProvider;
use Modules\ExportTemplates\Services\TemplateDataExplorer;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SpecialSubjectCatalog;
use Tests\TestCase;

class LhlTemplateDataProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_data_has_same_schema_as_mock_and_groups_consecutive_periods(): void
    {
        $first = ScheduleDetail::factory()->create([
            'date' => '2026-03-02',
            'period' => 1,
            'lesson_type' => 'theory',
        ]);
        ScheduleDetail::query()->create([
            'training_schedule_id' => $first->training_schedule_id,
            'date' => $first->date,
            'period' => 2,
            'subject_id' => $first->subject_id,
            'subject_lesson_id' => $first->subject_lesson_id,
            'instructor_id' => $first->instructor_id,
            'classroom_id' => $first->classroom_id,
            'lesson_type' => $first->lesson_type,
        ]);

        $provider = app(LhlTemplateDataProvider::class);
        $data = $provider->load([
            'training_schedule_id' => $first->training_schedule_id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-08',
            'generated_by' => 'Kiểm thử',
        ]);

        $this->assertSame(
            [],
            app(TemplateDataExplorer::class)->validateDataShape($provider->schema(), $data)
        );
        $this->assertCount(1, $data['schedule_groups']);
        $this->assertSame('1-2', $data['schedule_groups'][0]['period_label']);
        $this->assertCount(5, $data['schedule_days']);
        $this->assertSame('1-2', $data['schedule_days'][0]['group_1']['period_label']);
        $this->assertSame(
            $data['schedule_groups'][0]['subject_short_name'],
            $data['schedule_days'][0]['period_1']['subject_short_name']
        );
        $this->assertSame(
            $data['schedule_groups'][0]['subject_short_name'],
            $data['schedule_days'][0]['period_2']['subject_short_name']
        );
        $this->assertFalse($data['schedule_days'][0]['period_3']['exists']);
        $this->assertSame('1-3', $data['schedule_days'][0]['slot_1_3']['period_label']);
        $this->assertTrue($data['schedule_days'][0]['slot_1_3']['exists']);
        $this->assertSame(
            $data['schedule_groups'][0]['subject_short_name'],
            $data['schedule_days'][0]['slot_1_3']['subject_short_name']
        );
        $this->assertSame(2, $data['summary']['total_periods']);
        $this->assertSame('Kiểm thử', $data['generated']['by']);
    }

    public function test_real_data_requires_explicit_schedule_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LhlTemplateDataProvider::class)->load([]);
    }

    public function test_special_schedule_activities_are_seeded_globally(): void
    {
        $subjects = Subject::query()
            ->where('is_special_activity', true)
            ->orderBy('code')
            ->get();

        $this->assertCount(6, $subjects);
        $this->assertEqualsCanonicalizing(
            SpecialSubjectCatalog::codes(),
            $subjects->pluck('code')->all()
        );
        $this->assertTrue($subjects->every(
            fn (Subject $subject): bool => $subject->specialization_id === null
                && $subject->short_label === $subject->code
        ));
    }

    public function test_week_nine_range_keeps_both_boundaries(): void
    {
        $provider = app(LhlTemplateDataProvider::class);
        $method = new \ReflectionMethod($provider, 'weeks');
        $weeks = $method->invoke(
            $provider,
            CarbonImmutable::parse('2026-03-17'),
            CarbonImmutable::parse('2026-03-23')
        );

        $this->assertSame('17/03-23/03', $weeks[0]['date_range']);
        $this->assertSame('2026-03-17', $weeks[0]['start_date']);
        $this->assertSame('2026-03-23', $weeks[0]['end_date']);
    }
}
