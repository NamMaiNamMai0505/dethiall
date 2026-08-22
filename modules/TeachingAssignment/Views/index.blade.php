@extends('layouts.admin')

@section('title', 'Phân công giảng dạy')
@section('page-title', 'Phân công giảng dạy')

@section('content')
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Phân công giảng dạy']
        ]" />

    <x-page-header title="DANH SÁCH PHÂN CÔNG GIẢNG VIÊN" />

    {{-- Bộ lọc --}}
    <x-filter-form :action="route('teaching-assignments.index')" :clear-url="route('teaching-assignments.index')"
        :filters="[
            [
                'type' => 'search',
                'name' => 'search',
                'placeholder' => 'Tìm kiếm tên, email, số điện thoại hoặc mã giảng viên...'
            ],
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
        ]" />

    {{-- Thống kê + chọn số dòng/trang --}}
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
                    onchange="changePerPage(this.value)">
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

    {{-- Bảng --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if($instructors->count() > 0)
            <div class="overflow-hidden rounded-lg">
                <table class="w-full">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">STT</th>
                            <th class="px-4 py-3 text-left font-medium">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                                   class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                                    Họ tên
                                    @if(request('sort_by') == 'name')
                                        <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">Mã GV</th>
                            <th class="px-4 py-3 text-left font-medium">Đơn vị</th>
                            <th class="px-4 py-3 text-left font-medium">Môn đang dạy</th>
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
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($instructors as $index => $instructor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $instructors->firstItem() + $index }}</td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $instructor->name }}</div>
                                        <div class="text-sm text-gray-600">
                                            <div>{{ $instructor->email }}</div>
                                            <div>{{ $instructor->phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $instructor->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $instructor->unit->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @forelse($instructor->subjects as $subject)
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mb-1">
                                            {{ $subject->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-500 italic">Chưa có</span>
                                    @endforelse
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        <div>{{ $instructor->created_at->format('d/m/Y') }}</div>
                                        <div class="text-xs">{{ $instructor->creator->name ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    @if(auth()->check() && auth()->user()->can('teaching-assignments.show'))
                                        <a href="{{ route('teaching-assignments.show', $instructor) }}"
                                            class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif


                                    @if($instructor->subjects->count() == 0)
                                        @if(auth()->check() && auth()->user()->can('teaching-assignments.create'))
                                            <a href="{{ route('teaching-assignments.create', ['instructor_id' => $instructor->id]) }}"
                                                class="text-green-600 hover:text-green-900" title="Tạo phân công mới">
                                                <i class="bi bi-plus-circle"></i>
                                            </a>
                                        @endif
                                    @else
                                        @if(auth()->check() && auth()->user()->can('teaching-assignments.edit'))
                                            <a href="{{ route('teaching-assignments.create', ['instructor_id' => $instructor->id]) }}"
                                                class="text-indigo-600 hover:text-indigo-900" title="Chỉnh sửa phân công">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                    @endif
                                    @if(auth()->check() && auth()->user()->can('teaching-assignments.delete'))
                                        <form action="{{ route('teaching-assignments.destroy', $instructor) }}" method="POST"
                                            class="inline-block" data-confirm='Xoá toàn bộ phân công của giảng viên này?'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa toàn bộ phân công">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($instructors->hasPages())
                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                    <div class="flex justify-end">
                        {{ $instructors->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <x-empty-state icon="bi-person-badge" title="Chưa có phân công nào"
                description="Hãy chọn một giảng viên để phân công môn học." />
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

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