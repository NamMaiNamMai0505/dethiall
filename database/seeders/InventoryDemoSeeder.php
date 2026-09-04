<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Inventory\Models\InventoryAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryMaterial;
use Modules\Unit\Models\Unit;

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

        $officeRoot = InventoryCategory::firstOrCreate(['code' => 'DEMO-VP'], ['name' => 'Vật tư văn phòng', 'active' => true]);
        $officeType = InventoryCategory::firstOrCreate(['code' => 'DEMO-VP01'], ['parent_id' => $officeRoot->id, 'name' => 'Trang bị phòng làm việc', 'active' => true]);
        $trainingRoot = InventoryCategory::firstOrCreate(['code' => 'DEMO-DT'], ['name' => 'Vật tư đào tạo', 'active' => true]);
        $trainingType = InventoryCategory::firstOrCreate(['code' => 'DEMO-DT01'], ['parent_id' => $trainingRoot->id, 'name' => 'Thiết bị giảng dạy', 'active' => true]);
        $medicalRoot = InventoryCategory::firstOrCreate(['code' => 'DEMO-YT'], ['name' => 'Vật tư y tế', 'active' => true]);
        $medicalType = InventoryCategory::firstOrCreate(['code' => 'DEMO-YT01'], ['parent_id' => $medicalRoot->id, 'name' => 'Dụng cụ sơ cứu', 'active' => true]);

        $materials = collect([
            ['code' => 'DEMO-DT-MC', 'name' => 'Máy chiếu lớp học', 'unit' => 'Bộ', 'category_id' => $trainingType->id, 'quantity' => 80, 'min_quantity' => 5, 'manufacture_year' => 2023, 'usage_year' => 2024],
            ['code' => 'DEMO-DT-MCCH', 'name' => 'Màn chiếu treo tường', 'unit' => 'Cái', 'category_id' => $trainingType->id, 'quantity' => 90, 'min_quantity' => 5, 'manufacture_year' => 2023, 'usage_year' => 2024],
            ['code' => 'DEMO-DT-LOA', 'name' => 'Loa trợ giảng', 'unit' => 'Bộ', 'category_id' => $trainingType->id, 'quantity' => 70, 'min_quantity' => 5, 'manufacture_year' => 2022, 'usage_year' => 2023],
            ['code' => 'DEMO-DT-BANG', 'name' => 'Bảng từ trắng', 'unit' => 'Cái', 'category_id' => $trainingType->id, 'quantity' => 120, 'min_quantity' => 10, 'manufacture_year' => 2024, 'usage_year' => 2024],
            ['code' => 'DEMO-VP-BAN', 'name' => 'Bàn làm việc', 'unit' => 'Cái', 'category_id' => $officeType->id, 'quantity' => 240, 'min_quantity' => 20, 'manufacture_year' => 2022, 'usage_year' => 2023],
            ['code' => 'DEMO-VP-GHE', 'name' => 'Ghế xoay văn phòng', 'unit' => 'Cái', 'category_id' => $officeType->id, 'quantity' => 260, 'min_quantity' => 20, 'manufacture_year' => 2022, 'usage_year' => 2023],
            ['code' => 'DEMO-VP-TU', 'name' => 'Tủ hồ sơ sắt', 'unit' => 'Cái', 'category_id' => $officeType->id, 'quantity' => 110, 'min_quantity' => 10, 'manufacture_year' => 2021, 'usage_year' => 2022],
            ['code' => 'DEMO-YT-TSC', 'name' => 'Tủ thuốc sơ cứu', 'unit' => 'Bộ', 'category_id' => $medicalType->id, 'quantity' => 60, 'min_quantity' => 5, 'manufacture_year' => 2024, 'usage_year' => 2024],
        ])->mapWithKeys(function (array $item): array {
            $material = InventoryMaterial::updateOrCreate(
                ['code' => $item['code']],
                $item + [
                    'status' => 'ACTIVE',
                    'price' => 0,
                    'asset_status' => 'NORMAL',
                    'purchase_date' => '2025-01-01',
                    'expiry_date' => null,
                    'location' => 'Phân bổ theo phòng',
                ]
            );

            return [$item['code'] => $material];
        });

        $unitIds = Unit::active()
            ->where('id', '<>', 1)
            ->orderBy('level')
            ->orderBy('name')
            ->pluck('id')
            ->values();

        if ($unitIds->isEmpty()) {
            return;
        }

        $rooms = Classroom::active()
            ->with(['building', 'managingUnit'])
            ->orderBy('building_id')
            ->orderBy('floor')
            ->orderBy('name')
            ->get();

        foreach ($rooms as $index => $classroom) {
            $holdingUnitId = $classroom->managing_unit_id ?: $unitIds[$index % $unitIds->count()];

            if (! $classroom->managing_unit_id) {
                $classroom->update(['managing_unit_id' => $holdingUnitId]);
            }

            $roomMaterials = $index % 3 === 0
                ? ['DEMO-DT-MC', 'DEMO-DT-MCCH', 'DEMO-DT-LOA', 'DEMO-DT-BANG', 'DEMO-YT-TSC']
                : ['DEMO-VP-BAN', 'DEMO-VP-GHE', 'DEMO-VP-TU', 'DEMO-DT-BANG', 'DEMO-YT-TSC'];

            foreach ($roomMaterials as $materialIndex => $materialCode) {
                $demoMaterial = $materials[$materialCode];
                $quantity = match ($materialCode) {
                    'DEMO-VP-GHE' => 12 + ($index % 8),
                    'DEMO-VP-BAN' => 6 + ($index % 5),
                    'DEMO-DT-BANG', 'DEMO-DT-MC', 'DEMO-DT-MCCH', 'DEMO-DT-LOA', 'DEMO-YT-TSC' => 1,
                    default => 2,
                };

                InventoryAsset::updateOrCreate(
                    ['asset_code' => 'ROOM-'.$classroom->id.'-'.$materialCode],
                    [
                        'material_id' => $demoMaterial->id,
                        'category_id' => $demoMaterial->category_id,
                        'classroom_id' => $classroom->id,
                        'name' => $demoMaterial->name,
                        'category' => $demoMaterial->category?->name,
                        'quantity' => $quantity,
                        'unit' => $demoMaterial->unit,
                        'holding_unit_id' => $holdingUnitId,
                        'grade' => ($index + $materialIndex) % 9 === 0 ? 2 : 1,
                        'status' => ($index + $materialIndex) % 23 === 0 ? 'REPAIRING' : 'NORMAL',
                        'manufacture_year' => $demoMaterial->manufacture_year,
                        'usage_year' => $demoMaterial->usage_year,
                        'install_address' => $classroom->name,
                        'purchase_date' => $demoMaterial->purchase_date,
                        'expiry_date' => $demoMaterial->expiry_date,
                        'note' => 'Dữ liệu mẫu phân bổ vật tư theo phòng và đơn vị quản lý.',
                    ]
                );
            }
        }
    }
}
