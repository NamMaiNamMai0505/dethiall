<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 44 / C5 — ngưỡng vắng tính theo % buổi, cấu hình riêng từng môn học
 * (quyết định đã chốt ở docs/sprints/SPRINT44_LMS_AND_SCOPE_UX.md §2). Môn
 * chưa khai thì dùng mức chung ở system_settings (LmsSettings::defaultAbsenceLimitPercent()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'absence_limit_percent')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedTinyInteger('absence_limit_percent')->nullable()->after('faculty_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'absence_limit_percent')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('absence_limit_percent');
            });
        }
    }
};
