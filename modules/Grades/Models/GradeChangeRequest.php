<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PDOT_OK = 'pdot_ok';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'grade_change_requests';

    protected $fillable = [
        'grade_book_id', 'requested_by', 'status', 'reason',
        'pdot_note', 'director_note', 'pdot_reviewed_by', 'pdot_reviewed_at',
        'director_reviewed_by', 'director_reviewed_at',
    ];

    protected $casts = [
        'pdot_reviewed_at' => 'datetime',
        'director_reviewed_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class, 'grade_book_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ PDOT',
            self::STATUS_PDOT_OK => 'PDOT đã chuyển CN',
            self::STATUS_APPROVED => 'Đã duyệt mở khóa',
            self::STATUS_REJECTED => 'Từ chối',
            default => $this->status,
        };
    }
}
