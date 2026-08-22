<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'lms_assignments';

    protected $fillable = [
        'lms_course_id', 'lms_lesson_id', 'title', 'description', 'due_at', 'max_score',
        'allow_late', 'is_published', 'created_by',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'max_score' => 'float',
        'allow_late' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lms_lesson_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LmsAssignmentSubmission::class, 'lms_assignment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        if (! $this->is_published) {
            return false;
        }
        if (! $this->due_at) {
            return true;
        }

        return $this->allow_late || now()->lte($this->due_at);
    }
}
