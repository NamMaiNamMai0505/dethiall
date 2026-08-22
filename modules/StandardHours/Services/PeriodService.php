<?php

namespace Modules\StandardHours\Services;

use App\Models\AcademicYear;
use App\Support\AcademicYearCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\StandardHours\Models\StandardHoursSetting;

class PeriodService
{
    public const MODE_CALENDAR_YEAR = 'calendar_year';

    public const MODE_ACADEMIC_YEAR = 'academic_year';

    public const MODES = [
        self::MODE_CALENDAR_YEAR,
        self::MODE_ACADEMIC_YEAR,
    ];

    public function mode(): string
    {
        if (! Schema::hasTable('standard_hours_settings')) {
            return self::MODE_CALENDAR_YEAR;
        }

        $value = StandardHoursSetting::query()
            ->where('key', StandardHoursSetting::KEY_PERIOD_MODE)
            ->value('value');
        $mode = is_array($value) ? ($value['mode'] ?? null) : null;

        return in_array($mode, self::MODES, true)
            ? $mode
            : self::MODE_CALENDAR_YEAR;
    }

    public function setMode(string $mode, ?int $userId = null): void
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException('Chế độ kỳ tính giờ chuẩn không hợp lệ.');
        }

        StandardHoursSetting::query()->updateOrCreate(
            ['key' => StandardHoursSetting::KEY_PERIOD_MODE],
            [
                'value' => ['mode' => $mode],
                'updated_by' => $userId ?? Auth::id(),
            ]
        );
    }

    public function isAcademicYear(): bool
    {
        return $this->mode() === self::MODE_ACADEMIC_YEAR;
    }

    public function modeLabel(?string $mode = null): string
    {
        return ($mode ?? $this->mode()) === self::MODE_ACADEMIC_YEAR
            ? 'Năm học'
            : 'Năm';
    }

    /**
     * @return array<int, string>
     */
    public function options(): array
    {
        if ($this->isAcademicYear()) {
            $resolved = [];
            foreach (AcademicYearCatalog::options() as $code => $label) {
                if (preg_match('/^(\d{4})-\d{4}$/', (string) $code, $matches)) {
                    $resolved[(int) $matches[1]] = (string) $label;
                }
            }

            if ($resolved !== []) {
                return $resolved;
            }
        }

        $currentYear = now()->year;
        $years = [];
        for ($year = $currentYear + 5; $year >= $currentYear - 2; $year--) {
            $years[$year] = (string) $year;
        }

        return $years;
    }

    public function currentYear(): int
    {
        if ($this->isAcademicYear()) {
            $code = AcademicYearCatalog::currentCode();
            if (preg_match('/^(\d{4})-\d{4}$/', $code, $matches)) {
                return (int) $matches[1];
            }
        }

        return now()->year;
    }

    public function label(int|string $year, ?string $mode = null): string
    {
        $startYear = (int) $year;
        $mode ??= $this->mode();

        return $mode === self::MODE_ACADEMIC_YEAR
            ? $startYear.'-'.($startYear + 1)
            : (string) $startYear;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function dateRange(int|string $year, ?string $mode = null): array
    {
        $startYear = (int) $year;
        $mode ??= $this->mode();
        $this->ensureValidYear($startYear);

        if ($mode === self::MODE_CALENDAR_YEAR) {
            return [
                Carbon::create($startYear, 1, 1)->toDateString(),
                Carbon::create($startYear, 12, 31)->toDateString(),
            ];
        }

        if (Schema::hasTable('academic_years')) {
            $academicYear = AcademicYear::query()
                ->where('start_year', $startYear)
                ->first();
            if ($academicYear) {
                return [
                    ($academicYear->starts_at ?: Carbon::create($startYear, 8, 1))->toDateString(),
                    ($academicYear->ends_at ?: Carbon::create($startYear + 1, 7, 31))->toDateString(),
                ];
            }
        }

        return [
            Carbon::create($startYear, 8, 1)->toDateString(),
            Carbon::create($startYear + 1, 7, 31)->toDateString(),
        ];
    }

    public function resolveYearForDate(string|\DateTimeInterface $date, ?string $mode = null): int
    {
        $value = Carbon::parse($date);
        $mode ??= $this->mode();

        if ($mode === self::MODE_CALENDAR_YEAR) {
            return $value->year;
        }

        if (Schema::hasTable('academic_years')) {
            $academicYear = AcademicYear::query()
                ->whereNotNull('starts_at')
                ->whereNotNull('ends_at')
                ->whereDate('starts_at', '<=', $value->toDateString())
                ->whereDate('ends_at', '>=', $value->toDateString())
                ->first();
            if ($academicYear) {
                return (int) $academicYear->start_year;
            }
        }

        return $value->month >= 8 ? $value->year : $value->year - 1;
    }

    public function rangeBelongsToPeriod(string $fromDate, string $toDate, ?string $mode = null): bool
    {
        return $this->resolveYearForDate($fromDate, $mode)
            === $this->resolveYearForDate($toDate, $mode);
    }

    public function isFullRange(int|string $year, ?string $fromDate, ?string $toDate, ?string $mode = null): bool
    {
        if (! $fromDate || ! $toDate) {
            return true;
        }

        [$periodStart, $periodEnd] = $this->dateRange($year, $mode);

        return $fromDate === $periodStart && $toDate === $periodEnd;
    }

    private function ensureValidYear(int $year): void
    {
        if ($year < 2000 || $year > 2200) {
            throw new \RuntimeException('Kỳ tính giờ chuẩn không hợp lệ.');
        }
    }
}
