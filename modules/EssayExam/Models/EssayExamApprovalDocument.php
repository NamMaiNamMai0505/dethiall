<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Class\Models\ClassModel;
use Modules\Subject\Models\Subject;

class EssayExamApprovalDocument extends Model
{
    protected $table = 'essay_exam_approval_documents';

    protected $fillable = [
        'essay_exam_id', 'decision_code', 'title', 'class_id', 'class_name',
        'subject_id', 'subject_name', 'approved_by_user_id', 'approver_name',
        'approved_at', 'signature_method', 'signature_path', 'document_path',
        'status', 'sent_to_exam_office_at', 'sent_to_exam_office_by_user_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'sent_to_exam_office_at' => 'datetime',
    ];

    public function exam() { return $this->belongsTo(EssayExam::class, 'essay_exam_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function class() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id'); }
}
