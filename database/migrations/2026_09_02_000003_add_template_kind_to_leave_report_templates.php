<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leave_report_templates')) {
            return;
        }

        if (! Schema::hasColumn('leave_report_templates', 'template_kind')) {
            Schema::table('leave_report_templates', function (Blueprint $table): void {
                $table->string('template_kind', 30)->default('report')->after('id')->index();
            });
        }

        DB::table('leave_report_templates')
            ->whereNull('template_kind')
            ->orWhere('template_kind', '')
            ->update(['template_kind' => 'report']);
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_report_templates') && Schema::hasColumn('leave_report_templates', 'template_kind')) {
            Schema::table('leave_report_templates', function (Blueprint $table): void {
                $table->dropColumn('template_kind');
            });
        }
    }
};
