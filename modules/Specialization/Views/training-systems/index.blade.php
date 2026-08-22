@extends('layouts.admin')

@section('title', 'Hệ đào tạo')
@section('page-title', 'Hệ đào tạo')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Hệ đào tạo']
]" />

<x-page-header
    title="HỆ ĐÀO TẠO"
    subtitle="Quản lý các hệ (Dân sự, Quân sự, …). Ngành đào tạo thuộc một hệ để lọc chương trình."
    :actions="[
        [
            'url' => route('specializations.index'),
            'label' => 'Ngành đào tạo',
            'icon' => 'list',
            'color' => 'gray'
        ]
    ]" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3">Thêm hệ mới</h3>
            <form method="POST" action="{{ route('training-systems.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên hệ <span class="text-red-500">*</span></label>
                    <input name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="VD: Hệ Dân sự">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã</label>
                    <input name="code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="civilian / military (tự tạo nếu trống)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự</label>
                    <input type="number" name="sort_order" value="0" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Đang hoạt động
                </label>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2 text-sm font-semibold">
                    Lưu hệ đào tạo
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="px-4 py-3">Tên</th>
                        <th class="px-4 py-3">Mã</th>
                        <th class="px-4 py-3">Ngành</th>
                        <th class="px-4 py-3">TT</th>
                        <th class="px-4 py-3">TT</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($systems as $sys)
                    <tr class="align-top">
                        <td class="px-4 py-3" colspan="6">
                            <div class="flex flex-wrap gap-2 items-start">
                                <form method="POST" action="{{ route('training-systems.update', $sys) }}" class="flex-1 grid sm:grid-cols-5 gap-2 items-center min-w-[16rem]">
                                    @csrf
                                    @method('PUT')
                                    <div class="sm:col-span-2">
                                        <input name="name" value="{{ $sys->name }}" required class="w-full border rounded-lg px-2 py-1.5 text-sm">
                                        @if($sys->description)
                                            <p class="text-xs text-slate-400 mt-0.5">{{ \Illuminate\Support\Str::limit($sys->description, 60) }}</p>
                                        @endif
                                    </div>
                                    <input name="code" value="{{ $sys->code }}" required class="border rounded-lg px-2 py-1.5 text-sm">
                                    <div class="text-slate-600 text-xs py-1.5">{{ $sys->specializations_count }} ngành · TT
                                        <input type="number" name="sort_order" value="{{ $sys->sort_order }}" class="border rounded-lg px-2 py-1 text-sm w-16 inline-block ml-1">
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <label class="inline-flex items-center gap-1 text-xs">
                                            <input type="checkbox" name="is_active" value="1" @checked($sys->is_active)>
                                            Active
                                        </label>
                                        <button class="px-3 py-1.5 bg-teal-600 text-white rounded-lg text-xs font-semibold">Lưu</button>
                                    </div>
                                </form>
                                <form method="POST"
                                      action="{{ route('training-systems.destroy', $sys) }}"
                                      data-confirm="Xóa hệ này?"
                                      data-confirm-danger="1"
                                      data-confirm-title="Xóa hệ đào tạo"
                                      data-confirm-ok="Xóa">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 border border-rose-200 text-rose-700 rounded-lg text-xs">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có hệ đào tạo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Khi tạo/sửa <strong>Ngành đào tạo</strong>, luôn chọn Hệ. Các form lọc môn/chương trình sẽ lọc theo Hệ → Ngành → Môn.
        </p>
    </div>
</div>
@endsection
