<?php
namespace Modules\EssayExam\Models;
use Illuminate\Database\Eloquent\Model;
class EssayExamDraw extends Model
{
    protected $table = 'essay_exam_draws';
    protected $fillable = ['essay_exam_id','paper_number','question_ids','question_points','draw_code','qr_code','draw_type','class_name','exam_date','exam_time','location','drawn_by_user_id','drawn_at','printed_at'];
    protected $casts = ['question_ids'=>'array','question_points'=>'decimal:2','drawn_at'=>'datetime','printed_at'=>'datetime','exam_date'=>'date'];
    protected static function booted(): void
    {
        static::creating(function (self $draw): void {
            if (app()->bound('essay_exam.paper_number')) $draw->paper_number = (int) app('essay_exam.paper_number');
        });
    }
    public function exam() { return $this->belongsTo(EssayExam::class, 'essay_exam_id'); }
    public function drawnBy() { return $this->belongsTo(\App\Models\User::class, 'drawn_by_user_id'); }
}
