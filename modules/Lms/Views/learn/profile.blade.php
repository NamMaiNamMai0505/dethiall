@extends('layouts.lms-learner')

@section('title', 'Thông tin của tôi')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Thông tin của tôi</h1>
    @php
        $isGv = $user->isInstructor() || \Modules\Lms\Support\LmsAccess::isInstructorUser($user);
        $inst = $user->instructor;
    @endphp
    <p class="text-sm text-slate-500 mt-1">
        {{ $isGv ? 'Hồ sơ giảng viên trên cổng LMS' : 'Hồ sơ học viên trên cổng LMS' }} — không cần vào Dashboard quản trị.
    </p>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lms-card p-6 lg:col-span-1 text-center">
        <div class="mx-auto h-20 w-20 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-3xl text-teal-700 mb-3">
            <i class="bi bi-person"></i>
        </div>
        <div class="font-bold text-lg text-slate-900">{{ $user->name }}</div>
        <div class="text-sm text-slate-500 mt-1">{{ $user->email }}</div>
        <div class="mt-3 inline-flex lms-chip lms-chip-teal">
            {{ $user->isStudent() ? 'Học viên' : ($isGv ? 'Giảng viên' : 'Tài khoản') }}
        </div>
    </div>

    <div class="lms-card p-6 lg:col-span-2 space-y-4">
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
            @if($isGv)
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Mã GV</div>
                    <div class="font-semibold text-slate-800 font-mono">{{ $inst->code ?? $user->code ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Đơn vị / Khoa</div>
                    <div class="font-semibold text-slate-800">{{ $inst->unit->name ?? $user->unit->name ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Điện thoại</div>
                    <div class="font-semibold text-slate-800">{{ $inst->phone ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Trạng thái hồ sơ GV</div>
                    <div class="font-semibold text-emerald-700">{{ $inst->status ?? ((int)($user->status ?? 0) === 1 ? 'active' : '—') }}</div>
                </div>
            @else
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Mã HV</div>
                    <div class="font-semibold text-slate-800">{{ $user->code ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Lớp</div>
                    <div class="font-semibold text-slate-800">{{ $user->class->name ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Đơn vị / Khoa</div>
                    <div class="font-semibold text-slate-800">{{ $user->unit->name ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                    <div class="text-[11px] text-slate-500 uppercase tracking-wide">Trạng thái</div>
                    <div class="font-semibold text-emerald-700">{{ (int)($user->status ?? 0) === 1 ? 'Đang hoạt động' : 'Tạm khóa' }}</div>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('lms.learn.profile.update') }}" class="space-y-3 pt-2 border-t border-slate-100">
            @csrf
            @method('PUT')
            <div class="font-semibold text-slate-800">Cập nhật</div>
            <div>
                <label class="text-xs font-medium text-slate-600">Họ tên</label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-lg text-sm px-3 py-2 mt-1">
                @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded-lg text-sm px-3 py-2 mt-1">
                @error('email')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600">Mật khẩu mới (tuỳ chọn)</label>
                    <input type="password" name="password" class="w-full border rounded-lg text-sm px-3 py-2 mt-1" autocomplete="new-password">
                    @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Xác nhận mật khẩu</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded-lg text-sm px-3 py-2 mt-1" autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="lms-btn lms-btn-primary">Lưu thay đổi</button>
        </form>
    </div>
</div>
@endsection
