@php
    $typeLabels = ['LIQUIDATION' => 'Thanh lý'];
    $statusLabels = ['PENDING' => 'Chờ duyệt', 'APPROVED' => 'Đã duyệt', 'REJECTED' => 'Từ chối', 'COMPLETED' => 'Đã hoàn thành'];
    $proposalItems = $proposal->items ?? collect();
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu đề xuất thanh lý {{ $proposal->proposal_code ?: '#'.$proposal->id }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: "Times New Roman", serif; font-size: 13pt; line-height: 1.4; }
        .doc-head { display: grid; grid-template-columns: 1fr 1fr; gap: 18mm; text-align: center; font-weight: bold; }
        .doc-head p { margin: 0; }
        .underline { display: inline-block; min-width: 44mm; border-bottom: 1px solid #111; padding-bottom: 2mm; }
        .meta { margin-top: 8mm; text-align: right; font-style: italic; }
        h1 { margin: 9mm 0 3mm; text-align: center; font-size: 18pt; letter-spacing: .3px; text-transform: uppercase; }
        .code { margin-bottom: 7mm; text-align: center; font-size: 12pt; }
        .info { width: 100%; margin: 0 0 6mm; border-collapse: collapse; }
        .info td { padding: 2mm 0; vertical-align: top; }
        .info .label { width: 35mm; font-weight: bold; white-space: nowrap; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        table.items th, table.items td { border: 1px solid #111; padding: 2.2mm; vertical-align: top; }
        table.items th { text-align: center; font-weight: bold; }
        .center { text-align: center; }
        .reason { margin-top: 6mm; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 28mm; margin-top: 14mm; text-align: center; font-weight: bold; }
        .sign-box { min-height: 38mm; }
        .sign-note { display: block; margin-top: 1mm; font-weight: normal; font-style: italic; }
        .sign-img { display: block; width: 48mm; height: 24mm; object-fit: contain; margin: 3mm auto 1mm; }
        .no-print { margin: 0 0 8mm; font-family: Arial, sans-serif; font-size: 12px; }
        .no-print button { border: 1px solid #1d4ed8; background: #1d4ed8; color: #fff; border-radius: 6px; padding: 8px 12px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">In phiếu</button></div>

    <div class="doc-head">
        <div>
            <p>TRƯỜNG CAO ĐẲNG HẬU CẦN 2</p>
            <p>{{ mb_strtoupper($proposal->unit?->name ?: 'ĐƠN VỊ ĐỀ XUẤT', 'UTF-8') }}</p>
            <span class="underline"></span>
        </div>
        <div>
            <p>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
            <p>Độc lập - Tự do - Hạnh phúc</p>
            <span class="underline"></span>
        </div>
    </div>

    <p class="meta">Ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}</p>
    <h1>Phiếu đề xuất thanh lý vật tư</h1>
    <p class="code">Số: {{ $proposal->proposal_code ?: str_pad((string) $proposal->id, 4, '0', STR_PAD_LEFT) }}/ĐXVT</p>

    <table class="info">
        <tr>
            <td class="label">Loại đề xuất:</td>
            <td>{{ $typeLabels[$proposal->type] ?? $proposal->type }}</td>
            <td class="label">Trạng thái:</td>
            <td>{{ $statusLabels[$proposal->status] ?? $proposal->status }}</td>
        </tr>
        <tr>
            <td class="label">Đơn vị đề xuất:</td>
            <td>{{ $proposal->unit?->name ?: $proposal->proposed_by_display_name ?: '—' }}</td>
            <td class="label">Ngành nhận:</td>
            <td>{{ $proposal->nganh_code ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tiêu đề:</td>
            <td colspan="3">{{ $proposal->title }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 10mm;">STT</th>
                <th style="width: 30mm;">Mã vật tư</th>
                <th>Tên vật tư</th>
                <th style="width: 28mm;">Số lượng thực tế phòng</th>
                <th style="width: 28mm;">Số lượng đề xuất</th>
                <th style="width: 34mm;">Phòng / vị trí</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposalItems as $i => $item)
                @php
                    $actualQuantity = $item->asset?->quantity;
                    if ($actualQuantity === null && $item->material_id && $item->from_classroom_id) {
                        $actualQuantity = \Modules\Inventory\Models\InventoryAsset::where('material_id', $item->material_id)
                            ->where('classroom_id', $item->from_classroom_id)
                            ->sum('quantity');
                    }
                    $roomName = $item->fromClassroom?->name ?: $item->from_room_name ?: $item->location_note ?: '—';
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $item->material_code ?: $item->original_code ?: '—' }}</td>
                    <td>{{ $item->material_name ?: $item->name }}</td>
                    <td class="center">{{ $actualQuantity !== null ? number_format((float) $actualQuantity, 0, ',', '.') : '—' }}</td>
                    <td class="center">{{ number_format((float) $item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                    <td>{{ $roomName }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Chưa có vật tư trong phiếu.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="reason">
        <strong>Lý do thanh lý:</strong>
        <div>{{ $proposal->description ?: '—' }}</div>
    </div>

    <div class="signatures">
        <div class="sign-box">
            NGƯỜI ĐỀ XUẤT
            <span class="sign-note">(Ký, ghi rõ họ tên)</span>
            <br><br><br><br>
            {{ $proposal->proposed_by_display_name ?: '' }}
        </div>
        <div class="sign-box">
            NGƯỜI DUYỆT
            <span class="sign-note">(Ký, ghi rõ họ tên)</span>
            @if($signatureUrl)
                <img src="{{ $signatureUrl }}" class="sign-img" alt="Chữ ký">
            @else
                <br><br><br><br>
            @endif
            {{ auth()->user()->name }}
        </div>
    </div>

    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
