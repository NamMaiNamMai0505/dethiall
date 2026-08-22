<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LmsAssignmentSubmissionVersion extends Model
{
    protected $table = 'lms_assignment_submission_versions';

    protected $fillable = [
        'lms_assignment_submission_id', 'version_no', 'text_answer', 'file_path', 'file_name',
        'disk', 'status', 'score', 'feedback', 'submitted_at', 'graded_by', 'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'float',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LmsAssignmentSubmission::class, 'lms_assignment_submission_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (($this->disk ?: 'local') !== 'public') {
            return null;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->file_path);
    }
}
