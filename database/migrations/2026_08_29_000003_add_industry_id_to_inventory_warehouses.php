<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_warehouses', 'industry_id')) {
            Schema::table('inventory_warehouses', function (Blueprint $table): void {
                $table->foreignId('industry_id')->nullable()->after('name')->constrained('inventory_categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_warehouses', 'industry_id')) {
            Schema::table('inventory_warehouses', function (Blueprint $table): void {
                $table->dropForeign(['industry_id']);
                $table->dropColumn('industry_id');
            });
        }
    }
};
