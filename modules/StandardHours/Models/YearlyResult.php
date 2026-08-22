<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;
use Modules\StandardHours\Services\PeriodService;

class YearlyResult extends Model
{
    use HasStandardHoursPeriod;

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_LOCKED = 'locked';

    public const DECLARATION_SUBMITTED = 'submitted';

    public const DECLARATION_APPROVED = 'approved';

    public const DECLARATION_REJECTED = 'rejected';

    protected $table = 'yearly_standard_results';

    protected $fillable = [
        'instructor_id',
        'year',
        'period_mode',
        'object_type_id',
        'position_id',
        'declaration_from_date',
        'declaration_to_date',
        'schedule_teaching_hours',
        'other_teaching_hours',
        'other_teaching_notes',
        'schedule_teaching_details',
        'schedule_retrieved_at',
        'teaching_hours',
        'conversion_hours',
        'research_hours',
        'total_standard_hours',
        'overtime_eligible_hours',
        'standard_norm_hours',
        'standard_difference',
        'min_classroom_hours',
        'meets_standard',
        'meets_classroom',
        'research_norm_hours',
        'research_difference',
        'meets_research',
        'meets_overall',
        'status',
        'calculated_by',
        'calculated_at',
        'declared_by',
        'declared_at',
        'declaration_status',
        'declaration_approved_by',
        'declaration_approved_at',
        'declaration_review_note',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'declaration_from_date' => 'date',
        'declaration_to_date' => 'date',
        'schedule_teaching_hours' => 'decimal:2',
        'other_teaching_hours' => 'decimal:2',
        'schedule_teaching_details' => 'array',
        'schedule_retrieved_at' => 'datetime',
        'teaching_hours' => 'decimal:2',
        'conversion_hours' => 'decimal:2',
        'research_hours' => 'decimal:2',
        'total_standard_hours' => 'decimal:2',
        'overtime_eligible_hours' => 'decimal:2',
        'standard_norm_hours' => 'decimal:2',
        'standard_difference' => 'decimal:2',
        'min_classroom_hours' => 'decimal:2',
        'meets_standard' => 'boolean',
        'meets_classroom' => 'boolean',
        'research_norm_hours' => 'decimal:2',
        'research_difference' => 'decimal:2',
        'meets_research' => 'boolean',
        'meets_overall' => 'boolean',
        'calculated_at' => 'datetime',
        'declared_at' => 'datetime',
        'declaration_approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function objectType(): BelongsTo
    {
        return $this->belongsTo(ObjectType::class, 'object_type_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function declarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function declarationApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declaration_approved_by');
    }

    public function getDeclarationPeriodTextAttribute(): string
    {
        if (! $this->declaration_from_date || ! $this->declaration_to_date) {
            [$fromDate, $toDate] = app(PeriodService::class)
                ->dateRange($this->year, $this->period_mode);

            return Carbon::parse($fromDate)->format('d/m/Y')
                .' – '
                .Carbon::parse($toDate)->format('d/m/Y');
        }

        return $this->declaration_from_date->format('d/m/Y')
            .' – '
            .$this->declaration_to_date->format('d/m/Y');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function scopeByYear($query, ?string $year)
    {
        if (blank($year)) {
            return $query;
        }

        return $query->where('year', $year);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        if (blank($instructorId)) {
            return $query;
        }

        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByUnit($query, $unitId)
    {
        if (blank($unitId)) {
            return $query;
        }

        return $query->whereHas('instructor', fn ($q) => $q->where('unit_id', $unitId));
    }

    public function scopeByStatus($query, ?string $status)
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeByOverall($query, ?string $result)
    {
        if (blank($result)) {
            return $query;
        }

        if ($result === 'pass') {
            return $query->where('meets_overall', true);
        }

        if ($result === 'fail') {
            return $query->where('meets_overall', false);
        }

        return $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->whereHas('instructor', function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CALCULATED => 'Đã tính',
            self::STATUS_LOCKED => 'Đã khóa',
            default => 'Không xác định',
        };
    }

    public function getOverallResultTextAttribute(): string
    {
        return $this->meets_overall ? 'Đạt' : 'Không đạt';
    }

    public function getDeclarationStatusTextAttribute(): string
    {
        return match ($this->declaration_status) {
            self::DECLARATION_SUBMITTED => 'Chờ duyệt',
            self::DECLARATION_APPROVED => 'Đã duyệt',
            self::DECLARATION_REJECTED => 'Từ chối',
            default => 'Chưa kê khai',
        };
    }
}
