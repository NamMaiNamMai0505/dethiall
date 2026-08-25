@if($section === 'approvals')
<div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div><h2 class="text-lg font-extrabold text-slate-900">Quy trình duyệt phép</h2><p class="text-sm text-slate-500">Đơn được xử lý lần lượt qua chỉ huy cơ quan và cơ quan quản lý.</p></div>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $items->count() }} đơn chờ</span>
    </div>
    <div class="space-y-3">
    @forelse($items as $pending)
        @php($isCommander=$pending->status==='PENDING_COMMANDER')
        <div class="rounded-xl border {{ $isCommander?'border-amber-200 bg-amber-50/50':'border-blue-200 bg-blue-50/40' }} p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><div class="font-bold text-slate-900">#{{ $pending->id }} · {{ $pending->personnel?->name ?: $pending->class_name }}</div><div class="mt-1 text-sm text-slate-600">{{ $pending->from_date?->format('d-m-Y') }} – {{ $pending->to_date?->format('d-m-Y') }} · {{ $pending->leave_type }} · {{ $pending->total_days }} ngày</div></div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $isCommander?'bg-amber-100 text-amber-800':'bg-blue-100 text-blue-800' }}">{{ $isCommander?'Chờ chỉ huy cơ quan':'Chờ quân lực' }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <a href="{{ route('leave-management.requests.show',$pending) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Xem chi tiết</a>
                <a target="_blank" href="{{ route('leave-management.requests.print',$pending) }}" class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-sm font-semibold text-blue-700">In giấy phép</a>
                @if($isCommander)
                    <a href="{{ route('leave-management.requests.show',$pending) }}" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-amber-800">Xem và sửa</a>
                    <form method="POST" action="{{ route('leave-management.requests.decide',$pending) }}" class="flex flex-wrap gap-2">@csrf @method('PATCH')<input name="decision_note" placeholder="Ghi chú xử lý" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><button name="status" value="PENDING_AGENCY" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white">Duyệt chuyển cơ quan quản lý</button><button name="status" value="REJECTED" class="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white">Từ chối</button></form>
                @else
                    <form method="POST" action="{{ route('leave-management.requests.decide',$pending) }}" class="flex flex-wrap items-center gap-2">@csrf @method('PATCH')<input type="hidden" name="bgh_signed" value="1"><input name="bgh_note" placeholder="Số/ngày văn bản BGH ký" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><input name="decision_note" placeholder="Ghi chú duyệt" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><button name="status" value="APPROVED" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white">BGH đã ký · Duyệt cuối</button><button name="status" value="REJECTED" class="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white">Từ chối</button></form>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Không có đơn phù hợp để xử lý.</div>
    @endforelse
    </div>
</div>
@endif
