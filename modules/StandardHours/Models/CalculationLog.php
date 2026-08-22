<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class CalculationLog extends Model
{
    use HasStandardHoursPeriod;

    public const ACTION_PREVIEW = 'preview';

    public const ACTION_CALCULATE = 'calculate';

    public const ACTION_ROLLBACK = 'rollback';

    public const ACTION_LOCK = 'lock';

    public $timestamps = false;

    protected $table = 'calculation_logs';

    protected $fillable = [
        'year',
        'period_mode',
        'action',
        'instructors_processed',
        'instructors_skipped',
        'notes',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'instructors_processed' => 'integer',
        'instructors_skipped' => 'integer',
        'created_at' => 'datetime',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionTextAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_PREVIEW => 'Xem trước',
            self::ACTION_CALCULATE => 'Tính giờ',
            self::ACTION_ROLLBACK => 'Hoàn tác',
            self::ACTION_LOCK => 'Khóa dữ liệu',
            default => 'Không xác định',
        };
    }
}
