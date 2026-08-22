<?php
namespace Modules\ExamOrganization\Models;

use Illuminate\Database\Eloquent\Model;

class ExamOrganizationCandidate extends Model
{
    protected $table = 'exam_organization_candidates';
    protected $fillable = ['plan_id','student_code','student_name','class_name','candidate_number','room_name','packet_number','seat_number','cipher_number','score','score_method','absent','status'];
    protected $casts = ['absent' => 'boolean'];
    public function plan() { return $this->belongsTo(ExamOrganizationPlan::class, 'plan_id'); }
}
