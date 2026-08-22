@extends('layouts.admin')

@section('title', 'Chi tiết phân công giảng viên')
@section('page-title', 'Chi tiết phân công giảng viên')

@section('content')
<x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Phân công giảng viên', 'url' => route('teaching-assignments.index')],
        ['title' => $instructor->name]
    ]" />

<x-page-header
    title="CHI TIẾT PHÂN CÔNG GIẢNG VIÊN"
    :actions="[
        [
            'url' => route('teaching-assignments.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ],
        [
            'url' => route('teaching-assignments.create', ['instructor_id' => $instructor->id]),
            'label' => 'Chỉnh sửa phân công',
            'icon' => 'pencil-square',
            'color' => 'blue'
        ]
    ]"
/>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Thông tin giảng viên --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- Instructor Profile Card --}}
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            {{-- Header with gradient --}}
            <div class="bg-gradient-to-br from-blue-600 to-indigo-600 px-6 py-8">
                <div class="text-center">
                    {{-- Avatar --}}
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl ring-4 ring-white/30">
                            {{ strtoupper(mb_substr($instructor->name, 0, 1)) }}
                        </div>
                    </div>
                    <h4 class="text-2xl font-bold text-white mb-2">{{ $instructor->name }}</h4>
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm">
                        <i class="bi bi-person-badge text-white mr-2"></i>
                        <code class="text-white font-mono text-sm">{{ $instructor->code }}</code>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <dl class="space-y-4">
                    @if($instructor->email)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="bi bi-envelope text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Email</dt>
                            <dd class="text-sm text-gray-900 truncate">
                                <a href="mailto:{{ $instructor->email }}" class="hover:text-blue-600">{{ $instructor->email }}</a>
                            </dd>
                        </div>
                    </div>
                    @endif

                    @if($instructor->phone)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="bi bi-telephone text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Số điện thoại</dt>
                            <dd class="text-sm text-gray-900">
                                <a href="tel:{{ $instructor->phone }}" class="hover:text-green-600">{{ $instructor->phone }}</a>
                            </dd>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="bi bi-building text-purple-600"></i>
                        </div>
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Đơn vị</dt>
                            <dd class="text-sm text-gray-900">{{ $instructor->unit->name ?? 'Chưa xác định' }}</dd>
                        </div>
                    </div>

                    <div class="flex items-start pt-4 border-t border-gray-200">
                        <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="bi bi-book text-orange-600"></i>
                        </div>
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Số môn giảng dạy</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-800">
                                    <i class="bi bi-journal-check mr-1"></i>
                                    {{ $instructor->subjects->count() }} môn học
                                </span>
                            </dd>
                        </div>
                    </div>

                    @if($instructor->status)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-{{ $instructor->status === 'active' ? 'green' : 'red' }}-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="bi bi-circle-fill text-{{ $instructor->status === 'active' ? 'green' : 'red' }}-600"></i>
                        </div>
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Trạng thái</dt>
                            <dd class="mt-1">
                                @if($instructor->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="bi bi-check-circle-fill mr-1"></i>
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="bi bi-x-circle-fill mr-1"></i>
                                        Tạm ngừng
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="flex items-center px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-lg mr-3">
                    <i class="bi bi-lightning-charge text-gray-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900">Thao tác nhanh</h3>
            </div>
            <div class="p-4 space-y-3">
                <a href="{{ route('teaching-assignments.create', ['instructor_id' => $instructor->id]) }}"
                   class="group w-full inline-flex items-center justify-center px-4 py-3 border-2 border-blue-300 text-sm font-semibold rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 hover:border-blue-400 transition-all duration-200">
                    <i class="bi bi-pencil-square mr-2 text-lg group-hover:scale-110 transition-transform"></i>
                    Chỉnh sửa phân công
                </a>
                <button onclick="confirmClearAll()"
                        class="group w-full inline-flex items-center justify-center px-4 py-3 border-2 border-red-300 text-sm font-semibold rounded-lg text-red-700 bg-red-50 hover:bg-red-100 hover:border-red-400 transition-all duration-200">
                    <i class="bi bi-trash3 mr-2 text-lg group-hover:scale-110 transition-transform"></i>
                    Xóa tất cả phân công
                </button>
                <a href="{{ route('instructors.show', $instructor) }}"
                   class="group w-full inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-gray-50 hover:bg-gray-100 hover:border-gray-400 transition-all duration-200">
                    <i class="bi bi-person-circle mr-2 text-lg group-hover:scale-110 transition-transform"></i>
                    Xem hồ sơ giảng viên
                </a>
            </div>
        </div>
    </div>

    {{-- Danh sách môn học --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="flex items-center px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg mr-3">
                    <i class="bi bi-journal-text text-blue-600"></i>
                </div>
                <h3 class="flex-1 font-semibold text-gray-900">Môn học được phân công</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                    <i class="bi bi-bar-chart-fill mr-1"></i>
                    {{ $instructor->subjects->count() }} môn
                </span>
            </div>

            <div class="p-6">
                @if($instructor->subjects->count() > 0)
                    {{-- Filter và Search --}}
                    <div class="mb-6 flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-search text-gray-400"></i>
                                </div>
                                <input type="text"
                                       id="search-subjects"
                                       class="block w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                       placeholder="Tìm kiếm môn học theo tên...">
                            </div>
                        </div>
                        <div>
                            <button onclick="sortSubjects('name')" class="px-4 py-3 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all">
                                <i class="bi bi-sort-alpha-down mr-2"></i>
                                Sắp xếp A-Z
                            </button>
                        </div>
                    </div>

                    {{-- Subjects Grid --}}
                    <div id="subjects-grid" class="space-y-3">
                        @foreach($instructor->subjects->sortBy('name') as $subject)
                            <div class="subject-card group border-2 border-gray-200 rounded-xl p-5 hover:border-blue-400 hover:shadow-md transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    {{-- Icon --}}
                                    <div class="flex-shrink-0">
                                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                            <i class="bi bi-book text-white text-2xl"></i>
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1 min-w-0">
                                                <a href="{{ route('subjects.show', $subject) }}"
                                                   class="text-lg font-bold text-gray-900 hover:text-blue-600 transition-colors group-hover:text-blue-600">
                                                    {{ $subject->name }}
                                                </a>
                                            </div>
                                            <span class="flex-shrink-0 ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                <i class="bi bi-check-circle-fill mr-1"></i>
                                                Đã phân công
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-4 text-sm">
                                            <span class="inline-flex items-center text-gray-600">
                                                <i class="bi bi-upc-scan mr-1.5 text-gray-400"></i>
                                                <code class="bg-gray-100 text-gray-700 px-2 py-1 rounded font-mono text-xs">{{ $subject->code ?? 'N/A' }}</code>
                                            </span>

                                            @if($subject->credits)
                                                <span class="inline-flex items-center text-gray-600">
                                                    <i class="bi bi-award mr-1.5 text-orange-500"></i>
                                                    <span class="font-semibold text-orange-600">{{ $subject->credits }}</span>
                                                    <span class="ml-1">tín chỉ</span>
                                                </span>
                                            @endif

                                            @if($subject->specialization)
                                                <span class="inline-flex items-center text-gray-600">
                                                    <i class="bi bi-mortarboard mr-1.5 text-purple-500"></i>
                                                    {{ $subject->specialization->name }}
                                                </span>
                                            @endif

                                            @if($subject->total_hours)
                                                <span class="inline-flex items-center text-gray-600">
                                                    <i class="bi bi-clock mr-1.5 text-blue-500"></i>
                                                    {{ $subject->total_hours }} tiết
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6">
                            <i class="bi bi-book text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Chưa có phân công môn học</h3>
                        <p class="text-gray-600 mb-8 max-w-md mx-auto">Giảng viên này chưa được phân công giảng dạy môn học nào. Bạn có thể thêm phân công mới bằng cách nhấn nút bên dưới.</p>
                        <a href="{{ route('teaching-assignments.create', ['instructor_id' => $instructor->id]) }}"
                           class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all duration-200">
                            <i class="bi bi-plus-circle mr-2 text-lg"></i>
                            Thêm phân công ngay
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 transform transition-all">
        <div class="flex items-start mb-6">
            <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4">
                <i class="bi bi-exclamation-triangle-fill text-red-600 text-2xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Xác nhận xóa</h3>
                <p class="text-sm text-gray-600 leading-relaxed">Bạn có chắc chắn muốn xóa toàn bộ phân công của giảng viên <strong class="text-gray-900">{{ $instructor->name }}</strong>?</p>
                <p class="text-sm text-red-600 mt-2 font-medium">
                    <i class="bi bi-info-circle mr-1"></i>
                    Thao tác này không thể hoàn tác!
                </p>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-8">
            <button onclick="hideDeleteModal()" class="px-6 py-2.5 border-2 border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all">
                <i class="bi bi-x-circle mr-1"></i>
                Hủy
            </button>
            <form action="{{ route('teaching-assignments.destroy', $instructor) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-2.5 bg-red-600 border-2 border-red-600 rounded-lg text-sm font-semibold text-white hover:bg-red-700 hover:border-red-700 transition-all shadow-md hover:shadow-lg">
                    <i class="bi bi-trash3-fill mr-1"></i>
                    Xóa tất cả
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('search-subjects');
    const subjectCards = document.querySelectorAll('.subject-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            subjectCards.forEach(card => {
                const subjectName = card.querySelector('h5').textContent.toLowerCase();
                if (subjectName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});

function sortSubjects(type) {
    const grid = document.getElementById('subjects-grid');
    const cards = Array.from(grid.children);
    
    cards.sort((a, b) => {
        const nameA = a.querySelector('h5').textContent;
        const nameB = b.querySelector('h5').textContent;
        return nameA.localeCompare(nameB);
    });
    
    // Re-append sorted cards
    cards.forEach(card => grid.appendChild(card));
}

function confirmClearAll() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>
@endsection