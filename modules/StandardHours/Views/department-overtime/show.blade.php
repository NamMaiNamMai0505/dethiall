@extends('layouts.admin')
@section('title', 'Vượt DM · '.$department->name)
@section('page-title', 'Vượt DM bộ môn')

@section('content')
<div class="mb-4 flex flex-wrap justify-between gap-3">
    <div>
        <a href="{{ route('standard-hours.department-overtime.index', ['year' => $year]) }}" class="text-sm text-blue-600">← Danh sách</a>
        <h1 class="text-xl font-bold mt-1">{{ $department->name }}</h1>
        <p class="text-sm text-slate-500">{{ $department->unit?->name }} · {{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }} {{ app(\Modules\StandardHours\Services\PeriodService::class)->label($year) }}</p>
    </div>
    <div class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="text-xs text-slate-500">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}</label>
            <select class="border rounded-lg text-sm px-3 py-2" onchange="location='?year='+encodeURIComponent(this.value)">
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @can('standard-hours.department-overtime.manage')
            <form method="POST" action="{{ route('standard-hours.department-overtime.calculate', $department) }}">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold">
                    {{ $pool ? 'Tính lại pool' : 'Tính pool vượt' }}
                </button>
            </form>
        @endcan
    </div>
</div>

@if(!$pool)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-sm text-amber-900">
        Chưa có pool năm {{ $year }}. Nên <strong>Tính giờ chuẩn</strong> cá nhân trước (menu Tính giờ chuẩn), rồi bấm <strong>Tính pool vượt</strong>.
        Pool = Σ giờ thực hiện − Σ định mức (đã áp giảm trừ Đ.11.3).
    </div>
@else
    <div class="grid sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-slate-500">Σ Định mức phải làm</div>
            <div class="text-2xl font-bold tabular-nums">{{ number_format($pool->pool_must_hours, 1) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-slate-500">Σ Thực hiện</div>
            <div class="text-2xl font-bold tabular-nums">{{ number_format($pool->pool_done_hours, 1) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-emerald-500">
            <div class="text-xs text-emerald-700">Pool vượt (Đ.17.2)</div>
            <div class="text-2xl font-bold text-emerald-800 tabular-nums">{{ number_format($pool->pool_excess_hours, 1) }}</div>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <div class="text-xs text-slate-500">Trạng thái</div>
            <div class="text-lg font-semibold">{{ $pool->status }}</div>
            <div class="text-[11px] text-slate-400">{{ $pool->calculated_at?->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden mb-4">
        <div class="px-4 py-2 border-b font-semibold text-sm">Chi tiết thành viên (snapshot lúc tính)</div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 text-left">
            <tr>
                <th class="px-3 py-2">GV</th>
                <th class="px-3 py-2 text-right">Phải làm</th>
                <th class="px-3 py-2 text-right">Thực hiện</th>
                <th class="px-3 py-2 text-right">Chênh</th>
                <th class="px-3 py-2 text-right">% giảm trừ</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @foreach(($pool->member_snapshot ?? []) as $m)
                <tr>
                    <td class="px-3 py-2">{{ $m['name'] ?? '' }} <span class="text-xs text-slate-400 font-mono">{{ $m['code'] ?? '' }}</span></td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($m['must_hours'] ?? 0, 1) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($m['done_hours'] ?? 0, 1) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($m['excess_hours'] ?? 0, 1) }}</td>
                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($m['reduction_percent'] ?? 0, 1) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <h2 class="font-semibold text-sm mb-1">Phân bổ vượt cho từng GV (Đ.17.3 — dân chủ, công khai)</h2>
        <p class="text-xs text-slate-500 mb-3">
            Tổng phân bổ ≤ pool {{ number_format($pool->pool_excess_hours, 1) }} giờ.
            Gợi ý chia đều đã điền sẵn (có thể sửa).
        </p>

        @if($pool->isLocked())
            <div class="text-sm text-slate-600 mb-2">Đã khóa — chỉ xem.</div>
        @endif

        <form method="POST" action="{{ route('standard-hours.department-overtime.allocate', $pool) }}">
            @csrf
            <table class="min-w-full text-sm mb-3">
                <thead class="bg-slate-50 text-xs text-left">
                <tr>
                    <th class="px-3 py-2">GV</th>
                    <th class="px-3 py-2 w-40">Giờ vượt được gán</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach(($pool->member_snapshot ?? []) as $m)
                    @php
                        $id = $m['instructor_id'];
                        $existing = $pool->allocations->firstWhere('instructor_id', $id);
                        $val = old('allocations.'.$id, $existing?->allocated_hours ?? ($suggest[$id] ?? 0));
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $m['name'] }}</td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.1" min="0" name="allocations[{{ $id }}]"
                                   value="{{ $val }}"
                                   class="w-full border rounded-lg text-sm px-2 py-1.5"
                                   @disabled($pool->isLocked())>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <textarea name="note" rows="2" class="w-full border rounded-lg text-sm px-3 py-2 mb-3"
                      placeholder="Ghi chú biên bản họp / nguyên tắc chia..." @disabled($pool->isLocked())>{{ old('note', $pool->note) }}</textarea>
            @can('standard-hours.department-overtime.manage')
                @unless($pool->isLocked())
                    <div class="flex flex-wrap gap-2">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Lưu phân bổ</button>
                    </div>
                @endunless
            @endcan
        </form>

        @can('standard-hours.department-overtime.manage')
            @if($pool->status === 'finalized')
                <form method="POST" action="{{ route('standard-hours.department-overtime.lock', $pool) }}" class="mt-3"
                      data-confirm="Khóa pool? Không sửa phân bổ sau khi khóa." data-confirm-danger="1">
                    @csrf
                    <button class="px-4 py-2 border border-rose-300 text-rose-700 rounded-lg text-sm">Khóa pool</button>
                </form>
            @endif
        @endcan
    </div>
@endif
@endsection
