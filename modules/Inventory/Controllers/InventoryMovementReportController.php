<?php

namespace Modules\Inventory\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Unit\Models\Unit;
use Modules\Inventory\Models\{InventoryMaterial, InventoryMovement, InventoryReportTemplate, InventoryTransfer};

class InventoryMovementReportController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;

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
            'reportTemplates' => InventoryReportTemplate::where('active', true)->whereNotNull('report_type')->whereNotNull('file_path')->orderBy('report_type')->orderBy('name')->get(),
            'defaultTemplates' => $this->defaultReportTemplates(),
            'locationsSyncedAt' => $locations['synced_at'],
        ]);
    }

    private function defaultReportTemplates(): array
    {
        return [
            'position' => ['name' => 'Theo vị trí lắp đặt', 'report' => 'Thống kê thực lực hiện có', 'scope' => 'Theo vị trí lắp đặt'],
            'total-position' => ['name' => 'Tổng hợp toàn bộ', 'report' => 'Thống kê thực lực hiện có', 'scope' => 'Tổng hợp toàn bộ'],
            'unit' => ['name' => 'Theo đơn vị', 'report' => 'Thống kê thực lực vật tư theo đơn vị'],
            'increase-decrease' => ['name' => 'Tăng, giảm', 'report' => 'Thống kê tăng, giảm thực lực vật tư'],
            'period' => ['name' => 'Tổng hợp theo kỳ', 'report' => 'Báo cáo tổng hợp theo kỳ'],
            'warehouse' => ['name' => 'Kho vật tư', 'report' => 'Báo cáo kho vật tư'],
            'using-position' => ['name' => 'Theo vị trí lắp đặt', 'report' => 'Báo cáo vật tư đang sử dụng', 'scope' => 'Theo vị trí lắp đặt'],
            'using-total' => ['name' => 'Tổng hợp toàn bộ', 'report' => 'Báo cáo vật tư đang sử dụng', 'scope' => 'Tổng hợp toàn bộ'],
            'system-warehouse' => ['name' => 'Kho-vật tư', 'report' => 'Báo cáo hệ thống kho-vật tư'],
            'transfer' => ['name' => 'Quyết định điều động', 'report' => 'Quyết định điều động'],
            'recall' => ['name' => 'Quyết định thu hồi', 'report' => 'Quyết định thu hồi'],
            'repair' => ['name' => 'Vật tư hư hại và sửa chữa', 'report' => 'Vật tư đang hư hại và sửa chữa'],
            'update-log' => ['name' => 'Cập nhật vật tư', 'report' => 'Cập nhật vật tư'],
        ];
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
