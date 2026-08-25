<?php
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\{Permission,Role};
return new class extends Migration {
    public function up(): void {
        $role = Role::firstOrCreate(['name' => 'Cơ quan quản lý: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân', 'guard_name' => 'web']);
        $source = Role::where('guard_name','web')->where('name','Quân lực: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân')->first();
        $permissions = $source?->permissions ?? Permission::where('guard_name','web')->whereIn('name', ['leave-management.access.index','leave-management.access.show','leave-management.requests.index','leave-management.requests.show','leave-management.approvals.index','leave-management.approvals.show','leave-management.approvals.approve','leave-management.batches.index','leave-management.batches.show','leave-management.records.index','leave-management.records.show','leave-management.reports.index','leave-management.reports.show','leave-management.reports.export','leave-management.index','leave-management.approve','leave-management.export','leave-management.edit'])->get();
        $role->syncPermissions($permissions);
    }
    public function down(): void { Role::where('guard_name','web')->where('name','Cơ quan quản lý: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân')->delete(); }
};
