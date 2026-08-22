<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        Role::where('guard_name','web')->where('name','Quân nhân : đề xuất phép')->first()?->givePermissionTo('dashboards.index');
    }

    public function down(): void
    {
        Role::where('guard_name','web')->where('name','Quân nhân : đề xuất phép')->first()?->revokePermissionTo('dashboards.index');
    }
};
