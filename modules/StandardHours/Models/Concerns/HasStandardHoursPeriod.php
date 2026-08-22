<?php

namespace Modules\StandardHours\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\StandardHours\Services\PeriodService;

trait HasStandardHoursPeriod
{
    public function scopeCurrentPeriod(Builder $query): Builder
    {
        return $query->where(
            $this->qualifyColumn('period_mode'),
            app(PeriodService::class)->mode()
        );
    }

    public function scopeForPeriod(Builder $query, int|string $year, ?string $mode = null): Builder
    {
        return $query
            ->where($this->qualifyColumn('year'), (int) $year)
            ->where(
                $this->qualifyColumn('period_mode'),
                $mode ?? app(PeriodService::class)->mode()
            );
    }

    public function getPeriodLabelAttribute(): string
    {
        return app(PeriodService::class)->label(
            (int) $this->getAttribute('year'),
            $this->getAttribute('period_mode') ?: PeriodService::MODE_CALENDAR_YEAR
        );
    }
}
