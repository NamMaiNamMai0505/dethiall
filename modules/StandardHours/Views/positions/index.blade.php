@extends('layouts.admin')

@section('title', 'Quản lý Chức danh')
@section('page-title', 'Quản lý Chức danh')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Chức danh']
]" />

<x-page-header
    title="DANH SÁCH CHỨC DANH"
    subtitle="TT 06/2026 Điều 12 — % định mức theo chức vụ. Điều 12.4: Hiệu trưởng / Giám đốc (tài khoản có quyền standard-hours.positions.manage) quy định % kiêm nhiệm khác."
    :actions="[
        \Modules\StandardHours\Support\HubNavigation::backAction(),
        [
            'url' => route('standard-hours.positions.create'),
            'label' => 'Thêm mới',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />

<div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
    <strong>Đ.12.4:</strong> Tài khoản quản trị nhà trường (quyền <code class="bg-white/80 px-1 rounded">standard-hours.positions.manage</code>) được
    <strong>tạo / sửa chức danh</strong> và <strong>% định mức</strong> (gồm các kiêm nhiệm khác ngoài bảng cố định).
    Mỗi GV gắn 1 chức danh; kiêm nhiều chức → chọn chức danh % thấp nhất (định mức cao nhất ưu tiên theo TT: chức danh cao nhất).
</div>

<x-filter-form
    :action="route('standard-hours.positions.index')"
    :clear-url="route('standard-hours.positions.index')"
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

@if($positions->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $positions->firstItem() }} - {{ $positions->lastItem() }}
            trong tổng số {{ $positions->total() }} kết quả
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
    @if($positions->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium w-16">STT</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên chức danh
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Tỷ lệ chức danh</th>
                    <th class="px-4 py-3 text-left font-medium">Tối thiểu đứng lớp</th>
                    <th class="px-4 py-3 text-left font-medium">Mô tả</th>
                    <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                    <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($positions as $index => $position)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $positions->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $position->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $position->formatted_ratio_percent }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $position->formatted_min_classroom_percent }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ Str::limit($position->description, 60) ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($position->is_active)
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
                        <x-table.action-buttons
                            :item="$position"
                            :routes="[
                                'show' => 'standard-hours.positions.show',
                                'edit' => 'standard-hours.positions.edit',
                                'destroy' => 'standard-hours.positions.destroy'
                            ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($positions->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-center">
                {{ $positions->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-person-badge text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'status']))
                    Không có chức danh nào phù hợp với tiêu chí tìm kiếm.
                @else
                    Chưa có chức danh nào được tạo.
                @endif
            </p>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('standard-hours.positions.index') }}"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Xóa bộ lọc
                </a>
            @else
                <a href="{{ route('standard-hours.positions.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Thêm chức danh đầu tiên
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
