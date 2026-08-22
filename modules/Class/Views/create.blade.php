@extends('layouts.admin')

@section('title', 'Quản lý Lớp học')
@section('page-title', 'Quản lý Lớp học')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Lớp học', 'url' => route('classes.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO MỚI LỚP HỌC"
    :actions="[
        [
            'url' => route('classes.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('classes.store') }}" method="POST">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Thông tin cơ bản -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Hệ đào tạo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Hệ đào tạo <span class="text-red-500">*</span>
                        </label>
                        <div class="ui-select-field">
                            <select id="training_system_id" name="training_system_id" required
                                    data-searchable="1" data-placeholder="Chọn hệ...">
                                <option value="">Chọn hệ đào tạo...</option>
                                @foreach($trainingSystems ?? [] as $id => $name)
                                    <option value="{{ $id }}" @selected(old('training_system_id') == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Dân sự / Quân sự — chọn trước khi chọn ngành</p>
                    </div>
                    <!-- Ngành đào tạo (lọc theo hệ) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngành đào tạo <span class="text-red-500">*</span>
                        </label>
                        <div class="ui-select-field">
                            <select id="specialization_id" name="specialization_id" required
                                    data-searchable="1" data-placeholder="Chọn ngành (sau khi chọn hệ)...">
                                <option value="">Chọn ngành đào tạo...</option>
                                @foreach($specializations as $spec)
                                    <option value="{{ $spec->id }}"
                                            data-system="{{ $spec->training_system_id }}"
                                            @selected(old('specialization_id') == $spec->id)>
                                        {{ $spec->selection_label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Tên lớp -->
                    <div>
                        <x-form.input
                            label="Tên lớp"
                            name="name"
                            required
                            placeholder="Nhập tên lớp"
                            :value="old('name')"
                        />
                    </div>

                    <!-- Mã lớp -->
                    <div>
                        <x-form.input
                            label="Mã lớp"
                            name="code"
                            required
                            placeholder="Nhập mã lớp"
                            :value="old('code')"
                        />
                    </div>

                    <!-- Giảng viên phụ trách -->
                    <div>
                        <x-form.select
                            label="Giảng viên phụ trách"
                            name="instructor_id"
                            required
                            :options="$instructors->pluck('name', 'id')"
                            placeholder="Chọn Giảng viên phụ trách"
                            :value="old('instructor_id')"
                        />
                    </div>
                </div>
            </div>

            <!-- Thông tin thời gian -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin thời gian</h3>
                <div class="grid grid-cols-3 gap-4">
                    <!-- Thời gian đào tạo -->
                    <div>
                        <x-form.input
                            type="number"
                            label="Thời gian đào tạo (tháng)"
                            name="duration_months"
                            required
                            min="1"
                            :value="old('duration_months')"
                        />
                    </div>

                    <!-- Ngày bắt đầu -->
                    <div>
                        <x-form.input
                            type="date"
                            label="Ngày bắt đầu"
                            name="start_date"
                            required
                            :value="old('start_date')"
                        />
                    </div>

                    <!-- Ngày kết thúc -->
                    <div>
                        <x-form.input
                            type="date"
                            label="Ngày kết thúc"
                            name="end_date"
                            required
                            :value="old('end_date')"
                        />
                    </div>
                </div>
            </div>

            <!-- Thông tin bổ sung -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin bổ sung</h3>
                <div class="grid grid-cols-3 gap-4">
                    <!-- Đơn vị quản lý -->
                    <div>
                        <x-form.input
                            label="Đơn vị quản lý"
                            name="management_unit"
                            required
                            placeholder="Nhập đơn vị quản lý"
                            :value="old('management_unit')"
                        />
                    </div>

                    <!-- Quân số -->
                    <div>
                        <x-form.input
                            type="number"
                            label="Quân số"
                            name="max_students"
                            required
                            min="1"
                            :value="old('max_students', 30)"
                        />
                    </div>

                    <!-- Giảng đường -->
                    <div>
                        <x-form.select
                            label="Giảng đường"
                            name="classroom_id"
                            required
                            :options="$classrooms->pluck('name', 'id')"
                            placeholder="Chọn giảng đường"
                            :value="old('classroom_id')"
                        />
                    </div>
                </div>
            </div>

            <!-- Trạng thái -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Trạng thái</h3>
                <div>
                    <x-form.checkbox
                        label="Hoạt động"
                        name="is_active"
                        :checked="old('is_active', true)"
                    />
                </div>
            </div>

            <!-- Mô tả -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Mô tả</h3>
                <div>
                    <x-form.textarea
                        name="description"
                        placeholder="Nhập mô tả cho lớp học"
                        :value="old('description')"
                        rows="4"
                    />
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end space-x-2">
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-lg mr-2"></i>Hủy
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save mr-2"></i>Lưu lớp học
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('partials.cascade-training-system', [
    'specializationsBySystem' => $specializationsBySystem ?? [],
    'initialSystemId' => old('training_system_id'),
    'initialSpecId' => old('specialization_id'),
])
@endpush
