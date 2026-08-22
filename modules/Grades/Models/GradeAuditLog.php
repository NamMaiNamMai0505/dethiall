<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeAuditLog extends Model
{
    protected $table = 'grade_audit_logs';

    protected $fillable = [
        'grade_book_id', 'user_id', 'action', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class, 'grade_book_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?int $bookId, string $action, array $meta = []): void
    {
        static::query()->create([
            'grade_book_id' => $bookId,
            'user_id' => auth()->id(),
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
