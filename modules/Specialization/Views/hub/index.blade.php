@extends('layouts.admin')

@section('title', 'Ngành đào tạo')
@section('page-title', 'Ngành đào tạo')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Ngành đào tạo']
    ]"/>

    <x-page-header
        title="NGÀNH ĐÀO TẠO"
        subtitle="Chương trình đào tạo theo bốn cấp: Hệ đào tạo → Ngành đào tạo → Môn học → Bài học"
    />

    <div class="mb-6 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold text-slate-900">Phạm vi tài khoản:</span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-slate-800">{{ $scopeLabel ?? '—' }}</span>
        @if(!empty($user?->unit?->name))
            <span class="text-slate-500">
                Đơn vị: <strong>{{ $user->unit->name }}</strong>@if($user->unit->code) ({{ $user->unit->code }})@endif
            </span>
        @endif
    </div>

    {{-- Chuỗi bốn cấp: nhìn thấy ngay quan hệ cha–con của chương trình đào tạo --}}
    <div class="mb-8 overflow-x-auto">
        <div class="flex min-w-max items-center gap-2 text-sm">
            {{-- Class viết đủ chuỗi để Tailwind JIT quét được (không ghép động) --}}
            @foreach([
                ['label' => 'Hệ đào tạo', 'count' => $stats['training_systems'], 'box' => 'border-teal-200 bg-teal-50', 'text' => 'text-teal-700'],
                ['label' => 'Ngành đào tạo', 'count' => $stats['specializations'], 'box' => 'border-blue-200 bg-blue-50', 'text' => 'text-blue-700'],
                ['label' => 'Môn học', 'count' => $stats['subjects'], 'box' => 'border-purple-200 bg-purple-50', 'text' => 'text-purple-700'],
                ['label' => 'Bài học', 'count' => $stats['lessons'], 'box' => 'border-amber-200 bg-amber-50', 'text' => 'text-amber-800'],
            ] as $level)
                @if(!$loop->first)
                    <i class="bi bi-chevron-right text-slate-300"></i>
                @endif
                <div class="rounded-xl border {{ $level['box'] }} px-4 py-2">
                    <div class="text-xs font-semibold uppercase tracking-wide {{ $level['text'] }}">{{ $level['label'] }}</div>
                    <div class="text-xl font-bold text-slate-900">{{ number_format($level['count']) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Chức năng</h2>
        <p class="text-sm text-gray-500">
            Chọn cấp cần quản lý. Ở mỗi cấp, bộ lọc bám theo cấp trên — chọn Hệ thì Ngành lọc theo Hệ, chọn Ngành thì Môn lọc theo Ngành.
        </p>
    </div>

    <div class="mb-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($menuItems as $item)
            <a href="{{ route($item['route']) }}"
               class="group flex flex-col rounded-xl border bg-white p-5 shadow-sm transition hover:shadow-md
                      {{ !empty($item['primary']) ? 'border-blue-300 ring-1 ring-blue-100 hover:border-blue-400' : 'hover:border-blue-200' }}">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $item['iconBg'] }}">
                        <i class="{{ $item['icon'] }} text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-slate-300 group-hover:text-slate-400">{{ number_format($item['count']) }}</span>
                </div>
                <span class="font-semibold text-gray-900 group-hover:text-blue-700">{{ $item['label'] }}</span>
                <span class="mt-1 text-sm leading-5 text-gray-500">{{ $item['desc'] }}</span>
            </a>
        @endforeach
    </div>
@endsection
