<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryReportTemplate extends Model { protected $table='inventory_report_templates'; protected $fillable=['code','name','file_path','description','active']; protected $casts=['active'=>'boolean']; }
