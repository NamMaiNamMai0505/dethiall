<?php
namespace Modules\LeaveManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\ManagerUnitScope;

class LeavePersonnel extends Model
{
    protected $table = 'leave_personnel';
    protected $fillable = ['user_id','unit_id','class_id','staff_code','name','unit','position','position_id','object_type','rank','enlistment_date','hometown','permanent_residence','email','gmail','active','commander_user_id','commander_name','class_name','managing_agency'];
    protected $casts = ['enlistment_date' => 'date'];

    protected static function booted(): void
    {
        static::addGlobalScope('leave-unit', function ($query): void {
            $user = auth()->user();
            if (ManagerUnitScope::isScoped($user)) $query->whereIn($query->getModel()->getTable().'.unit_id', ManagerUnitScope::managedUnitIds($user));
        });
        static::saving(function (self $personnel): void {
            if (!Schema::hasColumn('leave_personnel', 'managing_agency')) Schema::table('leave_personnel', fn (Blueprint $table) => $table->string('managing_agency', 30)->default('QUAN_LUC')->index());
            if (Schema::hasColumn('leave_personnel', 'position_id') && $personnel->position) $personnel->position_id = \Modules\StandardHours\Models\Position::where('name', $personnel->position)->value('id');
            $personnel->managing_agency = \Modules\LeaveManagement\Support\LeaveAccess::agencyForObject($personnel->object_type);
            if (ManagerUnitScope::isScoped(auth()->user()) && !in_array((int) $personnel->unit_id, ManagerUnitScope::managedUnitIds(auth()->user()), true)) abort(403, 'Bạn chỉ được quản lý nhân sự thuộc đơn vị được phân công.');
            // Bổ sung các trường hồ sơ trong form thêm quân nhân nếu controller cũ chưa nhận chúng.
            if (request()->routeIs('leave-management.personnel.store') || request()->routeIs('leave-management.personnel.update')) {
                foreach (['enlistment_date','hometown','permanent_residence','commander_name','gmail'] as $field) if (request()->has($field)) $personnel->{$field} = request()->input($field) ?: null;
                if (request()->has('commander_user_id')) $personnel->commander_user_id = request()->input('commander_user_id') ?: null;
            }
            $positionKey = Str::lower(Str::ascii((string) $personnel->position));
            $unitName = $personnel->unit ?: ($personnel->unit_id ? \Modules\Unit\Models\Unit::whereKey($personnel->unit_id)->value('name') : null);
            $isDepartmentCommander = Str::contains($positionKey, 'chi huy') && \Modules\LeaveManagement\Support\LeaveAccess::isDepartmentUnitName($unitName);
            $isAgencyPosition = Str::contains($positionKey, ['quan luc', 'co quan can bo']);
            if (Str::contains($positionKey, ['hieu truong', 'pho hieu truong'])) {
                $personnel->commander_user_id = null;
                $personnel->commander_name = null;
            }
            if ($isDepartmentCommander || $isAgencyPosition) {
                $personnel->commander_user_id = null;
                $personnel->commander_name = null;
            }
            if ($personnel->commander_user_id && !in_array((int) $personnel->commander_user_id, \Modules\LeaveManagement\Support\LeaveAccess::commanderUserIds(), true)) {
                abort(422, 'Tài khoản nhận đề xuất phải có chức vụ chỉ huy.');
            }
            if ($personnel->commander_user_id && $personnel->commander_name) {
                $selectedUnitId = \Modules\Unit\Models\Unit::where('name', $personnel->commander_name)->value('id');
                $commander = \App\Models\User::find($personnel->commander_user_id);
                $commanderUnitId = $commander?->unit_id ?: self::withoutGlobalScopes()->where(function ($query) use ($personnel, $commander) { $query->where('user_id', $personnel->commander_user_id); if ($commander?->name) $query->orWhere('name', $commander->name); })->value('unit_id');
                if ($selectedUnitId && ! $commanderUnitId) {
                    abort(422, 'Tài khoản chỉ huy chưa được liên kết với hồ sơ quân nhân và đơn vị.');
                }
                if ($selectedUnitId && $commanderUnitId && (int) $selectedUnitId !== (int) $commanderUnitId) {
                    abort(422, 'Tài khoản chỉ huy không thuộc cơ quan đang chỉ huy đã chọn.');
                }
            }
        });
    }

    public function requests(){return $this->hasMany(LeaveRequest::class,'personnel_id');}
    public function user(){return $this->belongsTo(\App\Models\User::class);}
    public function positionRelation(){return $this->belongsTo(\Modules\StandardHours\Models\Position::class,'position_id');}
    public function unitRelation(){return $this->belongsTo(\Modules\Unit\Models\Unit::class,'unit_id');}
    public function leaveClass(){return $this->belongsTo(LeaveClass::class,'class_id');}
    public function commander(){return $this->belongsTo(\App\Models\User::class,'commander_user_id');}
}
