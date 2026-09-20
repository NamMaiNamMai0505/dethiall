@section('title', 'Cổng thông tin nghiên cứu khoa học')
@section('page-title', 'Cổng thông tin nghiên cứu khoa học')
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<x-breadcrumb :items="[['title' => 'Trang chủ'], ['title' => 'Nghiên cứu khoa học', 'url' => route('scientific-research.index')], ['title' => 'Cổng thông tin']]" />
<x-page-header title="CỔNG THÔNG TIN NGHIÊN CỨU KHOA HỌC" subtitle="Tra cứu thông báo, đề tài cá nhân, sản phẩm và kho dữ liệu nghiên cứu" />
@include('partials.module-menu', ['module' => 'scientific-research'])
@include('scientific-research::partials.ui-style')

<div class="sr-page">
<div class="mb-5 grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
    <form method="GET" action="{{ route('scientific-research.portal') }}" class="sr-panel p-4">
        <label class="text-sm font-semibold text-slate-700">Tìm sản phẩm hoặc tài liệu
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input name="q" value="{{ $keyword }}" placeholder="Nhập tên sản phẩm, tác giả, từ khóa..." class="min-w-0 flex-1 rounded-lg border px-3 py-2.5">
                <button class="sr-action bg-blue-600 px-5 py-2.5 text-white"><i class="bi bi-search"></i> Tìm kiếm</button>
            </div>
        </label>
    </form>
    <div class="flex items-stretch gap-2">
        <a href="{{ route('scientific-research.registrations.create') }}" class="sr-action bg-slate-900 px-5 py-3 text-white"><i class="bi bi-plus-lg"></i> Đăng ký đề tài</a>
    </div>
</div>

<div class="grid min-w-0 gap-5 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold text-slate-900">Thông báo đang mở</h2>
            <a href="{{ route('scientific-research.announcements.index') }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-list-ul"></i> Xem tất cả</a>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            @forelse($openAnnouncements as $announcement)
                <article class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">{{ $announcement->title }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Hạn: {{ $announcement->closes_at?->format('d/m/Y H:i') ?: 'Không giới hạn' }}</p>
                    @if($announcement->content)
                        <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $announcement->content }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a href="{{ route('scientific-research.registrations.create', ['announcement_id' => $announcement->id]) }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-plus-lg"></i> Đăng ký</a>
                        @if($announcement->template_path)
                            <a href="{{ asset('storage/'.$announcement->template_path) }}" class="sr-action sr-action-sm border border-slate-200 bg-white text-slate-700"><i class="bi bi-download"></i> Tải biểu mẫu</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 md:col-span-2">Chưa có thông báo đang mở.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold text-slate-900">Đề tài của tôi</h2>
            <a href="{{ route('scientific-research.registrations.index', ['mine' => 1]) }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-folder2-open"></i> Mở danh sách</a>
        </div>
        <div class="space-y-3">
            @forelse($myRegistrations as $registration)
                <a href="{{ route('scientific-research.registrations.show', $registration) }}" class="block rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-bold text-slate-900">{{ $registration->title }}</p>
                        <span class="sr-badge shrink-0 bg-blue-50 text-blue-700">{{ $statuses[$registration->status] ?? $registration->status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $registration->project_code ?: 'Chưa cấp mã' }} · {{ $registration->researchCategory?->code }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, max(0, (int) $registration->progress_percent)) }}%"></div>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Bạn chưa có đề tài NCKH.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="mt-5 grid min-w-0 gap-5 xl:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold text-slate-900">Sản phẩm khoa học</h2>
            <a href="{{ route('scientific-research.products.index') }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-pencil-square"></i> Quản lý</a>
        </div>
        <div class="space-y-3">
            @forelse($products as $product)
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-bold text-slate-900">{{ $product->title }}</h3>
                        <span class="sr-badge bg-emerald-50 text-emerald-700">{{ $product->type }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $product->authors ?: 'Chưa có tác giả' }} @if($product->published_year) · {{ $product->published_year }} @endif</p>
                    @if($product->registration)
                        <p class="mt-2 text-sm text-slate-600">{{ $product->registration->project_code }} · {{ $product->registration->title }}</p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Chưa có sản phẩm phù hợp.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-extrabold text-slate-900">Kho dữ liệu</h2>
            <a href="{{ route('scientific-research.repository.index') }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-pencil-square"></i> Quản lý</a>
        </div>
        <div class="space-y-3">
            @forelse($documents as $document)
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-bold text-slate-900">{{ $document->title }}</h3>
                        <span class="sr-badge bg-slate-100 text-slate-700">{{ $document->document_type }}</span>
                    </div>
                    @if($document->summary)
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $document->summary }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        @if($document->keywords)
                            <span class="text-slate-500">{{ $document->keywords }}</span>
                        @endif
                        @if($document->file_path)
                            <a href="{{ asset('storage/'.$document->file_path) }}" class="font-bold text-blue-700 hover:underline">{{ $document->file_name ?: 'Tải tệp' }}</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Chưa có tài liệu phù hợp.</div>
            @endforelse
        </div>
    </section>
</div>
</div>
</div>
@endsection
