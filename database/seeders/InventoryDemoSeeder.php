<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Inventory\Models\InventoryAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryMaterial;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $root = InventoryCategory::firstOrCreate(['code' => 'DEMO-IT'], ['name' => 'Công nghệ thông tin', 'active' => true]);
        $type = InventoryCategory::firstOrCreate(['code' => 'DEMO-IT01'], ['parent_id' => $root->id, 'name' => 'Thiết bị máy tính', 'active' => true]);
        $building = Building::firstOrCreate(['code' => 'DEMO-A'], ['name' => 'Tòa nhà mẫu A', 'status' => true]);
        $room = Classroom::firstOrCreate(['code' => 'DEMO-A101'], ['name' => 'Phòng mẫu 101', 'building_id' => $building->id, 'room_type' => 'Phòng học', 'floor' => '1', 'capacity' => 40, 'status' => true]);
        $material = InventoryMaterial::firstOrCreate(['code' => 'DEMO-IT0101'], ['category_id' => $type->id, 'building_id' => $building->id, 'classroom_id' => $room->id, 'name' => 'Máy tính để bàn mẫu', 'unit' => 'Cái', 'quantity' => 21, 'min_quantity' => 5, 'status' => 'ACTIVE', 'manufacture_year' => 2024, 'usage_year' => 2025, 'purchase_date' => '2025-01-15', 'expiry_date' => '2028-01-15', 'location' => 'Phòng mẫu 101']);
        InventoryAsset::firstOrCreate(['asset_code' => 'DEMO-ASSET-001'], ['material_id' => $material->id, 'classroom_id' => $room->id, 'name' => 'Máy tính để bàn mẫu', 'category' => 'Thiết bị máy tính', 'quantity' => 5, 'unit' => 'Cái', 'grade' => 1, 'status' => 'NORMAL', 'manufacture_year' => 2024, 'usage_year' => 2025, 'install_address' => 'Phòng mẫu 101']);
    }
}
