<?php
namespace Modules\ExamOrganization\Models;
use Illuminate\Database\Eloquent\Model;
class ExamOrganizationPlan extends Model { protected $table='exam_organization_plans'; protected $fillable=['name','exam_category','custom_exam_name','subject_id','class_id','exam_date','exam_time','exam_form','exam_type','status','note']; protected $casts=['exam_date'=>'date']; public function subject(){return $this->belongsTo(\Modules\Subject\Models\Subject::class);} public function class(){return $this->belongsTo(\Modules\Class\Models\ClassModel::class,'class_id');} public function candidates(){return $this->hasMany(ExamOrganizationCandidate::class,'plan_id');} public function logs(){return $this->hasMany(ExamOrganizationLog::class,'plan_id')->latest();} }
