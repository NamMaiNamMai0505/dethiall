<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    private function role(string $name): ?Role
    {
        return Role::where('guard_name','web')->where('name',$name)->first();
    }

    public function up(): void
    {
        $military=$this->role('Quân nhân : đề xuất phép');
        $commander=$this->role('chỉ huy cơ quan : duyệt phép và đưa phép lên cho các cơ quan quản lý xem xét');
        $agency=$this->role('Quân lực: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân');

        $military?->syncPermissions([
            'leave-management.access.index','leave-management.access.show',
            'leave-management.requests.index','leave-management.requests.show','leave-management.requests.create',
            // Tương thích route cũ của màn hình gửi đề xuất.
            'leave-management.index','leave-management.create',
        ]);

        $commander?->syncPermissions([
            'leave-management.access.index','leave-management.access.show',
            'leave-management.personnel.index','leave-management.personnel.show',
            'leave-management.requests.index','leave-management.requests.show','leave-management.requests.edit',
            'leave-management.approvals.index','leave-management.approvals.show','leave-management.approvals.approve',
            'leave-management.batches.index','leave-management.batches.show','leave-management.records.index','leave-management.records.show',
            'leave-management.index','leave-management.edit','leave-management.approve',
        ]);

        $agency?->syncPermissions([
            'leave-management.access.index','leave-management.access.show',
            'leave-management.requests.index','leave-management.requests.show',
            'leave-management.approvals.index','leave-management.approvals.show','leave-management.approvals.approve',
            'leave-management.batches.index','leave-management.batches.show','leave-management.records.index','leave-management.records.show',
            'leave-management.reports.index','leave-management.reports.show','leave-management.reports.export',
            'leave-management.index','leave-management.approve','leave-management.export',
        ]);
    }

    public function down(): void
    {
        // Không tự khôi phục quyền cũ để tránh làm sai ma trận quyền hiện hành.
    }
};
