<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Unit\Models\Unit;
use Modules\Inventory\Models\{InventoryMaterial, InventoryMovement, InventoryTransfer};

class InventoryMovementReportController extends ModuleBaseController
{
    public function index(Request $request)
    {
        $locations = $this->syncedLocations($request);
        $rows = InventoryMovement::with(['material', 'user'])
            ->when($request->type, fn ($query, $value) => $query->where('type', $value))
            ->when($request->from, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($request->to, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest()->paginate(50)->withQueryString();

        return view('inventory::feature', [
            'section' => 'movement-report',
            'title' => 'Xuất báo cáo',
            'rows' => $rows,
            'transfers' => InventoryTransfer::with(['asset', 'fromClassroom', 'toClassroom'])->latest()->limit(100)->get(),
            'buildings' => collect($locations['buildings'])->map(fn ($item) => (object) $item),
            'classrooms' => collect($locations['classrooms'])->map(fn ($item) => (object) $item),
            'units' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'materials' => InventoryMaterial::orderBy('name')->get(['id', 'code', 'name']),
            'locationsSyncedAt' => $locations['synced_at'],
        ]);
    }

    public function syncLocations(Request $request)
    {
        $this->storeLocations();

        return redirect()->back()
            ->with('success', 'Đã đồng bộ danh sách tòa nhà và phòng từ dữ liệu dùng chung với Dashboard.');
    }

    private function syncedLocations(Request $request): array
    {
        if (! session()->has('inventory_export_locations')) {
            $this->storeLocations();
        }

        return session('inventory_export_locations');
    }

    private function storeLocations(): void
    {
        session([
            'inventory_export_locations' => [
                'buildings' => Building::where('status', true)->orderBy('name')
                    ->get(['id', 'code', 'name'])->toArray(),
                'classrooms' => Classroom::active()->with('building:id,name')->orderBy('name')
                    ->get(['id', 'building_id', 'name'])->map(fn ($room) => [
                        'id' => $room->id,
                        'building_id' => $room->building_id,
                        'name' => $room->name,
                    ])->values()->all(),
                'synced_at' => now()->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function csv(Request $request)
    {
        $rows = InventoryMovement::with('material')
            ->when($request->type, fn ($query, $value) => $query->where('type', $value))
            ->latest()->get();
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Time', 'Code', 'Name', 'Type', 'Quantity', 'Reference', 'Note']);
            foreach ($rows as $i => $row) {
                fputcsv($out, [$i + 1, $row->created_at?->format('Y-m-d H:i'), $row->material?->code, $row->material?->name, $row->type, $row->quantity, $row->reference, $row->note]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'inventory-movements.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
