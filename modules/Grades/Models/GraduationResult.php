<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Class\Models\ClassModel;

class GraduationResult extends Model
{
    protected $table = 'grade_graduation_results';

    public const STATUSES = [
        'pending' => 'Chờ xử lý',
        'tot_nghiep' => 'Tốt nghiệp',
        'gioi' => 'Giỏi',
        'kha' => 'Khá',
        'trung_binh' => 'Trung bình',
        'khong_tn' => 'Không TN',
        'dinh_chi' => 'Đình chỉ',
        'thoi_hoc' => 'Thôi học',
        'vang_thi' => 'Vắng thi',
    ];

    protected $fillable = [
        'session_id', 'user_id', 'class_id', 'rank_order', 'result_status',
        'gpa', 'exam_score', 'retake_registered', 'note', 'updated_by',
    ];

    protected $casts = [
        'gpa' => 'float',
        'exam_score' => 'float',
        'retake_registered' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GraduationSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->result_status] ?? $this->result_status;
    }
}
