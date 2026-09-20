@section('title', 'Đăng ký nghiên cứu khoa học')
@section('page-title', 'Đăng ký nghiên cứu khoa học')
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<x-breadcrumb :items="[['title' => 'Trang chủ'], ['title' => 'Nghiên cứu khoa học', 'url' => route('scientific-research.index')], ['title' => 'Đăng ký']]" />
<x-page-header title="ĐĂNG KÝ NGHIÊN CỨU KHOA HỌC" subtitle="Danh mục NCKH được lấy sẵn từ phân hệ Giờ chuẩn GV" />
@include('partials.module-menu', ['module' => 'scientific-research'])
@include('scientific-research::partials.ui-style')

<div class="sr-page">
@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif
@php
    $initialScientificResearchMembers = collect(old('member_user_ids', []))->values()->map(function ($userId, $index) {
        return [
            'user_id' => $userId,
            'role' => old("member_roles.$index", 'Thành viên'),
            'percent' => old("member_contribution_percents.$index", 0),
        ];
    })->filter(fn ($row) => filled($row['user_id']) || filled($row['role']) || (float) $row['percent'] > 0)->values();
@endphp

<form method="POST" action="{{ route('scientific-research.registrations.store') }}" enctype="multipart/form-data" class="sr-panel p-5">
    @csrf
    <div class="grid min-w-0 gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 md:col-span-2">
            <p class="font-bold text-blue-900">Nơi tiếp nhận hồ sơ</p>
            <p class="mt-1 text-sm text-blue-800">Sau khi bấm “Gửi đăng ký”, hồ sơ chuyển đến Chỉ huy đơn vị quản lý để duyệt bước 1; hồ sơ được duyệt sẽ chuyển tiếp đến Ban Khoa học Quân sự để tiếp nhận/thẩm định nội dung.</p>
        </div>

        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đợt thông báo NCKH
            <select name="announcement_id" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                <option value="">Không gắn với đợt thông báo</option>
                @foreach($openAnnouncements as $announcement)
                    <option value="{{ $announcement->id }}" @selected((string) request('announcement_id') === (string) $announcement->id)>{{ $announcement->title }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">Dùng khi đề tài đăng ký theo một thông báo/đợt mở đăng ký NCKH cụ thể.</span>
        </label>

        <div class="md:col-span-2">
            <p class="mb-1 text-sm font-semibold text-slate-700">Chọn danh mục NCKH từ Giờ chuẩn GV <span class="text-rose-500">*</span></p>
            <p class="mb-2 text-xs text-slate-500">Không nhập danh mục tại đây. Muốn thêm/sửa danh mục thì thực hiện bên Giờ chuẩn GV.</p>
            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($categories as $category)
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300 hover:shadow-sm">
                        <input type="radio" name="research_category_id" value="{{ $category->id }}" required class="mt-1" @checked((string) old('research_category_id') === (string) $category->id)>
                        <span>
                            <span class="block font-bold text-slate-900">{{ $category->code }} — {{ $category->name }}</span>
                            <span class="text-xs text-slate-500">{{ $category->formatted_research_hours }} · {{ $category->unit ?: 'Sản phẩm' }}</span>
                        </span>
                    </label>
                @empty
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800 md:col-span-2 xl:col-span-3">Chưa có danh mục NCKH đang sử dụng bên Giờ chuẩn GV.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 md:col-span-2">
            <p class="text-sm font-semibold text-slate-700">Chủ nhiệm đề tài</p>
            <p class="mt-1 font-bold text-slate-900">{{ auth()->user()?->name ?: 'Tài khoản hiện tại' }}</p>
            <p class="mt-1 text-xs text-slate-500">Hệ thống tự lấy theo tài khoản đang đăng ký, không nhập riêng tại biểu mẫu này.</p>
        </div>

        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên đề tài / tên sản phẩm <span class="text-rose-500">*</span><input name="title" value="{{ old('title') }}" required placeholder="Nhập tên đề tài hoặc tên sản phẩm nghiên cứu" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Chủ đề/lĩnh vực đề tài<input name="topic" value="{{ old('topic') }}" placeholder="VD: Chuyển đổi số trong đào tạo, điều dưỡng, y học cơ sở..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Nội dung đăng ký<textarea name="content" rows="4" class="mt-1 w-full rounded-lg border px-3 py-2.5">{{ old('content') }}</textarea></label>
        <label class="text-sm font-semibold text-slate-700">Năm học<input name="academic_year" placeholder="VD: 2026-2027" value="{{ old('academic_year') }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Năm thực hiện<input name="implementation_year" type="number" min="2000" max="2200" value="{{ old('implementation_year', now()->year) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Từ ngày<input name="start_date" type="date" value="{{ old('start_date') }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Đến ngày<input name="end_date" type="date" value="{{ old('end_date') }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Số năm thực hiện<input name="duration_years" type="number" min="0.25" max="20" step="0.25" value="{{ old('duration_years', 1) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Số lượng sản phẩm<input name="product_quantity" type="number" min="1" max="999" value="{{ old('product_quantity', 1) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Kinh phí<input name="budget" type="number" min="0" step="1000" value="{{ old('budget', 0) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Tổng số người tham gia <span class="text-xs font-normal text-slate-500">(bao gồm chủ nhiệm)</span><input name="participant_count" type="number" min="1" value="{{ old('participant_count', 1) }}" required class="mt-1 w-full rounded-lg border px-3 py-2.5" data-sr-participant-count></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tỷ lệ đóng góp của chủ nhiệm (%)
            <input name="lead_contribution_percent" type="number" min="0" max="100" step="0.01" value="{{ old('lead_contribution_percent', 100) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5">
        </label>

        <div class="md:col-span-2 rounded-lg border border-slate-200 bg-white p-4" data-sr-members-block data-sr-initial-members="{{ $initialScientificResearchMembers->toJson() }}">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-slate-900">Thành viên tham gia đề tài</p>
                    <p class="text-xs text-slate-500">Chủ nhiệm đề tài chọn thành viên tham gia ngay trong hồ sơ đề tài.</p>
                </div>
                <span class="sr-badge bg-slate-100 text-slate-700" data-sr-member-count-label>0 thành viên</span>
            </div>
            <div class="grid gap-3" data-sr-members-list></div>
            <div class="hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500" data-sr-members-empty>
                Chỉ có chủ nhiệm đề tài, không cần nhập đồng tác giả.
            </div>
        </div>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tệp đăng ký/báo cáo ban đầu<input name="files[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
    </div>
    <div class="mt-5 flex justify-end gap-2">
        <a href="{{ route('scientific-research.index') }}" class="sr-action border px-4 py-2.5 text-slate-700"><i class="bi bi-x-lg"></i> Hủy</a>
        <button class="sr-action bg-blue-600 px-5 py-2.5 text-white"><i class="bi bi-send"></i> Gửi đăng ký</button>
    </div>
</form>
</div>

<template id="sr-member-row-template">
    <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[48px_minmax(0,2fr)_minmax(0,1fr)_160px]">
        <div class="flex h-10 items-center justify-center self-end rounded-lg bg-white text-sm font-extrabold text-slate-600" data-sr-member-index></div>
        <label class="text-xs font-bold text-slate-600">Giảng viên đồng tác giả
            <select name="member_user_ids[]" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" data-sr-member-user>
                <option value="">Chọn thành viên</option>
                @foreach($internalUsers as $memberUser)
                    <option value="{{ $memberUser->id }}">
                        {{ $memberUser->name }}{{ $memberUser->code ? ' - '.$memberUser->code : '' }}{{ $memberUser->unit?->name ? ' - '.$memberUser->unit->name : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-bold text-slate-600">Vai trò trong đề tài
            <input name="member_roles[]" placeholder="VD: Thành viên" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" data-sr-member-role>
        </label>
        <label class="text-xs font-bold text-slate-600">Tỷ lệ đóng góp (%)
            <input name="member_contribution_percents[]" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" placeholder="VD: 20" data-sr-member-percent>
        </label>
    </div>
</template>

</div>
@endsection
