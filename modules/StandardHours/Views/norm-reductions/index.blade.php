@extends('layouts.admin')
@section('title', 'Giảm trừ định mức')
@section('page-title', 'Giảm trừ định mức (Đ.11.3)')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Giảm trừ Đ.11.3'],
]" />
<x-page-header
    title="GIẢM TRỪ ĐỊNH MỨC GIỜ CHUẨN"
    subtitle="TT 06/2026 Điều 11.3 — nhiệm vụ đột xuất, nghỉ chữa bệnh, thai sản… Tỷ lệ giảm = ngày/365 hoặc % thủ công. Áp khi Tính giờ chuẩn."
/>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="bg-white border rounded-xl p-4">
        <h2 class="font-semibold text-sm mb-3">Thêm giảm trừ</h2>
        <form method="POST" action="{{ route('standard-hours.norm-reductions.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="text-xs text-slate-500">Giảng viên *</label>
                <select name="instructor_id" required class="w-full border rounded-lg px-3 py-2" data-searchable="1">
                    <option value="">— Chọn —</option>
                    @foreach($instructors as $gv)
                        <option value="{{ $gv->id }}">{{ $gv->name }} ({{ $gv->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }} *</label>
                <select name="year" required class="w-full border rounded-lg px-3 py-2">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Loại *</label>
                <select name="type" required class="w-full border rounded-lg px-3 py-2">
                    @foreach($types as $k => $lab)
                        <option value="{{ $k }}">{{ $lab }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Tiêu đề</label>
                <input name="title" class="w-full border rounded-lg px-3 py-2" placeholder="VD: Điều động công tác">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs text-slate-500">Từ ngày</label>
                    <input type="date" name="start_date" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Đến ngày</label>
                    <input type="date" name="end_date" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs text-slate-500">Số ngày</label>
                    <input type="number" name="days" min="0" max="366" class="w-full border rounded-lg px-3 py-2" placeholder="auto từ khoảng">
                </div>
                <div>
                    <label class="text-xs text-slate-500">% giảm (tuỳ chọn)</label>
                    <input type="number" step="0.1" name="reduction_percent" min="0" max="100" class="w-full border rounded-lg px-3 py-2" placeholder="vd 25">
                </div>
            </div>
            <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2" placeholder="Ghi chú / căn cứ"></textarea>
            @can('standard-hours.norm-reductions.manage')
                <button class="w-full py-2 bg-blue-600 text-white rounded-lg font-semibold">Lưu</button>
            @endcan
        </form>
    </div>

    <div class="lg:col-span-2 bg-white border rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-500">
            <tr>
                <th class="px-3 py-2">GV</th>
                <th class="px-3 py-2">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}</th>
                <th class="px-3 py-2">Loại</th>
                <th class="px-3 py-2">Khoảng / ngày</th>
                <th class="px-3 py-2">% giảm</th>
                <th class="px-3 py-2"></th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($rows as $r)
                <tr>
                    <td class="px-3 py-2">{{ $r->instructor?->name }}</td>
                    <td class="px-3 py-2">{{ $r->period_label }}</td>
                    <td class="px-3 py-2 text-xs">{{ $r->type_label }}</td>
                    <td class="px-3 py-2 text-xs">
                        @if($r->start_date)
                            {{ $r->start_date->format('d/m/Y') }} → {{ $r->end_date?->format('d/m/Y') ?? '…' }}
                        @endif
                        · {{ $r->days }} ngày
                    </td>
                    <td class="px-3 py-2 font-semibold tabular-nums">{{ number_format($r->resolvedPercent(), 1) }}%</td>
                    <td class="px-3 py-2 text-right">
                        @can('standard-hours.norm-reductions.manage')
                            <form method="POST" action="{{ route('standard-hours.norm-reductions.destroy', $r) }}"
                                  data-confirm="Xóa giảm trừ này?" data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button class="text-rose-600 text-xs">Xóa</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có bản ghi giảm trừ.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
