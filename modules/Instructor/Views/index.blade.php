@extends('layouts.admin')

@section('title', 'Quản lý Giảng viên')
@section('page-title', 'Quản lý Giảng viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giảng viên']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH GIẢNG VIÊN"
    :actions="array_filter([
        auth()->user()->can('instructors.create') ? [
            'url' => route('instructors.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<x-filter-form
    :action="route('instructors.index')"
    :clear-url="route('instructors.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm tên, email, số điện thoại hoặc mã giảng viên...'
        ],
        /* [
            'type' => 'select',
            'name' => 'specialization_id',
            'placeholder' => 'Tất cả chuyên môn',
            'options' => $specializations->pluck('name', 'id')->toArray()
        ], */
        [
            'type' => 'select',
            'name' => 'unit_id',
            'placeholder' => 'Tất cả đơn vị',
            'options' => $units->pluck('name', 'id')->toArray()
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => \Modules\Instructor\Models\Instructor::getStatusOptions()
        ]
    ]">

{{-- Results Summary and Per Page Selector --}}
@if($instructors->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $instructors->firstItem() }} - {{ $instructors->lastItem() }}
            trong tổng số {{ $instructors->total() }} kết quả
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
    @if($instructors->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">STT</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Họ và tên
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Mã GV</th>
                    <th class="px-4 py-3 text-left font-medium">Đơn vị</th>
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
                @foreach($instructors as $key => $instructor)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $instructors->firstItem() + $key }}</td>
                    <td class="px-4 py-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $instructor->name }}</div>
                            <div class="text-sm text-gray-600">
                                <div>{{ $instructor->email }}</div>
                                <div>{{ $instructor->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $instructor->code }}</td>
                    <td class="px-4 py-3">{{ $instructor->unit->name ?? 'Chưa cập nhật' }}</td>
                    <td class="px-4 py-3">
                        <x-status-badge :is-active="$instructor->status === 'active'" />
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">
                            <div>{{ $instructor->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs">{{ $instructor->creator->name ?? 'N/A' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$instructor"
                            :routes="[
                                'show' => 'instructors.show',
                                'edit' => 'instructors.edit',
                                'destroy' => 'instructors.destroy'
                            ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

                {{-- Pagination --}}
        @if($instructors->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-end">
                {{ $instructors->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'department', 'subject', 'status']))
                    Không có giảng viên nào phù hợp với tiêu chí tìm kiếm.
                @else
                    Chưa có giảng viên nào được tạo.
                @endif
            </p>
            @if(request()->hasAny(['search', 'department', 'subject', 'status']))
                <a href="{{ route('instructors.index') }}"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Xóa bộ lọc
                </a>
            @else
                <a href="{{ route('instructors.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Tạo giảng viên đầu tiên
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

    // Add smooth scroll to top when pagination links are clicked
    document.addEventListener('DOMContentLoaded', function() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Small delay to allow page load, then scroll to top
                setTimeout(() => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }, 100);
            });
        });
    });
</script>
@endpush
