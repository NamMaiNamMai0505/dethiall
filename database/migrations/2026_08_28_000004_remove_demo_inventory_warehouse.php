<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove only the warehouse created by InventoryDemoSeeder. Stock rows
        // are cascade-deleted by the warehouse_items foreign key.
        DB::table('inventory_warehouses')->where('code', 'DEMO-KHO')->delete();
    }

    public function down(): void
    {
        // Demo data is intentionally not restored on rollback.
    }
};
