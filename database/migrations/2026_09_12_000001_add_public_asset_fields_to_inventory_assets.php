<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_assets', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_assets', 'management_type')) {
                $table->string('management_type', 20)->default('MATERIAL')->after('status');
            }
            if (! Schema::hasColumn('inventory_assets', 'unit_price')) {
                $table->decimal('unit_price', 16, 2)->default(0)->after('unit');
            }
            if (! Schema::hasColumn('inventory_assets', 'total_amount')) {
                $table->decimal('total_amount', 16, 2)->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('inventory_assets', 'contract_invoice_number')) {
                $table->string('contract_invoice_number', 150)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('inventory_assets', 'supplier')) {
                $table->string('supplier')->nullable()->after('contract_invoice_number');
            }
            if (! Schema::hasColumn('inventory_assets', 'depreciation_rate')) {
                $table->decimal('depreciation_rate', 5, 2)->default(0)->after('supplier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_assets', function (Blueprint $table): void {
            foreach (['depreciation_rate', 'supplier', 'contract_invoice_number', 'total_amount', 'unit_price', 'management_type'] as $column) {
                if (Schema::hasColumn('inventory_assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
