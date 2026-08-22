@extends('layouts.admin')

@section('title', 'Quản lý Đối tượng')
@section('page-title', 'Quản lý Đối tượng')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Đối tượng']
]" />

<x-page-header
    title="DANH SÁCH ĐỐI TƯỢNG"
    :actions="[
        \Modules\StandardHours\Support\HubNavigation::backAction(),
        [
            'url' => route('standard-hours.object-types.create'),
            'label' => 'Thêm mới',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />

<x-filter-form
    :action="route('standard-hours.object-types.index')"
    :clear-url="route('standard-hours.object-types.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm theo tên hoặc mô tả...'
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                'active' => 'Đang sử dụng',
                'inactive' => 'Ngừng sử dụng'
            ]
        ]
    ]">

@if($objectTypes->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $objectTypes->firstItem() }} - {{ $objectTypes->lastItem() }}
            trong tổng số {{ $objectTypes->total() }} kết quả
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

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($objectTypes->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium w-16">STT</th>
                    <x-table.sort-header column="code" label="Mã" />
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">GC (base)</th>
                    <th class="px-4 py-3 text-left font-medium">NCKH</th>
                    <th class="px-4 py-3 text-left font-medium">Giờ hành chính</th>
                    <th class="px-4 py-3 text-left font-medium">Mô tả</th>
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
                @foreach($objectTypes as $index => $objectType)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $objectTypes->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-mono font-semibold text-blue-800">{{ $objectType->code ?: '—' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $objectType->name }}</td>
                    <td class="px-4 py-3 tabular-nums">{{ number_format((float)$objectType->standard_hours, 0) }}</td>
                    <td class="px-4 py-3 tabular-nums">{{ number_format((float)$objectType->research_hours, 0) }}</td>
                    <td class="px-4 py-3 tabular-nums font-medium text-indigo-700">{{ number_format((float)$objectType->administrative_hours, 0) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ Str::limit($objectType->description, 60) ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($objectType->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Đang sử dụng
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Ngừng sử dụng
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">
                            <div>{{ $objectType->created_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs">{{ $objectType->creator->name ?? 'N/A' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$objectType"
                            :routes="[
                                'show' => 'standard-hours.object-types.show',
                                'edit' => 'standard-hours.object-types.edit',
                                'destroy' => 'standard-hours.object-types.destroy'
                            ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($objectTypes->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-center">
                {{ $objectTypes->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-tags text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'status']))
                    Không có đối tượng nào phù hợp với tiêu chí tìm kiếm.
                @else
                    Chưa có đối tượng nào được tạo.
                @endif
            </p>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('standard-hours.object-types.index') }}"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Xóa bộ lọc
                </a>
            @else
                <a href="{{ route('standard-hours.object-types.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Thêm đối tượng đầu tiên
                </a>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('select[name]').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
</script>
@endpush
