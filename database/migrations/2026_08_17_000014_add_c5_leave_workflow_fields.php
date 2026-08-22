<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_classes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('leave_extra_standards', function (Blueprint $t): void {
            $t->id();
            $t->string('code')->unique();
            $t->string('label');
            $t->unsignedInteger('days')->default(0);
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('leave_mail_logs', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $t->string('to_email');
            $t->string('subject');
            $t->text('body')->nullable();
            $t->string('mode')->nullable();
            $t->boolean('ok')->default(false);
            $t->text('error')->nullable();
            $t->text('preview_url')->nullable();
            $t->string('kind')->nullable();
            $t->timestamps();
        });
        Schema::table('leave_personnel', function (Blueprint $t): void {
            $t->foreignId('class_id')->nullable()->constrained('leave_classes')->nullOnDelete();
            $t->foreignId('commander_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('commander_name')->nullable();
            $t->string('class_name')->nullable();
        });
        Schema::table('leave_requests', function (Blueprint $t): void {
            $t->foreignId('class_id')->nullable()->constrained('leave_classes')->nullOnDelete();
            $t->string('class_name')->nullable();
            $t->string('personnel_code')->nullable();
            $t->string('personnel_name')->nullable();
            $t->string('rank')->nullable();
            $t->string('position')->nullable();
            $t->date('enlistment_date')->nullable();
            $t->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $t->string('unit_name')->nullable();
            $t->text('note')->nullable();
            $t->foreignId('commander_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('commander_name')->nullable();
            $t->foreignId('replacement_personnel_id')->nullable()->constrained('leave_personnel')->nullOnDelete();
            $t->string('replacement_personnel_name')->nullable();
            $t->string('replacement_position')->nullable();
            $t->text('admin_note')->nullable();
        });
        Schema::table('leave_records', function (Blueprint $t): void {
            $t->string('personnel_code')->nullable();
            $t->string('personnel_name')->nullable();
            $t->string('object_type')->nullable();
            $t->string('rank')->nullable();
            $t->string('position')->nullable();
            $t->date('enlistment_date')->nullable();
            $t->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $t->string('unit_name')->nullable();
            $t->unsignedInteger('service_years')->default(0);
            $t->unsignedInteger('base_days')->default(0);
            $t->unsignedInteger('travel_days')->default(0);
            $t->unsignedInteger('extra_days')->default(0);
            $t->json('extra_reasons')->nullable();
            $t->string('leave_year')->nullable();
            $t->foreignId('locality_id')->nullable()->constrained('leave_localities')->nullOnDelete();
            $t->text('locality_path')->nullable();
            $t->text('admin_note')->nullable();
            $t->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('proposed_by_username')->nullable();
            $t->string('proposed_by_display_name')->nullable();
            $t->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('decided_by_username')->nullable();
            $t->timestamp('decided_at')->nullable();
        });
        foreach ([
            ['code'=>'MS01','label'=>'Phép thêm tiêu chuẩn 01','days'=>5,'sort_order'=>1],
            ['code'=>'MS02','label'=>'Phép thêm tiêu chuẩn 02','days'=>5,'sort_order'=>2],
            ['code'=>'MS03','label'=>'Phép thêm tiêu chuẩn 03','days'=>10,'sort_order'=>3],
        ] as $standard) {
            \DB::table('leave_extra_standards')->updateOrInsert(['code'=>$standard['code']], $standard + ['created_at'=>now(),'updated_at'=>now()]);
        }
    }

    public function down(): void
    {
        Schema::table('leave_records', function (Blueprint $t): void { $t->dropForeign(['locality_id']); $t->dropForeign(['unit_id']); $t->dropForeign(['proposed_by_user_id']); $t->dropForeign(['decided_by_user_id']); $t->dropColumn(['personnel_code','personnel_name','object_type','rank','position','enlistment_date','unit_id','unit_name','service_years','base_days','travel_days','extra_days','extra_reasons','leave_year','locality_id','locality_path','admin_note','proposed_by_user_id','proposed_by_username','proposed_by_display_name','decided_by_user_id','decided_by_username','decided_at']); });
        Schema::table('leave_requests', function (Blueprint $t): void { $t->dropForeign(['class_id']); $t->dropForeign(['unit_id']); $t->dropForeign(['commander_user_id']); $t->dropForeign(['replacement_personnel_id']); $t->dropColumn(['class_id','class_name','personnel_code','personnel_name','rank','position','enlistment_date','unit_id','unit_name','note','commander_user_id','commander_name','replacement_personnel_id','replacement_personnel_name','replacement_position','admin_note']); });
        Schema::table('leave_personnel', function (Blueprint $t): void { $t->dropForeign(['class_id']); $t->dropForeign(['commander_user_id']); $t->dropColumn(['class_id','commander_user_id','commander_name','class_name']); });
        Schema::dropIfExists('leave_mail_logs'); Schema::dropIfExists('leave_extra_standards'); Schema::dropIfExists('leave_classes');
    }
};
