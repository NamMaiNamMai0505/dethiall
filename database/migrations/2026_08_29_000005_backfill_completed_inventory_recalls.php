<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $transfers = DB::table('inventory_transfers as t')
            ->leftJoin('inventory_assets as a', 'a.id', '=', 't.asset_id')
            ->leftJoin('inventory_materials as m', 'm.id', '=', 't.material_id')
            ->where('t.type', 'RECALL')
            ->where('t.status', 'COMPLETED')
            ->whereNotNull('t.warehouse_id')
            ->select([
                't.warehouse_id', 't.quantity', 't.general_note',
                'a.asset_code', 'a.name as asset_name', 'a.unit as asset_unit', 'a.material_id as asset_material_id',
                'm.id as material_id', 'm.code as material_code', 'm.name as material_name', 'm.unit as material_unit',
            ])->get();

        foreach ($transfers as $transfer) {
            $quantity = (int) ($transfer->quantity ?: 0);
            if ($quantity < 1 && preg_match('/Số lượng chuyển:\s*(\d+)/u', (string) $transfer->general_note, $match)) {
                $quantity = (int) $match[1];
            }
            $quantity = max(1, $quantity);
            $code = $transfer->material_code ?: $transfer->asset_code;
            $name = $transfer->material_name ?: $transfer->asset_name;
            if (!$code || !$name) continue;

            $item = DB::table('inventory_warehouse_items')
                ->where('warehouse_id', $transfer->warehouse_id)
                ->where('code', $code)->first();
            if ($item) {
                DB::table('inventory_warehouse_items')->where('id', $item->id)->update([
                    'quantity' => (float) $item->quantity + $quantity,
                    'material_id' => $transfer->material_id ?: $transfer->asset_material_id,
                    'name' => $name,
                    'unit' => $transfer->material_unit ?: $transfer->asset_unit,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('inventory_warehouse_items')->insert([
                    'warehouse_id' => $transfer->warehouse_id,
                    'material_id' => $transfer->material_id ?: $transfer->asset_material_id,
                    'code' => $code,
                    'name' => $name,
                    'unit' => $transfer->material_unit ?: $transfer->asset_unit,
                    'quantity' => $quantity,
                    'minimum_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Existing warehouse stock cannot be safely separated from later receipts.
    }
};
