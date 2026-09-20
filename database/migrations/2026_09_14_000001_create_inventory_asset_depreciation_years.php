<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_asset_depreciation_years')) {
            return;
        }

        Schema::create('inventory_asset_depreciation_years', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('inventory_assets')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('depreciation_rate', 5, 2)->default(0);
            $table->decimal('depreciation_amount', 16, 2)->default(0);
            $table->decimal('remaining_value', 16, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_id', 'year'], 'inventory_asset_depreciation_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset_depreciation_years');
    }
};
