@extends('layouts.module-portal')

@php
    $portalHome = route('leave-management.portal');
    $portalTitle = 'Cổng quản lý phép';
    $portalIcon = 'bi-calendar2-check';
    $tabs = [
        ['objects', 'Đối tượng'],
        ['annual', 'Phép hàng năm'],
        ['extra', 'Phép thêm'],
        ['special', 'Phép đặc biệt'],
        ['annual_hsqbs', 'Phép hàng năm của HSQBS'],
        ['annual_hsqbs_student', 'Phép hàng năm của HSQBS là học viên'],
        ['special_hsqbs', 'Phép đặc biệt của HSQBS'],
    ];
    $ruleTabs = [
        'annual' => ['title' => 'Phép hàng năm', 'leave_type' => 'ANNUAL', 'object_type' => null, 'label' => null, 'focused' => false],
        'extra' => ['title' => 'Phép thêm', 'leave_type' => 'EXTRA', 'object_type' => null, 'label' => null, 'focused' => false],
        'special' => ['title' => 'Phép đặc biệt', 'leave_type' => 'SPECIAL', 'object_type' => null, 'label' => null, 'focused' => false],
        'annual_hsqbs' => ['title' => 'Phép hàng năm của HSQBS', 'leave_type' => 'ANNUAL', 'object_type' => 'HSQBS', 'label' => 'Phép hàng năm của HSQBS', 'focused' => true],
        'annual_hsqbs_student' => ['title' => 'Phép hàng năm của HSQBS là học viên', 'leave_type' => 'ANNUAL', 'object_type' => 'HV', 'label' => 'Phép hàng năm của HSQBS là học viên', 'focused' => true],
        'special_hsqbs' => ['title' => 'Phép đặc biệt của HSQBS', 'leave_type' => 'SPECIAL', 'object_type' => 'HSQBS', 'label' => 'Phép đặc biệt của HSQBS', 'focused' => true],
    ];
    $activeRuleTab = $ruleTabs[$tab] ?? null;
    $ruleRows = collect();
    if ($activeRuleTab) {
        $ruleRows = $regulations->where('leave_type', $activeRuleTab['leave_type']);
        if ($activeRuleTab['focused']) {
            $ruleRows = $ruleRows
                ->where('object_type', $activeRuleTab['object_type'])
                ->filter(fn($rule) => !$activeRuleTab['label'] || $rule->label === $activeRuleTab['label'])
                ->values();
        }
    }
@endphp

@section('title', 'Quy định về phép')
@section('page-title', 'Quy định về phép')
@section('content')
@include('partials.module-menu', ['module' => 'leave'])

<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Quy định về phép</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Quản lý đối tượng, phép hàng năm, phép thêm và phép đặc biệt.</p>
    </div>

    <nav class="flex flex-wrap gap-2 rounded-2xl border border-blue-100 bg-white p-2 shadow-sm">
        @foreach($tabs as [$key, $label])
            <a href="{{ route('leave-management.regulations.dashboard', ['tab' => $key]) }}" class="rounded-xl px-4 py-2.5 text-sm font-extrabold {{ $tab === $key ? 'bg-blue-700 text-white shadow-md' : 'text-slate-700 hover:bg-blue-50 hover:text-blue-700' }}">
                {{ $label }} @if($key === 'objects')({{ $objects->count() }})@endif
            </a>
        @endforeach
    </nav>

    @if($tab === 'objects')
        <form method="POST" action="{{ route('leave-management.object-types.store') }}" class="grid gap-3 rounded-2xl border border-blue-100 bg-white p-5 shadow-sm md:grid-cols-[140px_1fr_140px_auto]">
            @csrf
            <input name="code" required placeholder="Mã ĐT" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <input name="name" required placeholder="Tên đối tượng" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <input name="sort_order" type="number" min="0" value="{{ $objects->count() + 1 }}" placeholder="Thứ tự" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700"><input type="checkbox" name="active" value="1" checked class="h-4 w-4 accent-blue-600"> Hoạt động</label>
            <button class="rounded-xl bg-blue-700 px-4 py-2.5 font-bold text-white md:col-span-4">Thêm đối tượng</button>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-blue-50 px-5 py-4">
                <h2 class="font-extrabold text-slate-900">Danh mục đối tượng</h2>
                <p class="mt-1 text-sm text-slate-600">Mã đối tượng và tên đối tượng áp dụng quy định phép.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-extrabold text-slate-700">
                        <tr><th class="px-5 py-3">Mã ĐT</th><th class="px-5 py-3">Tên ĐT</th><th class="px-5 py-3">Thứ tự</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($objects as $object)
                            <tr class="align-top">
                                <td class="px-5 py-3 font-bold text-blue-800">{{ $object->code }}</td>
                                <td class="px-5 py-3 font-semibold">{{ $object->name }}</td>
                                <td class="px-5 py-3">{{ $object->sort_order }}</td>
                                <td class="px-5 py-3"><span class="rounded-full {{ $object->active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }} px-2 py-1 text-xs font-bold">{{ $object->active ? 'Hoạt động' : 'Tạm ẩn' }}</span></td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded bg-blue-600 px-3 py-1.5 text-sm font-bold text-white">Sửa</summary>
                                            <form method="POST" action="{{ route('leave-management.object-types.update', $object) }}" class="absolute right-0 z-30 mt-2 grid w-[min(92vw,460px)] gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xl md:grid-cols-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Mã ĐT</span><input name="code" required value="{{ $object->code }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ $object->sort_order }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="block md:col-span-2"><span class="mb-1 block text-xs font-bold text-slate-600">Tên đối tượng</span><input name="name" required value="{{ $object->name }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 md:col-span-2"><input type="checkbox" name="active" value="1" @checked($object->active) class="h-4 w-4 accent-blue-600"> Hoạt động</label>
                                                <button class="rounded bg-blue-700 px-4 py-2 font-bold text-white md:col-span-2">Lưu thay đổi</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('leave-management.object-types.delete', $object) }}" onsubmit="return confirm('Xóa đối tượng phép này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-rose-600 px-3 py-1.5 text-sm font-bold text-white">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6 text-center text-slate-500">Chưa có đối tượng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($activeRuleTab)
        <form method="POST" action="{{ route('leave-management.regulations.store') }}" class="grid gap-3 rounded-2xl border border-blue-100 bg-white p-5 shadow-sm md:grid-cols-4">
            @csrf
            <input type="hidden" name="leave_type" value="{{ $activeRuleTab['leave_type'] }}">
            @if($activeRuleTab['focused'])
                <input type="hidden" name="object_type" value="{{ $activeRuleTab['object_type'] }}">
                <input type="hidden" name="label" value="{{ $activeRuleTab['label'] }}">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-semibold text-slate-700 md:col-span-2">{{ $activeRuleTab['title'] }}</div>
            @else
                <select name="object_type" class="rounded-xl border border-slate-200 px-3 py-2.5">
                    <option value="">Mọi đối tượng</option>
                    @foreach($objects as $object)
                        <option value="{{ $object->code }}">{{ $object->code }} — {{ $object->name }}</option>
                    @endforeach
                </select>
                <input name="label" placeholder="Tên quy định" class="rounded-xl border border-slate-200 px-3 py-2.5">
            @endif
            <input name="min_years" type="number" min="0" placeholder="Từ năm công tác" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <input name="max_years" type="number" min="0" placeholder="Đến năm công tác" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <input name="base_days" type="number" min="0" required placeholder="{{ $tab === 'annual_hsqbs_student' ? '0 nếu theo nghỉ hè' : 'Số ngày' }}" class="rounded-xl border border-slate-200 px-3 py-2.5">
            <textarea name="description" placeholder="Căn cứ / mô tả quy định" class="rounded-xl border border-slate-200 px-3 py-2.5 md:col-span-3"></textarea>
            <button class="rounded-xl bg-blue-700 px-4 py-2.5 font-bold text-white md:col-span-4">Thêm quy định</button>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-blue-50 px-5 py-4">
                <h2 class="font-extrabold text-slate-900">{{ $activeRuleTab['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">Có thể thêm, sửa hoặc xóa quy định trong tab này.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-extrabold text-slate-700">
                        <tr><th class="px-5 py-3">Tên quy định</th><th class="px-5 py-3">Đối tượng</th><th class="px-5 py-3">Thâm niên</th><th class="px-5 py-3">Số ngày</th><th class="px-5 py-3">Căn cứ / nội dung</th><th class="px-5 py-3">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($ruleRows as $rule)
                            <tr class="align-top">
                                <td class="px-5 py-3 font-bold text-slate-900">{{ $rule->label ?: $activeRuleTab['title'] }}</td>
                                <td class="px-5 py-3 font-semibold">{{ $rule->object_type ? ($objects->firstWhere('code', $rule->object_type)?->name ?? $rule->object_type) : 'Mọi đối tượng' }}</td>
                                <td class="px-5 py-3">{{ $rule->min_years ?? 0 }} – {{ $rule->max_years ?? 'trở lên' }} năm</td>
                                <td class="px-5 py-3 font-extrabold text-blue-700">{{ (int) $rule->base_days > 0 ? $rule->base_days.' ngày' : 'Theo thời gian nghỉ' }}</td>
                                <td class="px-5 py-3 leading-6 text-slate-600">{{ $rule->description ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded bg-blue-600 px-3 py-1.5 text-sm font-bold text-white">Sửa</summary>
                                            <form method="POST" action="{{ route('leave-management.regulations.update', $rule) }}" class="absolute right-0 z-30 mt-2 grid w-[min(92vw,560px)] gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xl md:grid-cols-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="leave_type" value="{{ $activeRuleTab['leave_type'] }}">
                                                @if($activeRuleTab['focused'])
                                                    <input type="hidden" name="object_type" value="{{ $activeRuleTab['object_type'] }}">
                                                    <input type="hidden" name="label" value="{{ $activeRuleTab['label'] }}">
                                                    <div class="rounded border bg-slate-50 px-3 py-2 font-semibold text-slate-700 md:col-span-2">{{ $activeRuleTab['title'] }}</div>
                                                @else
                                                    <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Đối tượng</span><select name="object_type" class="w-full rounded border px-3 py-2"><option value="">Mọi đối tượng</option>@foreach($objects as $object)<option value="{{ $object->code }}" @selected($rule->object_type === $object->code)>{{ $object->code }} — {{ $object->name }}</option>@endforeach</select></label>
                                                    <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Tên quy định</span><input name="label" value="{{ $rule->label }}" class="w-full rounded border px-3 py-2"></label>
                                                @endif
                                                <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Từ năm</span><input name="min_years" type="number" min="0" value="{{ $rule->min_years }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Đến năm</span><input name="max_years" type="number" min="0" value="{{ $rule->max_years }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="block"><span class="mb-1 block text-xs font-bold text-slate-600">Số ngày</span><input name="base_days" type="number" min="0" required value="{{ $rule->base_days }}" class="w-full rounded border px-3 py-2"></label>
                                                <label class="block md:col-span-2"><span class="mb-1 block text-xs font-bold text-slate-600">Căn cứ / mô tả</span><textarea name="description" rows="3" class="w-full rounded border px-3 py-2">{{ $rule->description }}</textarea></label>
                                                <button class="rounded bg-blue-700 px-4 py-2 font-bold text-white md:col-span-2">Lưu thay đổi</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('leave-management.regulations.delete', $rule) }}" onsubmit="return confirm('Xóa quy định phép này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-rose-600 px-3 py-1.5 text-sm font-bold text-white">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">Chưa có quy định.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
