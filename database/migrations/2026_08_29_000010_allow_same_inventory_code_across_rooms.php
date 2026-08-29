<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_assets', function (Blueprint $table): void {
            $table->dropUnique('inventory_assets_asset_code_unique');
        });
    }

    public function down(): void
    {
        // Không tự khôi phục unique vì dữ liệu có thể đã có cùng mã ở nhiều phòng.
    }
};
