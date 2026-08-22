<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsForumTopic extends Model
{
    use SoftDeletes;

    protected $table = 'lms_forum_topics';

    protected $fillable = [
        'lms_course_id',
        'user_id',
        'title',
        'body',
        'is_pinned',
        'is_locked',
        'replies_count',
        'last_reply_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'last_reply_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(LmsForumReply::class, 'lms_forum_topic_id')->orderBy('id');
    }
}
