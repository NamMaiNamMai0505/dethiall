<?php

namespace Modules\Inventory\Providers;

use App\Support\InventoryRoomAccess;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Classroom\Models\Classroom;
use Modules\Inventory\Models\InventoryAsset;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryMaterial;
use Modules\Inventory\Models\InventoryProposal;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'inventory');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        View::composer('inventory::partials.room-details', function ($view): void {
            $data = $view->getData();
            $classroom = $data['classroom'] ?? null;
            $users = $data['users'] ?? collect();
            $unitId = $classroom?->managing_unit_id;

            $view->with('users', $unitId
                ? collect($users)->where('unit_id', $unitId)->values()
                : collect());
        });

        InventoryProposal::addGlobalScope('inventory-room-manager', function ($query): void {
            if (!InventoryRoomAccess::shouldScopeList()) return;
            $roomIds = InventoryRoomAccess::allowedRoomIds(auth()->user());
            if ($roomIds === null) return;
            $query->whereHas('items', fn ($items) => $items->whereIn('from_classroom_id', $roomIds));
        });

        InventoryAsset::addGlobalScope('inventory-room-manager', function ($query): void {
            if (!InventoryRoomAccess::shouldScopeList()) return;
            $roomIds = InventoryRoomAccess::allowedRoomIds(auth()->user());
            if ($roomIds === null) return;
            $query->whereIn('classroom_id', $roomIds ?: [-1]);
        });

        InventoryMaterial::addGlobalScope('inventory-room-manager', function ($query): void {
            if (!InventoryRoomAccess::shouldScopeList()) return;
            $roomIds = InventoryRoomAccess::allowedRoomIds(auth()->user());
            if ($roomIds === null) return;
            $query->where(function ($materials) use ($roomIds): void {
                $materials->whereIn('classroom_id', $roomIds ?: [-1])
                    ->orWhereExists(function ($assets) use ($roomIds): void {
                        $assets->selectRaw('1')->from('inventory_assets')
                            ->whereColumn('inventory_assets.material_id', 'inventory_materials.id')
                            ->whereIn('inventory_assets.classroom_id', $roomIds ?: [-1]);
                    });
            });
        });

        InventoryCategory::addGlobalScope('inventory-room-manager', function ($query): void {
            if (!InventoryRoomAccess::shouldScopeList()) return;
            $roomIds = InventoryRoomAccess::allowedRoomIds(auth()->user());
            if ($roomIds === null) return;

            $categoryIds = InventoryMaterial::withoutGlobalScopes()
                ->where(function ($materials) use ($roomIds): void {
                    $materials->whereIn('classroom_id', $roomIds ?: [-1])
                        ->orWhereExists(function ($assets) use ($roomIds): void {
                            $assets->selectRaw('1')->from('inventory_assets')
                                ->whereColumn('inventory_assets.material_id', 'inventory_materials.id')
                                ->whereIn('inventory_assets.classroom_id', $roomIds ?: [-1]);
                        });
                })->pluck('category_id')->filter()->unique()->values();
            $visibleIds = $categoryIds->merge(
                InventoryCategory::withoutGlobalScopes()->whereIn('id', $categoryIds)->pluck('parent_id')
            )->filter()->unique()->values();
            $query->whereIn('id', $visibleIds->all() ?: [-1]);
        });

        Classroom::addGlobalScope('inventory-room-manager', function ($query): void {
            if (!InventoryRoomAccess::shouldScopeList()) return;
            $roomIds = InventoryRoomAccess::allowedRoomIds(auth()->user());
            if ($roomIds === null) return;
            $query->whereIn('id', $roomIds ?: [-1]);
        });
    }
}
