<?php

namespace Modules\StandardHours\Support;

use Carbon\Carbon;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Services\PeriodService;

class YearlyResultFormatter
{
    public static function yearDateRange(string $year, ?string $fromDate = null, ?string $toDate = null): array
    {
        if ($fromDate && $toDate) {
            return [
                'from' => Carbon::parse($fromDate)->format('d/m/Y'),
                'to' => Carbon::parse($toDate)->format('d/m/Y'),
            ];
        }

        try {
            [$periodStart, $periodEnd] = app(PeriodService::class)
                ->dateRange($year);
        } catch (\Throwable) {
            return ['from' => '—', 'to' => '—'];
        }

        return [
            'from' => Carbon::parse($periodStart)->format('d/m/Y'),
            'to' => Carbon::parse($periodEnd)->format('d/m/Y'),
        ];
    }

    public static function formatDifference(float $value): string
    {
        $rounded = (int) round($value);

        if ($rounded > 0) {
            return '+'.$rounded;
        }

        if ($rounded < 0) {
            return (string) $rounded;
        }

        return '0';
    }

    public static function formatCapBac(?string $objectTypeName): string
    {
        if (blank($objectTypeName)) {
            return '';
        }

        if (preg_match('/(\d+)/', $objectTypeName, $matches)) {
            return ((int) $matches[1]).'/';
        }

        return $objectTypeName;
    }

    public static function formatUnitCode(?string $code, ?string $name): string
    {
        return $code ?: ($name ?? '');
    }

    public static function buildNotes(YearlyResult $result): string
    {
        $notes = [];

        if (! $result->meets_standard) {
            $notes[] = 'Không đạt giờ chuẩn';
        }

        if (! $result->meets_classroom) {
            $notes[] = 'Không đạt giờ chuẩn đúng lớp';
        }

        if (! $result->meets_research) {
            $notes[] = 'Không đạt giờ NCKH';
        }

        return implode('; ', $notes);
    }

    public static function classroomDifference(YearlyResult $result): float
    {
        return (float) $result->teaching_hours - (float) $result->min_classroom_hours;
    }

    /**
     * @return array<int, string|int|float>
     */
    public static function toStatisticsRow(YearlyResult $result, int $index): array
    {
        $classroomDiff = self::classroomDifference($result);

        return [
            $index,
            $result->instructor->name ?? '',
            self::formatCapBac($result->objectType->name ?? null),
            $result->position->name ?? '',
            self::formatUnitCode(
                $result->instructor->unit->code ?? null,
                $result->instructor->unit->name ?? null
            ),
            (int) round((float) $result->standard_norm_hours),
            (int) round((float) $result->total_standard_hours),
            self::formatDifference((float) $result->standard_difference),
            (int) round((float) $result->min_classroom_hours),
            (int) round((float) $result->teaching_hours),
            self::formatDifference($classroomDiff),
            (int) round((float) $result->conversion_hours),
            (int) round((float) $result->research_norm_hours),
            (int) round((float) $result->research_hours),
            self::formatDifference((float) $result->research_difference),
            self::buildNotes($result),
        ];
    }

    /**
     * Unit summary row aligned with personal statistics columns:
     * TT | Khoa | Số GV | (trống) | (trống) | GC ĐM/Có/SS | Lớp ĐM/Có/SS | QĐ | NCKH ĐM/Có/SS | Ghi chú
     *
     * @param  array<string, mixed>  $summary
     * @return array<int, string|int|float>
     */
    public static function toUnitSummaryRow(array $summary, int $index): array
    {
        $noteParts = [];
        if ((int) ($summary['passed'] ?? 0) > 0) {
            $noteParts[] = 'Đạt: '.$summary['passed'];
        }
        if ((int) ($summary['failed'] ?? 0) > 0) {
            $noteParts[] = 'Không đạt: '.$summary['failed'];
        }

        return [
            $index,
            $summary['unit_name'] ?? 'Chưa phân khoa',
            (int) ($summary['instructor_count'] ?? 0),
            '',
            self::formatUnitCode($summary['unit_code'] ?? null, $summary['unit_name'] ?? null),
            (int) round((float) ($summary['standard_norm_hours'] ?? 0)),
            (int) round((float) ($summary['total_standard_hours'] ?? 0)),
            self::formatDifference((float) ($summary['standard_difference'] ?? 0)),
            (int) round((float) ($summary['min_classroom_hours'] ?? 0)),
            (int) round((float) ($summary['teaching_hours'] ?? 0)),
            self::formatDifference((float) ($summary['classroom_difference'] ?? 0)),
            (int) round((float) ($summary['conversion_hours'] ?? 0)),
            (int) round((float) ($summary['research_norm_hours'] ?? 0)),
            (int) round((float) ($summary['research_hours'] ?? 0)),
            self::formatDifference((float) ($summary['research_difference'] ?? 0)),
            implode('; ', $noteParts),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<int, string|int|float>
     */
    public static function toSchoolTotalRow(array $summary): array
    {
        return self::toUnitSummaryRow([
            'unit_name' => 'TOÀN TRƯỜNG',
            'unit_code' => '',
            'instructor_count' => $summary['instructor_count'] ?? 0,
            'standard_norm_hours' => $summary['standard_norm_hours'] ?? 0,
            'total_standard_hours' => $summary['total_standard_hours'] ?? 0,
            'standard_difference' => $summary['standard_difference'] ?? 0,
            'min_classroom_hours' => $summary['min_classroom_hours'] ?? 0,
            'teaching_hours' => $summary['teaching_hours'] ?? 0,
            'classroom_difference' => $summary['classroom_difference'] ?? 0,
            'conversion_hours' => $summary['conversion_hours'] ?? 0,
            'research_norm_hours' => $summary['research_norm_hours'] ?? 0,
            'research_hours' => $summary['research_hours'] ?? 0,
            'research_difference' => $summary['research_difference'] ?? 0,
            'passed' => $summary['passed'] ?? 0,
            'failed' => $summary['failed'] ?? 0,
        ], 1);
    }
}
