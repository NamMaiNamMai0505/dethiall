<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;
use Modules\StandardHours\Services\PeriodService;

/**
 * Điều 11.3 TT 06/2026 — giảm trừ định mức GC theo thời gian nghỉ/đột xuất.
 */
class InstructorNormReduction extends Model
{
    use HasStandardHoursPeriod;

    public const TYPE_SPECIAL_DUTY = 'special_duty';

    public const TYPE_SICK_LEAVE = 'sick_leave';

    public const TYPE_MATERNITY = 'maternity';

    public const TYPE_OTHER = 'other';

    protected $table = 'instructor_norm_reductions';

    protected $fillable = [
        'instructor_id', 'year', 'period_mode', 'type', 'title', 'note',
        'start_date', 'end_date', 'days', 'reduction_percent',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'reduction_percent' => 'float',
        'is_active' => 'boolean',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_SPECIAL_DUTY => 'Nhiệm vụ đột xuất / điều động',
            self::TYPE_SICK_LEAVE => 'Nghỉ chữa bệnh',
            self::TYPE_MATERNITY => 'Nghỉ thai sản',
            self::TYPE_OTHER => 'Khác (theo quy định)',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, string $year)
    {
        return $query->where('year', $year);
    }

    /**
     * % giảm trừ (0–100). Ưu tiên reduction_percent; nếu không thì lấy số
     * ngày giảm chia đúng tổng số ngày của kỳ Năm/Năm học đang lưu trên bản ghi.
     */
    public function resolvedPercent(): float
    {
        if ($this->reduction_percent !== null) {
            return max(0, min(100, (float) $this->reduction_percent));
        }
        $days = max(0, (int) $this->days);
        if ($days <= 0 && $this->start_date && $this->end_date) {
            $days = max(0, $this->start_date->diffInDays($this->end_date) + 1);
        }

        [$periodStart, $periodEnd] = app(PeriodService::class)
            ->dateRange($this->year, $this->period_mode);
        $periodDays = max(
            1,
            Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) + 1
        );

        return max(0, min(100, round(($days / $periodDays) * 100, 2)));
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }
}
