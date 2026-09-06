<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class InventoryReportTemplate extends Model { protected $table='inventory_report_templates'; protected $fillable=['code','report_type','name','file_path','original_name','description','active']; protected $casts=['active'=>'boolean']; public function absolutePath():?string{return $this->file_path?Storage::disk('local')->path($this->file_path):null;} public function downloadName():string{return $this->original_name ?: basename((string)$this->file_path) ?: ('mau-bao-cao-vat-tu-'.$this->id.'.docx');} }
