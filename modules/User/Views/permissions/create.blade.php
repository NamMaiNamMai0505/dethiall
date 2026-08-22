@extends('layouts.admin')

@section('title', 'Tạo mới Quyền')
@section('page-title', 'Tạo mới Quyền')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quyền', 'url' => route('permissions.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO MỚI QUYỀN"
    :actions="[
        [
            'url' => route('permissions.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('permissions.store') }}" method="POST">
        @csrf
        <div class="max-w-2xl">
            {{-- Name --}}
            <div class="mb-6">
                <label for="name" class="block font-medium mb-2">Tên quyền <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="form-input w-full @error('name') border-red-500 @enderror"
                       placeholder="Nhập tên quyền">
                @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
            </div>

            {{-- Roles --}}
            <div class="mb-6">
                <label class="block font-medium mb-2">Gán cho vai trò</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-64 overflow-y-auto border rounded p-3">
                    @foreach($roles as $id => $label)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="roles[]" value="{{ $id }}"
                                   class="form-checkbox h-5 w-5 text-blue-600"
                                   {{ in_array($id, old('roles', [])) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('roles')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[120px]">Lưu</button>
        </div>
    </form>
</div>
@endsection

