<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveLocality extends Model { protected $table='leave_localities'; protected $fillable=['name','level','parent_id','code']; public function parent(){return $this->belongsTo(self::class,'parent_id');} public function children(){return $this->hasMany(self::class,'parent_id');} public function pathName(): string { $names=[]; for($item=$this;$item;$item=$item->parent){ array_unshift($names, trim((string)$item->name)); } return implode(' - ', array_filter($names)); } }
