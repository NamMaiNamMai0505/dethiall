<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;

class EssayExamWorkflowLog extends Model
{
    protected $table = 'essay_exam_workflow_logs';
    protected $fillable = ['essay_exam_id','action','from_status','to_status','note','actor_user_id','actor_username','actor_display_name'];
    public function exam() { return $this->belongsTo(EssayExam::class, 'essay_exam_id'); }
}
