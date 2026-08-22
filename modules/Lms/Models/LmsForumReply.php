<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsForumReply extends Model
{
    use SoftDeletes;

    protected $table = 'lms_forum_replies';

    protected $fillable = [
        'lms_forum_topic_id',
        'user_id',
        'body',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LmsForumTopic::class, 'lms_forum_topic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
