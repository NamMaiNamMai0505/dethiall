@extends('layouts.admin')

@section('title', 'Quản lý Đơn vị')
@section('page-title', 'Quản lý Đơn vị')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đơn vị']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH ĐƠN VỊ"
    :actions="array_filter([
        auth()->user()->can('units.create') ? [
            'url' => route('units.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<x-filter-form
    :action="route('units.index')"
    :clear-url="route('units.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm theo mã hoặc tên đơn vị...'
        ],
        [
            'type' => 'select',
            'name' => 'parent_id',
            'placeholder' => 'Tất cả đơn vị cấp trên',
            'options' => $parentUnits->pluck('name', 'id')->toArray()
        ],
        [
            'type' => 'select',
            'name' => 'functional_type',
            'placeholder' => 'Tất cả chức năng đơn vị',
            'options' => \Modules\Unit\Models\Unit::getFunctionalTypeOptions()
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                'active' => 'Hoạt động',
                'inactive' => 'Tạm ngừng'
            ]
        ]
    ]">

{{-- Results Summary and Per Page Selector --}}
@if($units->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $units->firstItem() }} - {{ $units->lastItem() }}
            trong tổng số {{ $units->total() }} kết quả
        </div>

        <div class="flex items-center space-x-2">
            <label for="per_page" class="text-sm text-gray-600">Hiển thị:</label>
            <select name="per_page" id="per_page"
                class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                onchange="this.form.submit()">
                @foreach([5, 10, 15, 25, 50] as $option)
                    <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            <span class="text-sm text-gray-600">/ trang</span>
        </div>
    </div>
@endif

</x-filter-form>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($units->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Mã đơn vị
                            @if(request('sort_by') == 'code')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên đơn vị
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Đơn vị cấp trên</th>
                    <th class="px-4 py-3 text-left font-medium">Chức năng</th>
                    <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Ngày tạo
                            @if(request('sort_by') == 'created_at' || !request('sort_by'))
                                <i class="bi bi-arrow-{{ (request('sort_order') == 'asc' || !request('sort_order')) ? 'down' : 'up' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($units as $unit)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">{{ $unit->code }}</code>
                    </td>
                    <td class="px-4 py-3">{{ $unit->formatted_name }}</td>
                    <td class="px-4 py-3">{{ $unit->parent->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            {{ $unit->functional_type_label }}@if($unit->faculty_code) · {{ $unit->faculty_code }}@endif
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $unit->status == 'active' ? 'Hoạt động' : 'Ngưng hoạt động' }}</td>
                   
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">
                            <div>{{ $unit->created_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs">{{ $unit->creator->name ?? 'N/A' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$unit"
                            :routes="[
                                'show' => 'units.show',
                                'edit' => 'units.edit',
                                'destroy' => 'units.destroy'
                            ]"
                            delete-confirm-message="Xóa đơn vị này? Các môn học đang thuộc khoa này sẽ chỉ mất gán (không còn khoa quản lý), bản thân môn học KHÔNG bị xóa." />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($units->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-center">
                {{ $units->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'parent_id', 'functional_type', 'status']))
                    Không có đơn vị nào phù hợp với tiêu chí tìm kiếm.
                @else
                    Chưa có đơn vị nào được tạo.
                @endif
            </p>
            @if(request()->hasAny(['search', 'parent_id', 'functional_type', 'status']))
                <a href="{{ route('units.index') }}"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Xóa bộ lọc
                </a>
            @else
                <a href="{{ route('units.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Tạo đơn vị đầu tiên
                </a>
            @endif
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Auto submit form when filters change
    document.querySelectorAll('select[name]').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
</script>
@endpush
