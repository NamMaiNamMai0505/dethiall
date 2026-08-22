<?php
namespace Modules\ExamOrganization\Models;

use Illuminate\Database\Eloquent\Model;

class ExamOrganizationLog extends Model
{
    protected $table = 'exam_organization_logs';
    protected $fillable = ['plan_id','process_type','method','from_number','to_number','file_name','created_by'];
    public function plan() { return $this->belongsTo(ExamOrganizationPlan::class, 'plan_id'); }
}
