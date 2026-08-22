<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Instructor\Models\Instructor;

class LmsCourseInstructor extends Model
{
    public const ROLE_LEAD = 'lead';

    public const ROLE_LECTURER = 'lecturer';

    public const ROLE_ASSISTANT = 'assistant';

    protected $table = 'lms_course_instructors';

    protected $fillable = [
        'lms_course_id',
        'instructor_id',
        'role',
        'source',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }
}
