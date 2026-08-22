<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;

class IntegratedAnswerSet extends Model
{
    protected $table = 'integrated_answer_sets';
    protected $fillable = ['code','title','subject_id','status','created_by_user_id','created_by_username','created_by_display_name','approved_by_user_id','approved_at','return_note'];
    protected $casts = ['approved_at' => 'datetime'];
    public function subject() { return $this->belongsTo(\Modules\Subject\Models\Subject::class); }
    public function items() { return $this->hasMany(IntegratedAnswerItem::class, 'answer_set_id')->orderBy('paper_number')->orderBy('question_number'); }
    public function logs() { return $this->hasMany(IntegratedAnswerWorkflowLog::class, 'answer_set_id')->latest(); }
}
