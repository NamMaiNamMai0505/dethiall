<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;

class IntegratedAnswerItem extends Model
{
    protected $table = 'integrated_answer_items';
    protected $fillable = ['answer_set_id','paper_number','question_number','answer','points'];
    public function answerSet() { return $this->belongsTo(IntegratedAnswerSet::class, 'answer_set_id'); }
}
