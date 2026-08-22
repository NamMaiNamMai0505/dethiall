@extends('layouts.admin')

@section('title', 'Chữ ký số')
@section('page-title', 'Chữ ký số của tôi')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ', 'url' => route('dashboard')],
        ['title' => 'Chữ ký số'],
    ]" />

    <x-page-header
        title="CHỮ KÝ SỐ"
        subtitle="Upload & quản lý chữ ký ảnh. 3 mẫu LHL (HK2) tự gán khi tên tài khoản khớp."
    />

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-2 mb-6">
        <form method="POST" action="{{ route('signatures.claim') }}">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">
                <i class="bi bi-person-check mr-2"></i> Nhận chữ ký mẫu khớp tên
            </button>
        </form>
        @if($user->isSuperAdmin())
            <form method="POST" action="{{ route('signatures.admin-claim-all') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-700 text-white text-sm font-semibold hover:bg-slate-800">
                    <i class="bi bi-people mr-2"></i> Admin: seed + claim tất cả user
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Upload mới --}}
        <div class="xl:col-span-1">
            <div class="bg-white rounded-xl border shadow-sm p-5">
                <h3 class="font-bold text-slate-800 mb-3"><i class="bi bi-upload text-blue-600"></i> Thêm chữ ký</h3>
                <form method="POST" action="{{ route('signatures.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Họ tên trên văn bản</label>
                        <input type="text" name="display_name" value="{{ old('display_name', $user->name) }}" required
                               class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Chức danh dòng 1</label>
                        <input type="text" name="role_line1" value="{{ old('role_line1') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="VD: NGƯỜI LÀM LỊCH">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Chức danh dòng 2</label>
                        <input type="text" name="role_line2" value="{{ old('role_line2') }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Vị trí LHL (tuỳ chọn)</label>
                        <select name="slot_key" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="custom">Tuỳ chọn</option>
                            @foreach($slots as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Ảnh chữ ký (PNG/JPG)</label>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required
                               class="w-full text-sm">
                        <p class="text-[11px] text-slate-500 mt-1">Nên dùng PNG nền trong suốt, ≤ 2MB.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" name="is_default" value="1" class="rounded"> Mặc định cho vị trí
                    </label>
                    <button type="submit" class="w-full py-2.5 rounded-lg bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700">
                        Lưu chữ ký
                    </button>
                </form>
            </div>
        </div>

        {{-- Danh sách --}}
        <div class="xl:col-span-2 space-y-4">
            @forelse($items as $sig)
                <div class="bg-white rounded-xl border shadow-sm p-4">
                    <div class="flex flex-wrap gap-4 items-start">
                        <div class="w-28 h-20 rounded-lg border bg-slate-50 flex items-center justify-center overflow-hidden shrink-0">
                            @if($sig->imageUrl())
                                <img src="{{ $sig->imageUrl() }}" alt="" class="max-h-full max-w-full object-contain">
                            @else
                                <span class="text-xs text-slate-400">Chưa có ảnh</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-bold text-slate-900">{{ $sig->display_name }}</span>
                                @if($sig->is_system_template)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">Mẫu HK2</span>
                                @endif
                                @if($sig->is_default)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-semibold">Mặc định</span>
                                @endif
                                @if(! $sig->is_active)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">Tắt</span>
                                @endif
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $sig->slotLabel() }}</span>
                            </div>
                            <p class="text-xs text-slate-600">
                                {{ $sig->role_line1 }}
                                @if($sig->role_line2) · {{ $sig->role_line2 }} @endif
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Chủ:
                                @if($sig->user_id)
                                    #{{ $sig->user_id }} {{ $sig->user?->name ?? '' }}
                                @else
                                    <em>Chưa gán (chờ claim theo tên)</em>
                                @endif
                            </p>

                            @if($sig->canManage($user))
                                <form method="POST" action="{{ route('signatures.update', $sig) }}" enctype="multipart/form-data"
                                      class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 border-t pt-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="display_name" value="{{ $sig->display_name }}" required
                                           class="border rounded-lg px-2 py-1.5 text-xs" placeholder="Họ tên">
                                    <input type="text" name="role_line1" value="{{ $sig->role_line1 }}"
                                           class="border rounded-lg px-2 py-1.5 text-xs" placeholder="Chức danh 1">
                                    <input type="text" name="role_line2" value="{{ $sig->role_line2 }}"
                                           class="border rounded-lg px-2 py-1.5 text-xs" placeholder="Chức danh 2">
                                    <input type="file" name="image" accept="image/*" class="text-xs">
                                    @if($user->isSuperAdmin())
                                        <input type="number" name="user_id" value="{{ $sig->user_id }}"
                                               class="border rounded-lg px-2 py-1.5 text-xs" placeholder="user_id gán (admin)">
                                    @endif
                                    <label class="inline-flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="is_active" value="1" @checked($sig->is_active)> Active
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="is_default" value="1" @checked($sig->is_default)> Default
                                    </label>
                                    <div class="sm:col-span-2 flex flex-wrap gap-2">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Cập nhật</button>
                                    </div>
                                </form>
                                <form method="POST"
                                      action="{{ route('signatures.destroy', $sig) }}"
                                      class="mt-2"
                                      data-confirm="Xoá / unclaim chữ ký này?"
                                      data-confirm-danger="1"
                                      data-confirm-title="Xác nhận chữ ký"
                                      data-confirm-ok="{{ $sig->is_system_template ? 'Unclaim' : 'Xoá' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">
                                        {{ $sig->is_system_template ? 'Unclaim mẫu' : 'Xoá chữ ký' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border p-8 text-center text-slate-500 text-sm">
                    Chưa có chữ ký. Upload mới hoặc bấm «Nhận chữ ký mẫu khớp tên».
                </div>
            @endforelse
        </div>
    </div>
@endsection
