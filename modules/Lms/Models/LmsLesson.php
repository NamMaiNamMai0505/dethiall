<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Subject\Models\SubjectLesson;

class LmsLesson extends Model
{
    use SoftDeletes;

    protected $table = 'lms_lessons';

    protected $fillable = [
        'lms_course_id',
        'subject_lesson_id',
        'content_type',
        'source_snapshot',
        'source_synced_at',
        'title',
        'summary',
        'content',
        'sort_order',
        'week_number',
        'is_published',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
        'week_number' => 'integer',
        'source_snapshot' => 'array',
        'source_synced_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function subjectLesson(): BelongsTo
    {
        return $this->belongsTo(SubjectLesson::class, 'subject_lesson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
