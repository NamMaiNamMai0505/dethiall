@extends('layouts.admin')

@section('title', 'Sửa bộ môn')
@section('page-title', 'Sửa bộ môn')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
        ['title' => 'Bộ môn', 'url' => route('standard-hours.departments.index')],
        ['title' => $department->name, 'url' => route('standard-hours.departments.show', $department)],
        ['title' => 'Chỉnh sửa'],
    ]" />

    <x-page-header
        title="CHỈNH SỬA BỘ MÔN"
        :subtitle="$department->name"
        :actions="[
            [
                'url' => route('standard-hours.departments.show', $department),
                'label' => 'Chi tiết',
                'icon' => 'eye',
                'color' => 'gray',
            ],
            [
                'url' => route('standard-hours.departments.index'),
                'label' => 'Danh sách',
                'icon' => 'arrow-left',
                'color' => 'gray',
            ],
        ]"
    />

    <div class="max-w-xl bg-white rounded-xl border shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('standard-hours.departments.update', $department) }}">
            @csrf
            @method('PUT')
            <div class="p-6">
                @include('standardhours::departments._form', ['department' => $department, 'units' => $units])
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex justify-end gap-2">
                <a href="{{ route('standard-hours.departments.show', $department) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200
                          text-sm font-semibold text-slate-700 hover:bg-white transition">
                    Huỷ
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold shadow-sm transition">
                    <i class="bi bi-check-lg"></i> Cập nhật
                </button>
            </div>
        </form>

        @can('standard-hours.departments.manage')
            <div class="px-6 pb-5 border-t border-slate-50">
                <form method="POST" action="{{ route('standard-hours.departments.destroy', $department) }}"
                      data-confirm="Xóa bộ môn «{{ $department->name }}»? Giảng viên sẽ được gỡ gán khỏi BM này."
                      data-confirm-danger="1"
                      data-confirm-title="Xóa bộ môn"
                      class="pt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                                   text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition">
                        <i class="bi bi-trash"></i> Xóa bộ môn
                    </button>
                </form>
            </div>
        @endcan
    </div>
@endsection
