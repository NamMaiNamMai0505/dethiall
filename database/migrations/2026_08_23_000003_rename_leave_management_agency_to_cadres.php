<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
return new class extends Migration {
    private const OLD_ROLE='Cơ quan quản lý: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân';
    private const NEW_ROLE='Cơ quan cán bộ: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân';
    public function up(): void {
        $old=Role::where('guard_name','web')->where('name',self::OLD_ROLE)->first();
        $new=Role::firstOrCreate(['name'=>self::NEW_ROLE,'guard_name'=>'web']);
        if($old){$new->syncPermissions($old->permissions); DB::table('model_has_roles')->where('role_id',$old->id)->update(['role_id'=>$new->id]); $old->delete();}
        foreach(['leave_personnel','leave_requests'] as $table) if(DB::getSchemaBuilder()->hasColumn($table,'managing_agency')) DB::table($table)->where('managing_agency','CO_QUAN_QUAN_LY')->update(['managing_agency'=>'CO_QUAN_CAN_BO']);
    }
    public function down(): void { $new=Role::where('guard_name','web')->where('name',self::NEW_ROLE)->first(); if($new){$old=Role::firstOrCreate(['name'=>self::OLD_ROLE,'guard_name'=>'web']);$old->syncPermissions($new->permissions);DB::table('model_has_roles')->where('role_id',$new->id)->update(['role_id'=>$old->id]);$new->delete();} foreach(['leave_personnel','leave_requests'] as $table) if(DB::getSchemaBuilder()->hasColumn($table,'managing_agency')) DB::table($table)->where('managing_agency','CO_QUAN_CAN_BO')->update(['managing_agency'=>'CO_QUAN_QUAN_LY']); }
};
