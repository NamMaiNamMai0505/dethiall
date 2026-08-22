@extends('layouts.lms-learner')

@section('title', 'Lượt thi · '.$exam->title)

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div>
        <a href="{{ route('lms.learn.courses.show', $course) }}?mode=teach&tab=exam" class="text-sm text-teal-700 hover:underline">← Thi online</a>
        <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $exam->title }}</h1>
        <p class="text-sm text-slate-500">{{ $course->title }} · {{ $rows->count() }} lượt làm</p>
    </div>
    <a href="{{ route('lms.teach.exams.export', [$course, $exam]) }}" class="lms-btn-solid text-sm" data-turbo="false">
        <i class="bi bi-filetype-csv"></i> Xuất CSV
    </a>
</div>

<div class="lms-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-500">
            <tr>
                <th class="px-3 py-2 font-semibold">Học viên</th>
                <th class="px-3 py-2 font-semibold">Bắt đầu</th>
                <th class="px-3 py-2 font-semibold">Nộp</th>
                <th class="px-3 py-2 font-semibold">Điểm</th>
                <th class="px-3 py-2 font-semibold">Blur</th>
                <th class="px-3 py-2 font-semibold">Proctor</th>
                <th class="px-3 py-2 font-semibold">TT</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows as $att)
                <tr class="align-top">
                    <td class="px-3 py-2">
                        <div class="font-medium">{{ $att->user->name ?? '—' }}</div>
                        <div class="text-[11px] text-slate-400">{{ $att->user->email ?? '' }}</div>
                    </td>
                    <td class="px-3 py-2 tabular-nums">{{ $att->started_at?->format('d/m H:i') ?? '—' }}</td>
                    <td class="px-3 py-2 tabular-nums">{{ $att->submitted_at?->format('d/m H:i') ?? '—' }}</td>
                    <td class="px-3 py-2 font-semibold text-teal-800">
                        {{ $att->score !== null ? $att->score.'/'.$att->max_score : '—' }}
                    </td>
                    <td class="px-3 py-2 tabular-nums {{ ($att->blur_count ?? 0) > 3 ? 'text-rose-600 font-semibold' : '' }}">
                        {{ $att->blur_count ?? 0 }}
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-500">
                        @php $evs = is_array($att->proctor_events) ? $att->proctor_events : []; @endphp
                        <button type="button" class="text-teal-700 font-semibold hover:underline"
                                onclick="document.getElementById('proctor-{{ $att->id }}').classList.toggle('hidden')">
                            {{ count($evs) }} sk ▾
                        </button>
                        <div id="proctor-{{ $att->id }}" class="hidden mt-2 max-h-40 overflow-y-auto rounded border border-slate-100 bg-slate-50 p-2 space-y-1">
                            @forelse($evs as $ev)
                                <div class="text-[11px] leading-snug">
                                    <span class="font-mono text-slate-400">{{ \Illuminate\Support\Str::limit($ev['at'] ?? '', 19, '') }}</span>
                                    <span class="font-semibold text-slate-700">{{ $ev['type'] ?? '?' }}</span>
                                    @if(!empty($ev['detail']))
                                        <span class="text-slate-500">· {{ $ev['detail'] }}</span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-[11px] text-slate-400">Không có sự kiện.</div>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-3 py-2 text-xs">
                        @if($att->status === 'graded' || $att->status === 'submitted')
                            <span class="text-emerald-700 font-medium">{{ $att->status }}</span>
                        @elseif($att->status === 'in_progress')
                            <span class="text-amber-700">đang làm</span>
                        @else
                            {{ $att->status }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">Chưa có lượt làm bài.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
