<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_repairs', 'source_id')) {
            Schema::table('inventory_repairs', function (Blueprint $table): void {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->index(['source_type', 'source_id'], 'inventory_repairs_source_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_repairs', 'source_id')) {
            Schema::table('inventory_repairs', function (Blueprint $table): void {
                $table->dropIndex('inventory_repairs_source_index');
                $table->dropColumn('source_id');
            });
        }
    }
};
