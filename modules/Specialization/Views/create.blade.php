@extends('layouts.admin')

@section('title', 'Thêm Ngành đào tạo mới')
@section('page-title', 'Thêm Ngành đào tạo mới')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Ngành đào tạo', 'url' => route('specializations.index')],
    ['title' => 'Thêm mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="THÊM NGÀNH ĐÀO TẠO MỚI"
    subtitle="Tạo một ngành đào tạo mới cho hệ thống"
    :actions="[
        [
            'url' => route('specializations.index'),
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
                <form action="{{ route('specializations.store') }}" method="POST" id="specializationForm">
                    @csrf

                    <div class="mb-6">
                        <x-form.select
                            name="training_system_id"
                            label="Hệ đào tạo"
                            placeholder="Chọn hệ (Dân sự / Quân sự…)"
                            :options="$trainingSystems"
                            :value="old('training_system_id')"
                            required
                            class="tom-select" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <x-form.input
                                name="name"
                                label="Tên ngành đào tạo"
                                placeholder="Nhập tên ngành đào tạo..."
                                required />
                        </div>

                        {{-- Code --}}
                        <div>
                            <x-form.input
                                name="code"
                                label="Mã số (khóa chính)"
                                placeholder="Để trống để hệ thống tự sinh"
                                help="Khóa chính của hệ thống — không xuất hiện trên báo cáo; báo cáo dùng Mã ngành" />
                        </div>

                        <div>
                            <x-form.input
                                name="major_code"
                                label="Mã ngành (hiển thị/báo cáo)"
                                placeholder="Ví dụ: 6720101"
                                required />
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-6">
                        <x-form.textarea
                            name="description"
                            label="Mô tả"
                            placeholder="Nhập mô tả về ngành đào tạo..." />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- Level --}}
                        <div>
                            <x-form.select
                                name="level"
                                label="Trình độ"
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
                                min="1"
                                max="120"
                                placeholder="Số tháng"
                                required />
                        </div>

                        <div>
                            <x-form.select
                                name="training_form"
                                label="Hình thức đào tạo"
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
                            placeholder="Chọn loại chứng chỉ"
                            :options="$certificationTypes"
                            required />
                    </div>

                    {{-- Prerequisites --}}
                    <div class="mb-6">
                        <x-prerequisites-manager />
                    </div>

                    {{-- Status --}}
                    <div class="mb-6">
                        <x-form.checkbox
                            name="is_active"
                            label="Kích hoạt ngay sau khi tạo"
                            :checked="true" />
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('specializations.index') }}"
                           class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 font-medium">
                            <i class="bi bi-x mr-2"></i>Hủy
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <i class="bi bi-check mr-2"></i>Lưu
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Help Sidebar --}}
    <div class="lg:col-span-1 space-y-6">
        <x-help-sidebar
            :tips="[
                'Tên ngành đào tạo nên rõ ràng, dễ hiểu',
                'Mã sẽ tự động tạo từ tên nếu không nhập',
                'Cấp độ giúp phân loại độ khó',
                'Thời gian đào tạo tính bằng tháng',
                'Điều kiện tiên quyết có thể để trống'
            ]"
            :warnings="[
                'Các trường có dấu * là bắt buộc',
                'Mã ngành đào tạo phải duy nhất',
                'Chỉ được dùng chữ cái, số, gạch ngang cho mã'
            ]" />

        {{-- Level Guide --}}
        <x-level-guide />
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto generate code from name
    const nameInput = document.getElementById('name');
    const codeInput = document.getElementById('code');

    nameInput.addEventListener('input', function() {
        if (!codeInput.value) {
            const name = this.value;
            const code = name
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // Remove accents
                .replace(/[^a-zA-Z0-9\s]/g, '') // Remove special chars except spaces
                .replace(/\s+/g, '') // Remove spaces
                .toUpperCase()
                .substring(0, 10); // Limit to 10 chars
            codeInput.value = code;
        }
    });

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

    // Real-time code validation
    codeInput.addEventListener('blur', function() {
        const code = this.value.trim();
        if (code) {
            fetch('/specializations/api/check-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ code: code })
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
});
</script>
@endpush
