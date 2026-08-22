@extends('layouts.admin')

@section('title', 'Chỉnh sửa: ' . $specialization->name)
@section('page-title', 'Chỉnh sửa ngành đào tạo')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'ngành đào tạo', 'url' => route('specializations.index')],
    ['title' => $specialization->name, 'url' => route('specializations.show', $specialization)],
    ['title' => 'Chỉnh sửa']
]" />

{{-- Page Header --}}
<x-page-header
    title="{{ $specialization->name }}"
    subtitle="Cập nhật thông tin ngành đào tạo"
    :actions="[
        [
            'url' => route('specializations.show', $specialization),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<!-- Main Form -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Column -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-info-circle text-blue-500 mr-2"></i>
                    Thông tin cơ bản
                </h3>
            </div>
            <div class="p-6">
                <form action="{{ route('specializations.update', $specialization) }}" method="POST" id="specializationForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-6">
                        <x-form.select
                            name="training_system_id"
                            label="Hệ đào tạo"
                            placeholder="Chọn hệ (Dân sự / Quân sự…)"
                            :options="$trainingSystems"
                            :value="old('training_system_id', $specialization->training_system_id)"
                            required
                            class="tom-select" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <x-form.input
                                name="name"
                                label="Tên ngành đào tạo"
                                :value="$specialization->name"
                                placeholder="Nhập tên ngành đào tạo..."
                                required />
                        </div>

                        {{-- Code --}}
                        <div>
                            <x-form.input
                                name="code"
                                label="Mã số (khóa chính)"
                                :value="$specialization->code"
                                placeholder="Ví dụ: A.6720101"
                                help="Khóa chính của hệ thống — không xuất hiện trên báo cáo; báo cáo dùng Mã ngành" />
                        </div>

                        <div>
                            <x-form.input
                                name="major_code"
                                label="Mã ngành (hiển thị/báo cáo)"
                                :value="$specialization->major_code"
                                placeholder="Ví dụ: 6720101"
                                required />
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-6">
                        <x-form.textarea
                            name="description"
                            label="Mô tả"
                            :value="$specialization->description"
                            placeholder="Nhập mô tả về ngành đào tạo..." />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- Level --}}
                        <div>
                            <x-form.select
                                name="level"
                                label="Trình độ"
                                :value="$specialization->level"
                                placeholder="Chọn trình độ"
                                :options="$levels"
                                required />
                        </div>

                        {{-- Duration --}}
                        <div>
                            <x-form.input
                                name="duration_months"
                                label="Thời gian đào tạo (tháng)"
                                type="number"
                                :value="$specialization->duration_months"
                                min="1"
                                max="120"
                                placeholder="Số tháng"
                                required />
                        </div>

                        <div>
                            <x-form.select
                                name="training_form"
                                label="Hình thức đào tạo"
                                :value="$specialization->training_form"
                                placeholder="Chọn hình thức"
                                :options="$trainingForms"
                                required />
                        </div>
                    </div>

                    {{-- Certification Type --}}
                    <div class="mb-6">
                        <x-form.select
                            name="certification_type"
                            label="Loại chứng chỉ"
                            :value="$specialization->certification_type"
                            placeholder="Chọn loại chứng chỉ"
                            :options="$certificationTypes"
                            required />
                    </div>

                    {{-- Prerequisites --}}
                    <div class="mb-6">
                        <x-prerequisites-manager :values="$specialization->prerequisites ?? []" />
                    </div>

                    {{-- Status --}}
                    <div class="mb-6">
                        <x-form.checkbox
                            name="is_active"
                            label="Đang hoạt động"
                            :checked="$specialization->is_active" />
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('specializations.show', $specialization) }}"
                           class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 font-medium">
                            <i class="bi bi-x mr-2"></i>Hủy
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <i class="bi bi-check mr-2"></i>Cập nhật
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Help Sidebar --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- Current Information --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-info-circle text-blue-500 mr-2"></i>
                    Thông tin hiện tại
                </h3>
            </div>
            <div class="p-4">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã ngành:</span>
                        <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $specialization->major_code }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã số:</span>
                        <code class="bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $specialization->code }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trình độ:</span>
                        <span class="font-medium">{{ $specialization->level_text }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Thời gian:</span>
                        <span class="font-medium">{{ $specialization->duration_months }} tháng</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Loại chứng chỉ:</span>
                        <span class="font-medium">{{ $specialization->certification_type_text }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trạng thái:</span>
                        <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $specialization->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $specialization->is_active ? 'Đang hoạt động' : 'Tạm dừng' }}
                        </span>
                    </div>
                </div>
                <div class="border-t pt-3 mt-3">
                    <div class="text-gray-500 mb-1">Cập nhật lần cuối:</div>
                    <div class="font-medium">{{ $specialization->updated_at->format('d/m/Y H:i') }}</div>
                    <div class="text-xs text-gray-500">{{ $specialization->updater->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Guidelines --}}
        <x-help-sidebar
            :tips="[
                'Kiểm tra kỹ thông tin trước khi lưu',
                'Mã ngành đào tạo phải duy nhất',
                'Thời gian đào tạo nên phù hợp với trình độ',
                'Có thể thêm/xóa điều kiện tiên quyết'
            ]"
            :warnings="[
                'Thay đổi có thể ảnh hưởng đến khóa học liên quan',
                'Tạm dừng sẽ ẩn khỏi danh sách chọn',
                'Không thể xóa nếu đã có khóa học'
            ]" />

        {{-- Actions --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-gear text-gray-500 mr-2"></i>
                    Thao tác khác
                </h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <a href="{{ route('specializations.show', $specialization) }}"
                       class="block w-full text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm font-medium">
                        <i class="bi bi-eye mr-2"></i>Xem chi tiết
                    </a>

                    <form method="POST"
                          action="{{ route('specializations.toggle-status', $specialization) }}"
                          data-confirm="Bạn có chắc chắn muốn {{ $specialization->is_active ? 'tạm dừng' : 'kích hoạt' }} ngành đào tạo này?">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="block w-full text-center px-3 py-2 bg-{{ $specialization->is_active ? 'red' : 'green' }}-100 text-{{ $specialization->is_active ? 'red' : 'green' }}-700 rounded-lg hover:bg-{{ $specialization->is_active ? 'red' : 'green' }}-200 text-sm font-medium">
                            <i class="bi bi-{{ $specialization->is_active ? 'pause' : 'play' }} mr-2"></i>
                            {{ $specialization->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                        </button>
                    </form>

                    <form method="POST"
                          action="{{ route('specializations.destroy', $specialization) }}"
                          data-confirm="Xóa ngành này sẽ xóa luôn TOÀN BỘ môn học và bài học thuộc ngành. Bạn có chắc chắn muốn xóa?"
                          data-confirm-danger="1"
                          data-confirm-ok="Xóa">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="block w-full text-center px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm font-medium">
                            <i class="bi bi-trash mr-2"></i>Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('specializationForm');
    form.addEventListener('submit', function(e) {
        // Remove empty prerequisites before submit
        const prerequisiteInputs = form.querySelectorAll('input[name="prerequisites[]"]');
        prerequisiteInputs.forEach(input => {
            if (!input.value.trim()) {
                input.remove();
            }
        });
    });

    // Real-time code validation (only if code changed)
    const codeInput = document.getElementById('code');
    const originalCode = @json($specialization->code);

    codeInput.addEventListener('blur', function() {
        const code = this.value.trim();
        if (code && code !== originalCode) {
            fetch('/specializations/api/check-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    code: code,
                    id: {{ (int) $specialization->id }}
                })
            })
            .then(response => response.json())
            .then(data => {
                const input = document.getElementById('code');
                const existingError = input.parentNode.querySelector('.text-red-600');

                if (data.exists) {
                    input.classList.add('border-red-300');
                    if (!existingError) {
                        const error = document.createElement('p');
                        error.className = 'mt-1 text-sm text-red-600';
                        error.textContent = 'Mã này đã tồn tại';
                        input.parentNode.appendChild(error);
                    }
                } else {
                    input.classList.remove('border-red-300');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });
        }
    });

    // Unsaved changes warning
    let formChanged = false;
    const formElements = form.querySelectorAll('input, select, textarea');

    formElements.forEach(element => {
        element.addEventListener('change', function() {
            formChanged = true;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    form.addEventListener('submit', function() {
        formChanged = false;
    });
});
</script>
@endpush
