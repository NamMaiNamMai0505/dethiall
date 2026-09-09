@php
    $types = ['LIQUIDATION' => 'Thanh lý'];
    $statuses = ['PENDING' => 'Chờ duyệt', 'APPROVED' => 'Đã duyệt', 'REJECTED' => 'Từ chối', 'COMPLETED' => 'Đã hoàn thành'];
    $canPrintProposal = in_array($proposal->status, ['PENDING', 'APPROVED', 'COMPLETED'], true)
        && \App\Support\PermissionCheck::userCan('inventory.proposals.export');
    $digitalSignature = auth()->id()
        ? \App\Models\DigitalSignature::query()
            ->active()
            ->forUser((int) auth()->id())
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first()
        : null;
    $hasDigitalSignature = (bool) ($digitalSignature?->imageUrl());
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-white p-5">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Chi tiết đề xuất thanh lý #{{ $proposal->id }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $proposal->title }}</p>
        </div>
        <a href="{{ route('inventory.proposals.approval') }}" class="rounded border px-4 py-2 text-sm font-semibold text-slate-700">
            Quay lại duyệt đề xuất
        </a>
    </div>

    <div class="rounded-lg border bg-white p-5">
        <div class="grid gap-4 md:grid-cols-4">
            <div><b>Loại đề xuất</b><p>{{ $types[$proposal->type] ?? $proposal->type }}</p></div>
            <div><b>Ngành vật tư</b><p>{{ $proposal->nganh_code ?: '—' }}</p></div>
            <div><b>Đơn vị đề xuất</b><p>{{ $proposal->unit?->name ?: $proposal->proposed_by_display_name ?: '—' }}</p></div>
            <div><b>Trạng thái</b><p>{{ $statuses[$proposal->status] ?? $proposal->status }}</p></div>
        </div>
        <div class="mt-4">
            <b>Lý do thanh lý</b>
            <p class="mt-1 whitespace-pre-line">{{ $proposal->description ?: '—' }}</p>
        </div>
        @if($proposal->status === 'REJECTED')
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3">
                <b class="text-rose-700">Lý do từ chối</b>
                <p class="mt-1 whitespace-pre-line text-rose-800">{{ $proposal->decision_note ?: 'Chưa có lý do' }}</p>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border bg-white p-5">
        <h3 class="mb-3 font-bold">Vật tư trong đề xuất</h3>
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3">Mã</th>
                    <th class="p-3">Tên vật tư</th>
                    <th class="p-3">Số lượng thực tế phòng</th>
                    <th class="p-3">Số lượng đề xuất</th>
                    <th class="p-3">Phòng / vị trí</th>
                    <th class="p-3">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proposal->items as $item)
                    @php
                        $actualQuantity = $item->asset?->quantity;
                        if ($actualQuantity === null && $item->material_id && $item->from_classroom_id) {
                            $actualQuantity = \Modules\Inventory\Models\InventoryAsset::where('material_id', $item->material_id)
                                ->where('classroom_id', $item->from_classroom_id)
                                ->sum('quantity');
                        }
                    @endphp
                    <tr class="border-t">
                        <td class="p-3">{{ $item->material_code ?: $item->original_code ?: '—' }}</td>
                        <td class="p-3">{{ $item->material_name ?: $item->name }}</td>
                        <td class="p-3">{{ $actualQuantity !== null ? number_format((float) $actualQuantity, 0, ',', '.') : '—' }} {{ $item->unit }}</td>
                        <td class="p-3 font-semibold">{{ number_format((float) $item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                        <td class="p-3">{{ $item->fromClassroom?->name ?: $item->from_room_name ?: $item->location_note ?: '—' }}</td>
                        <td class="p-3">{{ $item->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-5 text-center text-slate-500">Chưa có vật tư.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($proposal->status === 'PENDING')
        <div class="rounded-lg border bg-white p-5">
            <div class="flex flex-wrap items-center gap-2">
                @if($proposal->printed_at)
                    <form method="POST" action="{{ route('inventory.proposals.decide', $proposal) }}">
                        @csrf
                        @method('PATCH')
                        <button name="status" value="APPROVED" class="rounded bg-emerald-600 px-4 py-2 font-semibold text-white">
                            Duyệt đề xuất
                        </button>
                    </form>
                @else
                    <span class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
                        In phiếu trước khi duyệt
                    </span>
                @endif
                <form method="POST" action="{{ route('inventory.proposals.decide', $proposal) }}" class="flex min-w-[280px] flex-1 flex-wrap items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <input name="decision_note" required minlength="3" class="min-w-[220px] flex-1 rounded border p-2 text-sm" placeholder="Nhập lý do từ chối">
                    <button name="status" value="REJECTED" class="rounded bg-rose-600 px-4 py-2 font-semibold text-white">
                        Từ chối
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if($canPrintProposal)
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-5">
            <div class="grid gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                <div>
                    <h3 class="font-bold text-slate-900">In phiếu đề xuất</h3>
                    <p class="mt-1 text-sm text-slate-600">Bản trước duyệt dùng để xem nội dung, không có chữ ký.</p>
                    @if($proposal->status === 'PENDING')
                        <form method="POST" action="{{ route('inventory.proposals.print', $proposal) }}" target="_blank" class="mt-4">
                            @csrf
                            <input type="hidden" name="print_mode" value="preview">
                            <button class="w-full rounded bg-slate-900 px-4 py-2.5 font-semibold text-white">In xem trước</button>
                        </form>
                    @endif
                </div>
                <div class="lg:justify-self-end lg:w-[min(100%,760px)]">
                    @if(in_array($proposal->status, ['APPROVED', 'COMPLETED'], true))
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">Bản in sau duyệt</p>
                                <p class="mt-1 text-sm text-slate-600">Bản này chèn đầy đủ chữ ký và họ tên người duyệt.</p>
                            </div>
                            @if($hasDigitalSignature)
                                <form method="POST" action="{{ route('inventory.proposals.print', $proposal) }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="print_mode" value="digital">
                                    <button class="rounded bg-blue-600 px-4 py-2 font-semibold text-white">In bằng chữ ký số</button>
                                </form>
                            @else
                                <a href="{{ route('signatures.index') }}" class="rounded border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700">
                                    Cập nhật chữ ký số
                                </a>
                            @endif
                        </div>
                        <div class="mt-4">
                            <label class="text-sm font-semibold">Ký trực tiếp trên web</label>
                            <canvas id="proposal-sign-canvas" width="760" height="230" class="mt-1 w-full touch-none rounded border bg-white shadow-sm"></canvas>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <button type="button" id="proposal-sign-clear" class="rounded border px-3 py-1 text-sm">Xóa chữ ký</button>
                                <button type="button" id="proposal-print-direct" disabled class="rounded bg-emerald-600 px-4 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                                    In ký trực tiếp
                                </button>
                                <span id="proposal-sign-status" class="text-sm text-slate-600">{{ $hasDigitalSignature ? 'Đã có chữ ký số.' : 'Chưa có chữ ký số.' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-slate-200 bg-white/70 p-4 text-sm text-slate-600">
                            Sau khi in xem trước và duyệt phiếu, khu vực ký sẽ hiện ở đây để in bản chính thức.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif(in_array($proposal->status, ['PENDING', 'APPROVED', 'COMPLETED'], true))
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Tài khoản này chưa có quyền in phiếu đề xuất.
        </div>
    @endif
</div>

@if($canPrintProposal)
    <script>
    (() => {
        const canvas = document.getElementById('proposal-sign-canvas');
        const clear = document.getElementById('proposal-sign-clear');
        const status = document.getElementById('proposal-sign-status');
        const directButton = document.getElementById('proposal-print-direct');
        let data = '';
        let drawing = false;

        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#111827';
            const point = event => {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX - rect.left) * canvas.width / rect.width,
                    y: (event.clientY - rect.top) * canvas.height / rect.height
                };
            };
            const updateButtons = () => { if (directButton) directButton.disabled = !data; };
            canvas.addEventListener('pointerdown', event => {
                drawing = true;
                canvas.setPointerCapture(event.pointerId);
                const p = point(event);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            });
            canvas.addEventListener('pointermove', event => {
                if (!drawing) return;
                const p = point(event);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                data = canvas.toDataURL('image/png');
                status.textContent = 'Đã tạo chữ ký trực tiếp.';
                updateButtons();
            });
            canvas.addEventListener('pointerup', () => {
                drawing = false;
                data = canvas.toDataURL('image/png');
                status.textContent = 'Đã tạo chữ ký trực tiếp.';
                updateButtons();
            });
            clear?.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                data = '';
                status.textContent = '{{ $hasDigitalSignature ? 'Đã có chữ ký số.' : 'Chưa có chữ ký số.' }}';
                updateButtons();
            });
        }

        const print = async mode => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch('{{ route('inventory.proposals.print', $proposal) }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
                body: JSON.stringify({print_mode: mode, signature_data: mode === 'direct' ? data : ''})
            });
            if (!res.ok) {
                const html = await res.text();
                alert(html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Không thể in phiếu.');
                return;
            }
            const payload = await res.json();
            window.open(payload.url, '_blank');
        };

        if (directButton) directButton.addEventListener('click', () => print('direct'));
    })();
    </script>
@endif
