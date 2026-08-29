@extends('layouts.admin')

@section('title', 'Tạo phòng học mới')
@section('page-title', 'Tạo phòng học mới')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'phòng học', 'url' => route('classrooms.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO PHÒNG HỌC MỚI"
    :actions="[
        [
            'url' => route('classrooms.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

{{-- Form --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <form action="{{ route('classrooms.store') }}" method="POST" class="space-y-6">
            @csrf
            {{-- building_id --}}
            <div>
                <label for="building_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Giảng đường
                </label>
                <select id="building_id" 
                        name="building_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" 
                            data-name="{{ $building->name }}"
                            {{ old('building_id') == $building->id ? 'selected' : '' }}>
                            {{ $building->name }}
                        </option>
                    @endforeach
                </select>
                @error('building_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            {{-- Name Field --}}
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Mã phòng</label>
                    <input id="code" name="code" value="{{ old('code') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Ví dụ: P101">
                </div>
                <div>
                    <label for="room_type" class="block text-sm font-medium text-gray-700 mb-2">Loại phòng</label>
                    <input id="room_type" name="room_type" value="{{ old('room_type') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Phòng học, phòng làm việc...">
                </div>
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Tên phòng học <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                    placeholder="Chọn giảng đường trước..."
                    required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div><label for="floor" class="block text-sm font-medium text-gray-700 mb-2">Tầng</label><input id="floor" name="floor" value="{{ old('floor') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>
                <div><label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">Sức chứa</label><input id="capacity" type="number" min="0" name="capacity" value="{{ old('capacity') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>
                <div><label for="managing_unit_id" class="block text-sm font-medium text-gray-700 mb-2">Đơn vị quản lý</label><select id="managing_unit_id" name="managing_unit_id" class="w-full px-3 py-2 border border-gray-300 rounded-md"><option value="">Chọn đơn vị</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div>
            </div>
            <div><label for="description" class="block text-sm font-medium text-gray-700 mb-2">Mô tả phòng</label><textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md">{{ old('description') }}</textarea></div>

            {{-- Status Field --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Trạng thái
                </label>
                <select id="status" 
                        name="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Ngừng hoạt động</option>
                    <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Hoạt động</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            

            {{-- Submit Buttons --}}
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('classrooms.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Hủy bỏ
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="bi bi-save mr-1"></i>
                    Tạo phòng học
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buildingSelect = document.getElementById('building_id');
        const nameInput = document.getElementById('name');

        function updatePlaceholder() {
            const selected = buildingSelect.options[buildingSelect.selectedIndex];
            if (selected && selected.dataset.name) {
                nameInput.placeholder = "Ví dụ: " + selected.dataset.name + ".101";
            } else {
                nameInput.placeholder = "Nhập tên phòng học";
            }
        }

        // update ngay khi load
        updatePlaceholder();

        // update khi change
        buildingSelect.addEventListener('change', updatePlaceholder);
    });
</script>
@endsection
