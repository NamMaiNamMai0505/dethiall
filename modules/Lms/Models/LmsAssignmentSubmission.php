<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class LmsAssignmentSubmission extends Model
{
    protected $table = 'lms_assignment_submissions';

    protected $fillable = [
        'lms_assignment_id', 'user_id', 'attempt_no', 'version_count',
        'text_answer', 'file_path', 'file_name',
        'disk', 'submitted_at', 'status', 'score', 'feedback', 'graded_by', 'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'float',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(LmsAssignment::class, 'lms_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LmsAssignmentSubmissionVersion::class, 'lms_assignment_submission_id')
            ->orderBy('version_no');
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        // File trên disk private không được tạo URL công khai. Người dùng phải
        // tải qua controller có kiểm tra course/submission ownership.
        if (($this->disk ?: 'local') !== 'public') {
            return null;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->file_path);
    }
}
