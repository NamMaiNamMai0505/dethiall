@extends('layouts.admin')

@section('title', 'Thêm bộ môn')
@section('page-title', 'Thêm bộ môn')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
        ['title' => 'Bộ môn', 'url' => route('standard-hours.departments.index')],
        ['title' => 'Thêm mới'],
    ]" />

    <x-page-header
        title="THÊM BỘ MÔN"
        subtitle="Bộ môn thuộc một khoa — sau khi tạo sẽ gán giảng viên trên trang chi tiết."
        :actions="[
            [
                'url' => route('standard-hours.departments.index'),
                'label' => 'Quay lại',
                'icon' => 'arrow-left',
                'color' => 'gray',
            ],
        ]"
    />

    <div class="max-w-xl bg-white rounded-xl border shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('standard-hours.departments.store') }}">
            @csrf
            <div class="p-6">
                @include('standardhours::departments._form', ['department' => null, 'units' => $units])
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex justify-end gap-2">
                <a href="{{ route('standard-hours.departments.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200
                          text-sm font-semibold text-slate-700 hover:bg-white transition">
                    Huỷ
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold shadow-sm transition">
                    <i class="bi bi-check-lg"></i> Lưu bộ môn
                </button>
            </div>
        </form>
    </div>
@endsection
