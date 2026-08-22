<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsScormAttempt extends Model
{
    protected $table = 'lms_scorm_attempts';

    protected $fillable = [
        'lms_scorm_package_id', 'lms_course_id', 'user_id',
        'lesson_status', 'success_status',
        'score_raw', 'score_max', 'score_min', 'score_scaled',
        'session_time_sec', 'total_time_sec',
        'suspend_data', 'lesson_location', 'cmi_data',
        'started_at', 'completed_at', 'last_commit_at',
    ];

    protected $casts = [
        'score_raw' => 'float',
        'score_max' => 'float',
        'score_min' => 'float',
        'score_scaled' => 'float',
        'cmi_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_commit_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(LmsScormPackage::class, 'lms_scorm_package_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        $s = strtolower((string) $this->lesson_status);

        return in_array($s, ['completed', 'passed'], true)
            || strtolower((string) $this->success_status) === 'passed';
    }
}
