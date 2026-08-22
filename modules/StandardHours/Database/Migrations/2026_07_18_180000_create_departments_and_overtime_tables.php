<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bộ môn (thuộc khoa) + giảm trừ Đ.11.3 + phân bổ vượt DM Đ.17.
 * Bộ môn chỉ phục vụ giờ chuẩn; các module khác vẫn lọc theo unit (khoa).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_departments')) {
            Schema::create('academic_departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
                $table->string('code', 40);
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['unit_id', 'code'], 'acad_dept_unit_code_uq');
            });
        }

        if (Schema::hasTable('instructors') && ! Schema::hasColumn('instructors', 'department_id')) {
            Schema::table('instructors', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('unit_id');
                $table->foreign('department_id', 'instr_dept_fk')
                    ->references('id')->on('academic_departments')->nullOnDelete();
            });
        }

        // Điều 11.3 — giảm trừ định mức (đột xuất / ốm / thai sản)
        if (! Schema::hasTable('instructor_norm_reductions')) {
            Schema::create('instructor_norm_reductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id')->constrained('instructors')->cascadeOnDelete();
                $table->string('academic_year', 20);
                // special_duty | sick_leave | maternity | other
                $table->string('type', 30);
                $table->string('title')->nullable();
                $table->text('note')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                // Số ngày giảm (trong năm học) — dùng để tính tỷ lệ /365 hoặc / số ngày năm học
                $table->unsignedSmallInteger('days')->default(0);
                // Hoặc ghi đè % giảm thủ công (0–100); null = auto theo days
                $table->decimal('reduction_percent', 5, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['instructor_id', 'academic_year'], 'instr_norm_red_idx');
            });
        }

        // Điều 17 — snapshot pool + phân bổ vượt BM
        if (! Schema::hasTable('department_overtime_pools')) {
            Schema::create('department_overtime_pools', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('academic_departments')->cascadeOnDelete();
                $table->string('academic_year', 20);
                $table->decimal('pool_must_hours', 10, 2)->default(0); // tổng định mức phải làm
                $table->decimal('pool_done_hours', 10, 2)->default(0); // tổng thực hiện
                $table->decimal('pool_excess_hours', 10, 2)->default(0); // vượt
                $table->unsignedSmallInteger('member_count')->default(0);
                $table->json('member_snapshot')->nullable(); // chi tiết từng GV lúc tính
                $table->string('status', 20)->default('draft'); // draft|finalized|locked
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('calculated_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['department_id', 'academic_year'], 'dept_ot_pool_uq');
            });
        }

        if (! Schema::hasTable('department_overtime_allocations')) {
            Schema::create('department_overtime_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pool_id')->constrained('department_overtime_pools')->cascadeOnDelete();
                $table->foreignId('instructor_id')->constrained('instructors')->cascadeOnDelete();
                $table->decimal('allocated_hours', 10, 2)->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['pool_id', 'instructor_id'], 'dept_ot_alloc_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_overtime_allocations');
        Schema::dropIfExists('department_overtime_pools');
        Schema::dropIfExists('instructor_norm_reductions');

        if (Schema::hasTable('instructors') && Schema::hasColumn('instructors', 'department_id')) {
            Schema::table('instructors', function (Blueprint $table) {
                $table->dropForeign('instr_dept_fk');
                $table->dropColumn('department_id');
            });
        }

        Schema::dropIfExists('academic_departments');
    }
};
