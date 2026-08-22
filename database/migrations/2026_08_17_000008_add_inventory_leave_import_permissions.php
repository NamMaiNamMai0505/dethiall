<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void { app(PermissionRegistrar::class)->forgetCachedPermissions();foreach(['inventory.import','leave-management.import'] as $name)Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);foreach(['super-admin','school-manager','training-office-manager'] as $roleName){if($role=Role::where('name',$roleName)->first())$role->givePermissionTo(['inventory.import','leave-management.import']);}app(PermissionRegistrar::class)->forgetCachedPermissions(); }
    public function down(): void { Permission::whereIn('name',['inventory.import','leave-management.import'])->delete(); }
};
