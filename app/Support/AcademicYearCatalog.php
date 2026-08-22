<?php

namespace App\Support;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\Schema;

class AcademicYearCatalog
{
    public static function options(bool $activeOnly = true): array
    {
        if (! Schema::hasTable('academic_years')) {
            return self::fallbackOptions();
        }

        return AcademicYear::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderByDesc('start_year')
            ->get()
            ->mapWithKeys(fn (AcademicYear $year) => [$year->code => $year->code])
            ->all();
    }

    public static function currentCode(): string
    {
        if (Schema::hasTable('academic_years')) {
            $current = AcademicYear::query()
                ->where('is_current', true)
                ->where('is_active', true)
                ->value('code');
            if ($current) {
                return $current;
            }
        }

        $currentStart = now()->month >= 8 ? now()->year : now()->year - 1;

        return $currentStart.'-'.($currentStart + 1);
    }

    private static function fallbackOptions(): array
    {
        $currentStart = now()->month >= 8 ? now()->year : now()->year - 1;
        $options = [];
        for ($start = $currentStart + 3; $start >= $currentStart - 1; $start--) {
            $code = $start.'-'.($start + 1);
            $options[$code] = $code;
        }

        return $options;
    }
}
