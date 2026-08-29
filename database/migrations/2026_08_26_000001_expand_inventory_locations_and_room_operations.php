<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            if (! Schema::hasColumn('classrooms', 'code')) $table->string('code', 80)->nullable()->after('id');
            if (! Schema::hasColumn('classrooms', 'room_type')) $table->string('room_type', 100)->nullable()->after('name');
            if (! Schema::hasColumn('classrooms', 'floor')) $table->string('floor', 50)->nullable()->after('building_id');
            if (! Schema::hasColumn('classrooms', 'capacity')) $table->unsignedInteger('capacity')->nullable()->after('floor');
            if (! Schema::hasColumn('classrooms', 'managing_unit_id')) $table->foreignId('managing_unit_id')->nullable()->after('capacity')->constrained('units')->nullOnDelete();
            if (! Schema::hasColumn('classrooms', 'description')) $table->text('description')->nullable()->after('managing_unit_id');
        });

        Schema::create('inventory_room_break_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('inventory_assets')->nullOnDelete();
            $table->string('equipment_source')->nullable();
            $table->string('equipment_name');
            $table->decimal('quantity', 14, 2)->default(1);
            $table->date('broken_at');
            $table->text('condition_description')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('PENDING');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_room_repairs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('inventory_assets')->nullOnDelete();
            $table->date('repair_date');
            $table->string('equipment_name');
            $table->text('content')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('repaired_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_room_inventories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('inventory_assets')->nullOnDelete();
            $table->date('inventory_date');
            $table->string('equipment_name');
            $table->decimal('actual_quantity', 14, 2)->default(0);
            $table->decimal('book_quantity', 14, 2)->default(0);
            $table->string('result', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_room_replacements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->date('replaced_at');
            $table->string('old_equipment_name');
            $table->string('new_equipment_name');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_room_replacements');
        Schema::dropIfExists('inventory_room_inventories');
        Schema::dropIfExists('inventory_room_repairs');
        Schema::dropIfExists('inventory_room_break_reports');
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropForeign(['managing_unit_id']);
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'room_type', 'floor', 'capacity', 'managing_unit_id', 'description']);
        });
    }
};
