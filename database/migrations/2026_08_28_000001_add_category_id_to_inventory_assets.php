<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_assets', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_assets', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('material_id')->constrained('inventory_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_assets', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });
    }
};
