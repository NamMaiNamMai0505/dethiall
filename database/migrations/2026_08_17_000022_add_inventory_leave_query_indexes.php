<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_materials', function (Blueprint $table): void {
            $table->index(['name', 'code'], 'inventory_materials_name_code_idx');
            $table->index('category_id', 'inventory_materials_category_idx');
        });
        Schema::table('inventory_assets', function (Blueprint $table): void {
            $table->index(['name', 'asset_code'], 'inventory_assets_name_code_idx');
            $table->index('status', 'inventory_assets_status_idx');
        });
        Schema::table('inventory_proposals', function (Blueprint $table): void {
            $table->index(['type', 'status'], 'inventory_proposals_type_status_idx');
        });
        Schema::table('leave_personnel', function (Blueprint $table): void {
            $table->index(['active', 'unit_id', 'name'], 'leave_personnel_active_unit_name_idx');
        });
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->index(['status', 'from_date'], 'leave_requests_status_date_idx');
            $table->index('personnel_id', 'leave_requests_personnel_idx');
        });
        Schema::table('leave_localities', function (Blueprint $table): void {
            $table->index(['parent_id', 'name'], 'leave_localities_parent_name_idx');
            $table->index('code', 'leave_localities_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_materials', function (Blueprint $table): void { $table->dropIndex('inventory_materials_name_code_idx'); $table->dropIndex('inventory_materials_category_idx'); });
        Schema::table('inventory_assets', function (Blueprint $table): void { $table->dropIndex('inventory_assets_name_code_idx'); $table->dropIndex('inventory_assets_status_idx'); });
        Schema::table('inventory_proposals', function (Blueprint $table): void { $table->dropIndex('inventory_proposals_type_status_idx'); });
        Schema::table('leave_personnel', function (Blueprint $table): void { $table->dropIndex('leave_personnel_active_unit_name_idx'); });
        Schema::table('leave_requests', function (Blueprint $table): void { $table->dropIndex('leave_requests_status_date_idx'); $table->dropIndex('leave_requests_personnel_idx'); });
        Schema::table('leave_localities', function (Blueprint $table): void { $table->dropIndex('leave_localities_parent_name_idx'); $table->dropIndex('leave_localities_code_idx'); });
    }
};
