<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sau khi bật SoftDeletes trên User + TrainingSchedule, prod 500 nếu thiếu cột deleted_at.
 * Migration này idempotent: chỉ thêm cột khi chưa có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Drop unique email nếu còn (để soft-delete + tạo lại cùng email được)
        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            } catch (\Throwable) {
                try {
                    Schema::table('users', function (Blueprint $table) {
                        $table->dropUnique('users_email_unique');
                    });
                } catch (\Throwable) {
                    // already dropped
                }
            }

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('email');
                });
            } catch (\Throwable) {
                // index may already exist
            }
        }

        if (Schema::hasTable('training_schedules') && ! Schema::hasColumn('training_schedules', 'deleted_at')) {
            Schema::table('training_schedules', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Không drop deleted_at ở down — tránh phá dữ liệu thùng rác đã ghi.
    }
};
