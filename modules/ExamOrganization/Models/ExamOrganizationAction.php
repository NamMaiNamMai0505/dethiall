<?php
namespace Modules\ExamOrganization\Models;
use Illuminate\Database\Eloquent\Model;
class ExamOrganizationAction extends Model { protected $table='exam_organization_actions'; protected $fillable=['plan_id','action_type','name','status','note','instructor_id','role']; public function plan(){return $this->belongsTo(ExamOrganizationPlan::class,'plan_id');} public function instructor(){return $this->belongsTo(\Modules\Instructor\Models\Instructor::class,'instructor_id');} }
