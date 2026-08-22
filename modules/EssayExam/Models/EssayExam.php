<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Subject\Models\Subject;

class EssayExam extends Model
{
    protected $table = 'essay_exams';
    protected $fillable = ['code','title','subject_id','class_id','status','duration_minutes','note','return_note','created_by_user_id','created_by_username','created_by_display_name','approved_by_user_id','approved_at','approval_qr','locked','academic_year','semester','difficulty','exam_type'];
    protected $casts = ['approved_at' => 'datetime', 'locked' => 'boolean'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function class() { return $this->belongsTo(\Modules\Class\Models\ClassModel::class, 'class_id'); }
    public function questions() { return $this->hasMany(EssayExamQuestion::class)->orderBy('question_number'); }
    public function logs() { return $this->hasMany(EssayExamWorkflowLog::class)->latest(); }
    public function draws() { return $this->hasMany(EssayExamDraw::class, 'essay_exam_id'); }
    public function getStatusLabelAttribute(): string
    {
        return ['DRAFT'=>'Bản nháp','PENDING_DEPT'=>'Chờ duyệt khoa','PENDING_EXAM_OFFICE'=>'Chờ phòng đào tạo','PENDING_BGH'=>'Chờ BGH','APPROVED'=>'Đã duyệt','RETURNED'=>'Trả lại','REJECTED'=>'Từ chối'][$this->status] ?? $this->status;
    }
}
