@extends('layouts.admin')
@section('title', 'Vượt định mức bộ môn')
@section('page-title', 'Vượt DM theo bộ môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Vượt DM bộ môn'],
]" />
<x-page-header
    title="VƯỢT ĐỊNH MỨC THEO BỘ MÔN"
    subtitle="TT 06/2026/TT-BQP Điều 17 — pool chung BM = Σ thực hiện − Σ định mức (đã giảm trừ Đ.11.3)."
    :actions="[['url' => route('standard-hours.hub'), 'label' => 'Hub', 'icon' => 'arrow-left', 'color' => 'gray']]"
/>

<form method="GET" class="bg-white border rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="text-xs text-slate-500">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}</label>
        <select name="year" class="border rounded-lg text-sm px-3 py-2">
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Xem</button>
</form>

<div class="bg-white border rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs text-slate-500">
        <tr>
            <th class="px-4 py-2">Bộ môn</th>
            <th class="px-4 py-2">Khoa</th>
            <th class="px-4 py-2">GV</th>
            <th class="px-4 py-2 text-right">Phải làm</th>
            <th class="px-4 py-2 text-right">Thực hiện</th>
            <th class="px-4 py-2 text-right">Vượt (pool)</th>
            <th class="px-4 py-2">TT</th>
            <th class="px-4 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($departments as $d)
            @php $p = $pools[$d->id] ?? null; @endphp
            <tr>
                <td class="px-4 py-2 font-medium">{{ $d->name }}</td>
                <td class="px-4 py-2">{{ $d->unit?->name }}</td>
                <td class="px-4 py-2">{{ $d->instructors_count }}</td>
                <td class="px-4 py-2 text-right tabular-nums">{{ $p ? number_format($p->pool_must_hours, 1) : '—' }}</td>
                <td class="px-4 py-2 text-right tabular-nums">{{ $p ? number_format($p->pool_done_hours, 1) : '—' }}</td>
                <td class="px-4 py-2 text-right tabular-nums font-semibold {{ ($p?->pool_excess_hours ?? 0) > 0 ? 'text-emerald-700' : '' }}">
                    {{ $p ? number_format($p->pool_excess_hours, 1) : '—' }}
                </td>
                <td class="px-4 py-2 text-xs">{{ $p?->status ?? 'chưa tính' }}</td>
                <td class="px-4 py-2 text-right">
                    <a href="{{ route('standard-hours.department-overtime.show', ['department' => $d, 'year' => $year]) }}"
                       class="text-blue-600 font-medium">Chi tiết</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Chưa có bộ môn. Tạo tại «Bộ môn».</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
