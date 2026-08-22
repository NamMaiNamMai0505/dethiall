<?php

namespace Modules\TrainingSchedule\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Workbook lịch huấn luyện — mỗi sheet một lớp (mẫu HK2 25-26).
 */
class TrainingPlanWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  list<TrainingPlanExport>  $sheets
     */
    public function __construct(
        protected array $sheets
    ) {}

    public function sheets(): array
    {
        if ($this->sheets !== []) {
            return $this->sheets;
        }

        return [new TrainingPlanExport(
            'LHL',
            '—',
            '',
            '',
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay(),
            collect(),
            collect(),
            [],
        )];
    }
}
