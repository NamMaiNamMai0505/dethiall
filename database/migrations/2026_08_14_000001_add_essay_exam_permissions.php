<?php
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
return new class extends Migration {
    public function up(): void {
        $names = ['essay-exams.index','essay-exams.create','essay-exams.approve','essay-exams.bank','essay-exams.draw'];
        foreach ($names as $name) Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
        $grant = function (string $role, array $permissions): void { if ($r=Role::where('name',$role)->where('guard_name','web')->first()) $r->givePermissionTo($permissions); };
        $grant('instructor', ['essay-exams.index','essay-exams.create']);
        $grant('exam-manager', ['essay-exams.index','essay-exams.approve','essay-exams.bank','essay-exams.draw']);
        $grant('training-office-manager', ['essay-exams.index','essay-exams.approve','essay-exams.bank']);
        $grant('system-manager', $names);
    }
    public function down(): void { Permission::whereIn('name',['essay-exams.index','essay-exams.create','essay-exams.approve','essay-exams.bank','essay-exams.draw'])->delete(); }
};
