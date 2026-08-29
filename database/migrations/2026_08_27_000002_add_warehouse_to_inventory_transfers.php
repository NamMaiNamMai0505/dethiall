<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('inventory_transfers', function (Blueprint $table): void { $table->foreignId('warehouse_id')->nullable()->after('material_id')->constrained('inventory_warehouses')->nullOnDelete(); }); }
    public function down(): void { Schema::table('inventory_transfers', function (Blueprint $table): void { $table->dropForeign(['warehouse_id']); $table->dropColumn('warehouse_id'); }); }
};
