<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_report_templates') && ! Schema::hasColumn('inventory_report_templates', 'original_name')) {
            Schema::table('inventory_report_templates', function (Blueprint $table): void {
                $table->string('original_name')->nullable()->after('file_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_report_templates') && Schema::hasColumn('inventory_report_templates', 'original_name')) {
            Schema::table('inventory_report_templates', function (Blueprint $table): void {
                $table->dropColumn('original_name');
            });
        }
    }
};
