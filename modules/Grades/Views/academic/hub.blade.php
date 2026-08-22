@extends('layouts.grades')
@section('title', 'Tổng kết · Tốt nghiệp')

@section('content')
<div class="mb-6">
    <p class="text-xs font-bold uppercase tracking-wide text-orange-600/80 mb-1">Quản lý điểm</p>
    <h1 class="text-2xl font-bold text-slate-900">Tổng kết năm học &amp; kết quả tốt nghiệp</h1>
    <p class="text-sm text-slate-500 mt-1">
        Vai trò hiện tại:
        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-gradient-to-r from-orange-100 to-teal-100 text-teal-900 border border-orange-200">
            {{ $roleLabel }}
        </span>
    </p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="grades-card p-3 text-center">
        <p class="text-[10px] font-bold uppercase text-slate-400">Sửa nghiệp vụ</p>
        <p class="text-sm font-bold {{ $caps['edit'] ? 'text-teal-700' : 'text-slate-300' }}">{{ $caps['edit'] ? 'Có' : 'Không' }}</p>
    </div>
    <div class="grades-card p-3 text-center">
        <p class="text-[10px] font-bold uppercase text-slate-400">Phê duyệt</p>
        <p class="text-sm font-bold {{ $caps['approve'] ? 'text-orange-700' : 'text-slate-300' }}">{{ $caps['approve'] ? 'Có' : 'Không' }}</p>
    </div>
    <div class="grades-card p-3 text-center">
        <p class="text-[10px] font-bold uppercase text-slate-400">Vào điểm</p>
        <p class="text-sm font-bold {{ $caps['enter_scores'] ? 'text-teal-700' : 'text-slate-300' }}">{{ $caps['enter_scores'] ? 'Có' : 'Không' }}</p>
    </div>
    <div class="grades-card p-3 text-center">
        <p class="text-[10px] font-bold uppercase text-slate-400">In văn bằng</p>
        <p class="text-sm font-bold {{ $caps['print_diploma'] ? 'text-orange-700' : 'text-slate-300' }}">{{ $caps['print_diploma'] ? 'Có' : 'Không' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    @foreach($cards as $c)
        @continue(!($c['show'] ?? true))
        <a href="{{ route($c['route']) }}"
           class="grades-card p-5 block hover:border-orange-300 hover:shadow-md transition border border-transparent no-underline text-inherit">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-teal-600 text-white text-lg shrink-0">
                    <i class="bi {{ $c['icon'] }}"></i>
                </span>
                <div class="min-w-0">
                    <div class="font-bold text-slate-900">{{ $c['title'] }}</div>
                    <p class="text-sm text-slate-500 mt-1">{{ $c['desc'] }}</p>
                    <div class="mt-3 text-sm font-semibold text-teal-700">Mở →</div>
                </div>
            </div>
        </a>
    @endforeach
</div>
@endsection
