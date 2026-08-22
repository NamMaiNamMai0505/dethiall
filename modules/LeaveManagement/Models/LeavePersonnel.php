<?php
namespace Modules\LeaveManagement\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\ManagerUnitScope;

class LeavePersonnel extends Model
{
    protected $table = 'leave_personnel';
    protected $fillable = ['user_id','unit_id','class_id','staff_code','name','unit','position','object_type','rank','enlistment_date','hometown','permanent_residence','email','gmail','active','commander_user_id','commander_name','class_name'];
    protected $casts = ['enlistment_date' => 'date'];

    protected static function booted(): void
    {
        static::addGlobalScope('leave-unit', function ($query): void {
            $user = auth()->user();
            if (ManagerUnitScope::isScoped($user)) $query->whereIn($query->getModel()->getTable().'.unit_id', ManagerUnitScope::managedUnitIds($user));
        });
        static::saving(function (self $personnel): void {
            if (ManagerUnitScope::isScoped(auth()->user()) && !in_array((int) $personnel->unit_id, ManagerUnitScope::managedUnitIds(auth()->user()), true)) abort(403, 'Bạn chỉ được quản lý nhân sự thuộc đơn vị được phân công.');
            // Bổ sung các trường hồ sơ trong form thêm quân nhân nếu controller cũ chưa nhận chúng.
            if (request()->routeIs('leave-management.personnel.store')) {
                foreach (['enlistment_date','hometown','permanent_residence','commander_name','gmail'] as $field) if (request()->has($field)) $personnel->{$field} = request()->input($field) ?: null;
            }
        });
    }

    public function requests(){return $this->hasMany(LeaveRequest::class,'personnel_id');}
    public function user(){return $this->belongsTo(\App\Models\User::class);}
    public function unitRelation(){return $this->belongsTo(\Modules\Unit\Models\Unit::class,'unit_id');}
    public function leaveClass(){return $this->belongsTo(LeaveClass::class,'class_id');}
    public function commander(){return $this->belongsTo(\App\Models\User::class,'commander_user_id');}
}
