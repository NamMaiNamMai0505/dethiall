@extends('layouts.admin')

@section('title', 'Chi tiết lớp học')
@section('page-title', 'Chi tiết lớp học')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Lớp học', 'url' => route('classes.index')],
            ['title' => $class->name]
        ]" />

    {{-- Page Header --}}
    <x-page-header title="CHI TIẾT LỚP HỌC" :actions="[
            [
                'url' => route('classes.edit', $class),
                'label' => 'Chỉnh sửa',
                'icon' => 'pencil',
                'color' => 'blue'
            ],
            [
                'url' => route('classes.index'),
                'label' => 'Quay lại',
                'icon' => 'arrow-left',
                'color' => 'gray'
            ]
        ]" />

    {{-- Thông tin tổng quan --}}
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <div>
                        <h2 class="text-2xl font-bold">{{ $class->name }}</h2>
                        <p class="text-blue-100 text-sm mt-1">{{ $class->specialization->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <code class="bg-white/20 backdrop-blur-sm text-white px-3 py-1.5 rounded-lg text-sm font-mono">
                        {{ $class->code }}
                    </code>
                    <x-status-badge :is-active="$class->is_active" />
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="flex gap-4">
                <div class="text-center bg-white/10 backdrop-blur-sm rounded-lg px-6 py-4">
                    <div class="text-3xl font-bold">{{ $class->current_students }}</div>
                    <div class="text-xs text-blue-100 mt-1">Học viên</div>
                </div>
                <div class="text-center bg-white/10 backdrop-blur-sm rounded-lg px-6 py-4">
                    <div class="text-3xl font-bold">{{ $class->duration_months }}</div>
                    <div class="text-xs text-blue-100 mt-1">Tháng</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content - 2 columns --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Thông tin cơ bản --}}
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Thông tin cơ bản</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="text-sm font-medium text-gray-500 block mb-1">Tên lớp</label>
                                <p class="text-gray-900 font-medium">{{ $class->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="text-sm font-medium text-gray-500 block mb-1">Mã lớp</label>
                                <code
                                    class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm font-mono">{{ $class->code }}</code>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="text-sm font-medium text-gray-500 block mb-1">Ngành đào tạo</label>
                                <p class="text-gray-900 font-medium">{{ $class->specialization->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="text-sm font-medium text-gray-500 block mb-1">Giảng viên phụ trách</label>
                                <p class="text-gray-900 font-medium">{{ $class->instructor->name }}</p>
                            </div>
                        </div>

                        {{-- <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="text-sm font-medium text-gray-500 block mb-1">Giảng đường</label>
                                <p class="text-gray-900 font-medium">{{ $class->classroom->name ?? 'Chưa có' }}</p>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>

            {{-- Thông tin học viên --}}
            {{-- <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Thông tin học viên</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="text-3xl font-bold text-blue-600">{{ $class->max_students }}</div>
                            <div class="text-sm text-gray-600 mt-1">Sức chứa tối đa</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="text-3xl font-bold text-green-600">{{ $class->current_students }}</div>
                            <div class="text-sm text-gray-600 mt-1">Đang học</div>
                        </div>
                        <div class="text-center p-4 bg-orange-50 rounded-lg border border-orange-200">
                            <div class="text-3xl font-bold text-orange-600">
                                {{ $class->max_students - $class->current_students }}</div>
                            <div class="text-sm text-gray-600 mt-1">Còn trống</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-gray-700">Tỷ lệ lấp đầy</span>
                            <span
                                class="font-bold text-blue-600">{{ number_format(($class->current_students / $class->max_students) * 100, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500"
                                style="width: {{ ($class->current_students / $class->max_students) * 100 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            {{-- Mô tả --}}
            @if($class->description)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-800">Mô tả</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="prose max-w-none text-gray-700 leading-relaxed">
                            {{ $class->description }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar - 1 column --}}
        <div class="space-y-6">
            {{-- Thông tin thời gian --}}
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 px-6 py-4 border-b">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800">Thời gian</h3>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <label class="text-sm font-medium text-gray-500 block mb-1">Ngày bắt đầu</label>
                            <p class="text-gray-900 font-semibold">{{ $class->start_date->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <label class="text-sm font-medium text-gray-500 block mb-1">Ngày kết thúc</label>
                            <p class="text-gray-900 font-semibold">{{ $class->end_date->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <label class="text-sm font-medium text-gray-500 block mb-1">Thời gian đào tạo</label>
                            <p class="text-gray-900 font-semibold">{{ $class->duration_months }} tháng</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Thông tin quản lý --}}
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-info text-gray-500 mr-2"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Thông tin hệ thống</h3>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 block mb-1">Người tạo</label>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <span
                                    class="text-blue-600 font-semibold text-sm">{{ substr($class->creator->name, 0, 1) }}</span>
                            </div>
                            <span class="text-gray-900 font-medium">{{ $class->creator->name }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500 block mb-1">Cập nhật cuối</label>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <span
                                    class="text-green-600 font-semibold text-sm">{{ substr($class->updater->name, 0, 1) }}</span>
                            </div>
                            <span class="text-gray-900 font-medium">{{ $class->updater->name }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Thời gian tạo</span>
                            <span class="text-gray-700 font-medium">{{ $class->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Cập nhật lần cuối</span>
                            <span class="text-gray-700 font-medium">{{ $class->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection