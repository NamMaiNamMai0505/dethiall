<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;

class IntegratedAnswerWorkflowLog extends Model
{
    protected $table = 'integrated_answer_workflow_logs';
    protected $fillable = ['answer_set_id','action','from_status','to_status','note','actor_user_id','actor_username','actor_display_name'];
    public function answerSet() { return $this->belongsTo(IntegratedAnswerSet::class, 'answer_set_id'); }
}
