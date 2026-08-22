<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_materials', function (Blueprint $table): void {
            $table->foreignId('building_id')->nullable()->after('category_id')->constrained('buildings')->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->after('building_id')->constrained('classrooms')->nullOnDelete();
        });
        Schema::table('leave_personnel', function (Blueprint $table): void {
            $table->foreignId('unit_id')->nullable()->after('user_id')->constrained('units')->nullOnDelete();
            $table->string('object_type', 50)->nullable();
            $table->string('rank', 80)->nullable();
            $table->date('enlistment_date')->nullable();
            $table->string('hometown')->nullable();
            $table->string('permanent_residence')->nullable();
            $table->string('email')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('leave_personnel', function (Blueprint $table): void { $table->dropForeign(['unit_id']); $table->dropColumn(['unit_id','object_type','rank','enlistment_date','hometown','permanent_residence','email']); });
        Schema::table('inventory_materials', function (Blueprint $table): void { $table->dropForeign(['building_id']); $table->dropForeign(['classroom_id']); $table->dropColumn(['building_id','classroom_id']); });
    }
};
