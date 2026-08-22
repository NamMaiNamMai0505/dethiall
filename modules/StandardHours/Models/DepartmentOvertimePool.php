<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class DepartmentOvertimePool extends Model
{
    use HasStandardHoursPeriod;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_LOCKED = 'locked';

    protected $table = 'department_overtime_pools';

    protected $fillable = [
        'department_id', 'year', 'period_mode',
        'pool_must_hours', 'pool_done_hours', 'pool_excess_hours', 'member_count',
        'member_snapshot', 'status', 'calculated_by', 'calculated_at',
        'locked_by', 'locked_at', 'note',
    ];

    protected $casts = [
        'year' => 'integer',
        'pool_must_hours' => 'float',
        'pool_done_hours' => 'float',
        'pool_excess_hours' => 'float',
        'member_snapshot' => 'array',
        'calculated_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(AcademicDepartment::class, 'department_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DepartmentOvertimeAllocation::class, 'pool_id');
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function allocatedTotal(): float
    {
        return round((float) $this->allocations()->sum('allocated_hours'), 2);
    }
}
