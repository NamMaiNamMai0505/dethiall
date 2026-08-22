<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class HourExchangeRecord extends Model
{
    use HasStandardHoursPeriod, SoftDeletes;

    public const DIRECTION_NCKH_TO_CM = 'nckh_to_cm';

    public const DIRECTION_CM_TO_NCKH = 'cm_to_nckh';

    protected $table = 'hour_exchange_records';

    protected $fillable = [
        'instructor_id',
        'year',
        'period_mode',
        'direction',
        'source_hours',
        'target_hours',
        'rate',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'source_hours' => 'decimal:2',
        'target_hours' => 'decimal:2',
        'rate' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByInstructor($query, $instructorId)
    {
        if (blank($instructorId)) {
            return $query;
        }

        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByYear($query, ?string $year)
    {
        if (blank($year)) {
            return $query;
        }

        return $query->where('year', $year);
    }

    public function getDirectionTextAttribute(): string
    {
        return match ($this->direction) {
            self::DIRECTION_NCKH_TO_CM => 'NCKH → HĐ CM',
            self::DIRECTION_CM_TO_NCKH => 'HĐ CM → NCKH',
            default => 'Không xác định',
        };
    }

    public static function getDirectionOptions(): array
    {
        return [
            self::DIRECTION_NCKH_TO_CM => 'NCKH → HĐ CM',
            self::DIRECTION_CM_TO_NCKH => 'HĐ CM → NCKH',
        ];
    }
}
