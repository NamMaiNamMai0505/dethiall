@extends('layouts.admin')

@section('title', 'Định mức NCKH')
@section('page-title', 'Định mức NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Định mức NCKH']
]" />

<x-page-header
    title="ĐỊNH MỨC NGHIÊN CỨU KHOA HỌC"
    :actions="[
        \Modules\StandardHours\Support\HubNavigation::backAction(),
        [
            'url' => route('standard-hours.research-norms.create'),
            'label' => 'Thêm mới',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />

<x-filter-form
    :action="route('standard-hours.research-norms.index')"
    :clear-url="route('standard-hours.research-norms.index')"
    :filters="[
        [
            'type' => 'select',
            'name' => 'object_type_id',
            'placeholder' => 'Tất cả đối tượng',
            'options' => $objectTypes->pluck('name', 'id')->toArray()
        ],
        [
            'type' => 'select',
            'name' => 'year',
            'placeholder' => 'Tất cả năm',
            'options' => $years
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

@if($researchNorms->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $researchNorms->firstItem() }} - {{ $researchNorms->lastItem() }}
            trong tổng số {{ $researchNorms->total() }} kết quả
        </div>
        <div class="flex items-center space-x-2">
            <label for="per_page" class="text-sm text-gray-600">Hiển thị:</label>
            <select name="per_page" id="per_page" class="text-sm border border-gray-300 rounded-md px-2 py-1"
                onchange="this.form.submit()">
                @foreach([5, 10, 15, 25, 50] as $option)
                    <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif

</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($researchNorms->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium w-12">STT</th>
                    <th class="px-4 py-3 text-left font-medium">Đối tượng</th>
                    <th class="px-4 py-3 text-left font-medium">Năm</th>
                    <th class="px-4 py-3 text-left font-medium">Số giờ NCKH</th>
                    <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                    <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($researchNorms as $index => $researchNorm)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">{{ $researchNorms->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium">{{ $researchNorm->objectType->name }}</td>
                    <td class="px-4 py-3">{{ $researchNorm->year }}</td>
                    <td class="px-4 py-3">
                        <span class="text-blue-700 font-medium">{{ number_format($researchNorm->research_hours, 0) }} giờ</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($researchNorm->is_active)
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Đang sử dụng</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Ngừng sử dụng</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$researchNorm"
                            :routes="[
                                'show' => 'standard-hours.research-norms.show',
                                'edit' => 'standard-hours.research-norms.edit',
                                'destroy' => 'standard-hours.research-norms.destroy'
                            ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($researchNorms->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t">
            <div class="flex justify-center">{{ $researchNorms->appends(request()->query())->links() }}</div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-journal-bookmark text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có định mức NCKH</h3>
            <p class="text-gray-500 mb-6">Chưa có định mức nghiên cứu khoa học nào được cấu hình.</p>
            <a href="{{ route('standard-hours.research-norms.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-plus mr-2"></i>Thêm định mức đầu tiên
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('select[name]').forEach(select => {
        select.addEventListener('change', function() { this.form.submit(); });
    });
</script>
@endpush