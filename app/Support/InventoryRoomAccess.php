<?php

namespace App\Support;

use Illuminate\Http\Request;
use Modules\Classroom\Models\Classroom;
use Modules\Inventory\Models\InventoryAsset;
use Modules\Inventory\Models\InventoryMaterial;
use Modules\Inventory\Models\InventoryRoomUser;

final class InventoryRoomAccess
{
    public static function roomIds(?object $user): ?array
    {
        if (!$user || $user->isSuperAdmin()) return null;
        $assigned = InventoryRoomUser::where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNull('role')
                    ->orWhere('role', 'like', '%quản lý%')
                    ->orWhere('role', 'like', '%phụ trách%')
                    ->orWhere('role', 'like', '%manager%');
            })
            ->pluck('classroom_id')->map(fn ($id) => (int) $id)->all();
        return $assigned;
    }

    public static function allowedRoomIds(?object $user): ?array
    {
        $assigned = self::roomIds($user);
        if ($assigned === null || $assigned) return $assigned;
        if (!$user?->unit_id) return [];
        return Classroom::withoutGlobalScopes()->where('managing_unit_id', $user->unit_id)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function shouldScopeList(): bool
    {
        return request()->routeIs('inventory.proposals', 'inventory.liquidation');
    }

    public static function enforceProposal(Request $request): void
    {
        $user = $request->user();
        if (!$user || $user->isSuperAdmin()) return;

        $roomIds = self::allowedRoomIds($user) ?? [];
        abort_unless($roomIds, 403, 'Tài khoản chưa được gán quản lý phòng vật tư.');

        if ($user->unit_id) {
            abort_unless((int) $request->input('unit_id') === (int) $user->unit_id, 403,
                'Đơn vị đề xuất phải là đơn vị công tác của tài khoản.');
            $request->merge(['unit_id' => $user->unit_id]);
        }

        $roomId = (int) $request->input('classroom_id');
        abort_unless($roomId && in_array($roomId, $roomIds, true), 403,
            'Bạn chỉ được đề xuất hoặc thanh lý vật tư thuộc phòng được phân công quản lý.');

        if ($request->filled('asset_id')) {
            $asset = InventoryAsset::findOrFail($request->integer('asset_id'));
            abort_unless((int) $asset->classroom_id === $roomId, 403,
                'Vật tư được chọn không thuộc phòng đang quản lý.');
        }

        if ($request->filled('material_id')) {
            $materialId = $request->integer('material_id');
            $belongsToRoom = InventoryMaterial::whereKey($materialId)->where('classroom_id', $roomId)->exists()
                || InventoryAsset::where('classroom_id', $roomId)->where('material_id', $materialId)->exists();
            abort_unless($belongsToRoom, 403, 'Vật tư được chọn không thuộc phòng đang quản lý.');
        }
    }
}
