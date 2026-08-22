<?php

namespace Modules\TrainingSchedule\Services;

use Illuminate\Support\Collection;
use Modules\ExportTemplates\Data\LhlScheduleGroupService;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class LhlPeriodLayoutSelector
{
    public const CLASSIC = 'classic';

    public const GROUPED_PERIODS = 'grouped_periods';

    public const CLASSIC_FEATURE_KEY = 'lhl.training_plan';

    public const GROUPED_FEATURE_KEY = 'lhl.training_plan.grouped_periods';

    public function __construct(
        private readonly LhlScheduleGroupService $groupService
    ) {}

    /**
     * @param  array<int|string>  $trainingScheduleIds
     */
    public function select(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate
    ): string {
        $ids = array_values(array_filter(array_map('intval', $trainingScheduleIds)));
        if ($ids === []) {
            return self::CLASSIC;
        }

        $details = ScheduleDetail::query()
            ->with([
                'subject',
                'subjectLesson',
                'instructor.unit',
                'instructor.position',
                'classroom.building',
            ])
            ->whereIn('training_schedule_id', $ids)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('subject_id')
            ->orderBy('training_schedule_id')
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        return $this->selectFromDetails($details);
    }

    /**
     * LHL dùng một mẫu cố định ba slot cho mọi lịch: 1-3, 4-5 và 6-9.
     *
     * @param  Collection<int, ScheduleDetail>  $details
     */
    public function selectFromDetails(Collection $details): string
    {
        // LHL hiện hành luôn có ba slot cố định 1-3, 4-5 và 6-9.
        // Môn phủ 1-5 được điền lặp vào hai slot sáng tương ứng.
        return self::GROUPED_PERIODS;
    }

    public function featureKey(string $layout): string
    {
        return $layout === self::GROUPED_PERIODS
            ? self::GROUPED_FEATURE_KEY
            : self::CLASSIC_FEATURE_KEY;
    }
}
