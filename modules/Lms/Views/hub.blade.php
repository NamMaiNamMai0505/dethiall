@extends('layouts.admin')

@section('title', 'LMS')
@section('page-title', 'LMS — Học tập')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS'],
    ]" />

    <x-page-header
        title="LMS HỌC TẬP"
        subtitle="Khóa học liên thông lịch đào tạo · nội dung học tập · điểm danh · đánh giá"
    />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-sm text-slate-600">Khóa học (phạm vi bạn)</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['courses'] }}</p>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-5 border-l-4 border-green-500">
            <p class="text-sm text-slate-600">Đang mở</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['published'] }}</p>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-5 border-l-4 border-indigo-500">
            <p class="text-sm text-slate-600">Bài học LMS</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['lessons'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @foreach($menu as $item)
            <a href="{{ route($item['route']) }}"
               class="group bg-white rounded-xl border shadow-sm hover:shadow-md p-5 flex flex-col
                      {{ !empty($item['primary']) ? 'border-blue-300 ring-1 ring-blue-100' : 'hover:border-blue-200' }}">
                <div class="w-11 h-11 rounded-lg {{ $item['iconBg'] }} flex items-center justify-center mb-3">
                    <i class="{{ $item['icon'] }} text-xl"></i>
                </div>
                <span class="font-semibold text-slate-900 group-hover:text-blue-700">{{ $item['label'] }}</span>
                <span class="text-sm text-slate-500 mt-1">{{ $item['desc'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="rounded-xl border border-amber-100 bg-amber-50/80 p-4 text-sm text-amber-950">
        <p class="font-semibold mb-1"><i class="bi bi-layers mr-1"></i> Tích hợp hệ thống hiện có (không làm lại)</p>
        <ul class="list-disc ml-5 space-y-0.5 text-amber-900/90">
            <li>Môn thuộc ngành được tự động ghi danh vào lớp; Khoa chỉ phân công giảng viên.</li>
            <li>Bài học LMS liên kết <code>subject_lessons</code>; nội dung bổ sung vẫn được giữ độc lập.</li>
            <li>Lịch đào tạo đồng bộ GV thực dạy, roster và buổi điểm danh vào lớp học phần tương ứng.</li>
        </ul>
    </div>
@endsection
