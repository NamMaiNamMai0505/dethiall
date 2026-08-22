@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">System Admin</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Database Management Hub</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Sơ đồ schema hiện tại ở chế độ chỉ đọc. Các thay đổi database sẽ được đưa qua Migration Designer ở sprint sau.
            </p>
        </div>
        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <span class="font-semibold">Driver:</span> {{ strtoupper($driver) }}
            <div class="mt-1 text-xs text-blue-700">{{ count($catalog) }} bảng được phát hiện</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
        <div><span class="font-semibold text-violet-900">ERD Schema Map</span><span class="ml-2 text-sm text-violet-700">Xem và kéo thả vị trí các bảng.</span></div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('database-management.map') }}" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Sơ đồ quan hệ</a><a href="{{ route('database-management.business') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Business Map</a><a href="{{ route('database-management.data') }}" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Data Explorer</a><a href="{{ route('database-management.sql') }}" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">SQL Console</a><a href="{{ route('database-management.migrations') }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Migration Designer</a><a href="{{ route('database-management.integrity') }}" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700">Integrity Audit</a><a href="{{ route('database-management.audits') }}" class="rounded-xl bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Audit History</a></div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Schema Map</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ count($catalog) }}</div>
            <div class="mt-1 text-sm text-slate-500">Bảng và quan hệ khóa ngoại</div>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Chế độ an toàn</div>
            <div class="mt-2 text-2xl font-bold text-amber-900">READ ONLY</div>
            <div class="mt-1 text-sm text-amber-800">Không thực thi SQL hoặc ALTER trực tiếp</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Sprint tiếp theo</div>
            <div class="mt-2 text-2xl font-bold text-emerald-900">ERD Map</div>
            <div class="mt-1 text-sm text-emerald-800">Kéo thả quan hệ, chưa publish schema</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div>
                <h2 class="font-semibold text-slate-900">Schema Catalog</h2>
                <p class="text-xs text-slate-500">Chọn bảng để xem cột và liên kết.</p>
            </div>
            <form method="get" class="flex gap-2">
                <input name="search" data-live-search="1" value="{{ request('search') }}" placeholder="Tìm bảng hoặc cột..."
                       class="w-64 rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-blue-500 focus:ring-2">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lọc</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Bảng</th><th class="px-4 py-3">Cột</th><th class="px-4 py-3">Khóa ngoại</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($catalog as $table)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-900">{{ $table['name'] }}</td>
                        <td class="px-4 py-4"><div class="flex max-w-xl flex-wrap gap-1.5">
                            @foreach($table['columns'] as $column)
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-700" title="{{ $column['type'] }}">
                                    {{ $column['name'] }} <span class="text-slate-400">({{ $column['type'] }})</span>
                                </span>
                            @endforeach
                        </div></td>
                        <td class="px-4 py-4">
                            @forelse($table['foreign_keys'] as $foreign)
                                <div class="mb-1 rounded-lg border border-blue-100 bg-blue-50 px-2 py-1 text-xs text-blue-800">
                                    {{ implode(', ', $foreign['columns']) }} → {{ $foreign['foreign_table'] }}.{{ implode(', ', $foreign['foreign_columns']) }}
                                </div>
                            @empty <span class="text-xs text-slate-400">Không có</span> @endforelse
                        </td>
                        <td class="px-4 py-4"><span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã đọc schema</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Không tìm thấy bảng phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('partials.live-search')
@endsection
