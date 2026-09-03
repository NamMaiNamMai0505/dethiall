<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_report_templates') && ! Schema::hasColumn('inventory_report_templates', 'report_type')) {
            Schema::table('inventory_report_templates', function (Blueprint $table): void {
                $table->string('report_type', 80)->nullable()->after('code')->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_report_templates') && Schema::hasColumn('inventory_report_templates', 'report_type')) {
            Schema::table('inventory_report_templates', function (Blueprint $table): void {
                $table->dropUnique(['report_type']);
                $table->dropColumn('report_type');
            });
        }
    }
};
