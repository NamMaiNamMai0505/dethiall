<?php
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration { public function up(): void { app(PermissionRegistrar::class)->forgetCachedPermissions();$names=[];foreach(['inventory','leave-management'] as $module)foreach(['index','show','create','edit','delete','approve','export'] as $action)$names[]="$module.$action";foreach($names as $name)Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);foreach(['super-admin','school-manager','training-office-manager','faculty-manager','exam-manager'] as $roleName){$role=Role::where('name',$roleName)->first();if($role)$role->givePermissionTo($names);}app(PermissionRegistrar::class)->forgetCachedPermissions();} public function down(): void {Permission::whereIn('name',array_merge(array_map(fn($a)=>"inventory.$a",['index','show','create','edit','delete','approve','export']),array_map(fn($a)=>"leave-management.$a",['index','show','create','edit','delete','approve','export'])))->delete();}};
