<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now=now();
        foreach ([['leave_type'=>'ANNUAL','object_type'=>null,'base_days'=>12,'label'=>'Phép năm tiêu chuẩn'],['leave_type'=>'SICK','object_type'=>null,'base_days'=>30,'label'=>'Nghỉ ốm theo quy định'],['leave_type'=>'PERSONAL','object_type'=>null,'base_days'=>3,'label'=>'Nghỉ việc riêng']] as $row) DB::table('leave_regulations')->insertOrIgnore($row+['active'=>1,'created_at'=>$now,'updated_at'=>$now]);
        foreach ([['name'=>'Chỉ huy'],['name'=>'Trợ lý'],['name'=>'Nhân viên'],['name'=>'Khác']] as $row) DB::table('leave_positions')->insertOrIgnore($row+['sort_order'=>0,'active'=>1,'created_at'=>$now,'updated_at'=>$now]);
        DB::table('inventory_report_templates')->insertOrIgnore(['code'=>'INVENTORY_STANDARD','name'=>'Báo cáo vật tư tiêu chuẩn','description'=>'Mẫu báo cáo tồn kho và tài sản vật tư','active'=>1,'created_at'=>$now,'updated_at'=>$now]);
    }
    public function down(): void { DB::table('leave_regulations')->whereIn('leave_type',['ANNUAL','SICK','PERSONAL'])->delete();DB::table('inventory_report_templates')->where('code','INVENTORY_STANDARD')->delete(); }
};
