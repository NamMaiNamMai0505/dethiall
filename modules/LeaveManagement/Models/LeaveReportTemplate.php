<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class LeaveReportTemplate extends Model { protected $table='leave_report_templates'; protected $fillable=['name','template_kind','report_type','managing_agency','description','disk','file_path','original_name','mime','file_size','active','created_by','updated_by']; protected $casts=['active'=>'boolean','file_size'=>'integer']; public function absolutePath():?string{return $this->file_path?Storage::disk($this->disk?:'local')->path($this->file_path):null;} }
