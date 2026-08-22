<?php

namespace Modules\Grades\Support;

use App\Support\SystemSettings;

final class GradeSettings
{
    public static function maxScore(): float
    {
        return max(1, min(100, (float) SystemSettings::get('grades', 'max_score', 10)));
    }

    public static function passScore(): float
    {
        return max(0, min(self::maxScore(), (float) SystemSettings::get('grades', 'pass_score', 5)));
    }

    public static function excellentScore(): float
    {
        return max(self::passScore(), min(self::maxScore(), (float) SystemSettings::get('grades', 'excellent_score', 8)));
    }

    public static function decimalPlaces(): int
    {
        return max(0, min(2, (int) SystemSettings::get('grades', 'decimal_places', 1)));
    }

    public static function roundingMode(): string
    {
        $value = (string) SystemSettings::get('grades', 'rounding_mode', 'half_up');

        return in_array($value, ['half_up', 'half_down', 'half_even'], true) ? $value : 'half_up';
    }

    public static function round(float $score): float
    {
        $mode = match (self::roundingMode()) {
            'half_down' => PHP_ROUND_HALF_DOWN,
            'half_even' => PHP_ROUND_HALF_EVEN,
            default => PHP_ROUND_HALF_UP,
        };

        return round($score, self::decimalPlaces(), $mode);
    }

    public static function format(float|int|string|null $score, string $empty = '—'): string
    {
        if ($score === null || $score === '' || ! is_numeric($score)) {
            return $empty;
        }

        return number_format(self::round((float) $score), self::decimalPlaces(), '.', '');
    }

    /** @return array<string, float> */
    public static function columnWeights(): array
    {
        return [
            'oral_15' => self::weight('weight_oral_15', 10),
            'period_1' => self::weight('weight_period_1', 20),
            'midterm' => self::weight('weight_midterm', 30),
            'final' => self::weight('weight_final', 40),
        ];
    }

    private static function weight(string $key, float $defaultPercent): float
    {
        return max(0, min(100, (float) SystemSettings::get('grades', $key, $defaultPercent))) / 100;
    }
}
