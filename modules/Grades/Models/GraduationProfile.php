<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Class\Models\ClassModel;

class GraduationProfile extends Model
{
    protected $table = 'grade_graduation_profiles';

    protected $fillable = [
        'session_id', 'class_id', 'major_name', 'cohort', 'system_type', 'duration_text',
        'decision_number', 'decision_date', 'diploma_sign_date',
        'registry_number', 'series_number', 'series_group',
        'status', 'note', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'diploma_sign_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GraduationSession::class, 'session_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Nháp',
            'pending_approve' => 'Chờ phê duyệt',
            'approved' => 'Đã duyệt',
            default => $this->status,
        };
    }
}
