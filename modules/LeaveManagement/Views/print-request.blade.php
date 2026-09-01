<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Giấy nghỉ phép #{{ $request->id }}</title>
    <style>
        @page { size: A4; margin: 16mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: "Times New Roman", serif; font-size: 16px; }
        .sheet { min-height: 265mm; padding-top: 2mm; }
        .heads { display: grid; grid-template-columns: 42% 58%; text-align: center; line-height: 1.25; }
        .heads strong { display: block; font-size: 16px; }
        .heads .right strong { font-size: 15px; }
        .heads .right em { display: block; margin-top: 4px; font-size: 15px; }
        .number { margin: 18px 0 0 10px; }
        h1 { margin: 34px 0 28px; text-align: center; font-size: 22px; }
        .info { margin-left: 24mm; line-height: 1.9; }
        .row { display: grid; grid-template-columns: 42mm 1fr; }
        .label { white-space: nowrap; }
        .name { font-weight: bold; text-transform: uppercase; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; margin-top: 70px; text-align: center; font-weight: bold; }
        .hint { display: block; font-weight: normal; font-style: italic; margin-top: 4px; }
        .sign-space { height: 64mm; }
        .actions { margin: 15px auto; text-align: center; font-family: Arial, sans-serif; }
        .actions button { padding: 8px 18px; cursor: pointer; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
<main class="sheet">
    <div class="heads">
        <div><strong>TỔNG CỤC HẬU CẦN</strong><strong>TRƯỜNG CAO ĐẲNG HẬU CẦN 2</strong></div>
        <div class="right"><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><strong>Độc lập - Tự do - Hạnh phúc</strong><em>Thành phố Hồ Chí Minh, ngày {{ $request->created_at?->format('d') }} tháng {{ $request->created_at?->format('m') }} năm {{ $request->created_at?->format('Y') }}</em></div>
    </div>
    <div class="number">Số: {{ $request->id }}/GNP-CDHC</div>
    <h1>GIẤY NGHỈ PHÉP</h1>
    <div class="info">
        <div class="row"><span class="label">Họ và tên:</span><span class="name">{{ $request->personnel?->name ?? $request->personnel_name }}</span></div>
        <div class="row"><span class="label">Cấp bậc:</span><span>{{ $request->personnel?->rank ?? $request->rank ?? '—' }}</span></div>
        <div class="row"><span class="label">Chức vụ:</span><span>{{ $request->personnel?->position ?? $request->position ?? '—' }}</span></div>
        <div class="row"><span class="label">Đơn vị:</span><span>{{ ($printUnitPath ?? $request->unit_name ?? $request->personnel?->unitRelation?->name) ?: '—' }}</span></div>
        <div class="row"><span class="label">Được nghỉ từ:</span><span>07h00 ngày {{ $request->from_date?->format('d/m/Y') }}</span></div>
        <div class="row"><span class="label">Đến:</span><span>17h00 ngày {{ $request->to_date?->format('d/m/Y') }}</span></div>
        <div class="row"><span class="label">Nơi nghỉ phép:</span><span>{{ ($printLocalityPath ?? $request->locality_path) ?: '—' }}</span></div>
        @php
            $printReason = trim((string) ($request->reason ?? ''));
            if ($request->leave_type === 'ANNUAL') {
                $printReason = ($printReason ?: 'Nghỉ phép năm') . ' ' . now()->year;
            } elseif ($printReason === '') {
                $printReason = 'Nghỉ phép.';
            }
        @endphp
        <div class="row"><span class="label">Lý do:</span><span>{{ $printReason }}</span></div>
        @if($request->decision_note)
            <div class="row"><span class="label">Lý do trả về/từ chối:</span><span>{{ $request->decision_note }}</span></div>
        @endif
    </div>
    <div class="signatures">
        <div><div>XÁC NHẬN</div><div>Của chính quyền địa phương<br>nơi nghỉ phép</div><span class="hint">(Ký, đóng dấu)</span><div class="sign-space"></div></div>
        <div><div>KT. HIỆU TRƯỞNG</div><div>PHÓ HIỆU TRƯỞNG</div><div class="sign-space"></div><div>........................................</div></div>
    </div>
</main>
<div class="actions"><button onclick="window.print()">In giấy nghỉ phép</button><a href="{{ route('leave-management.approvals') }}">← Quay lại trang duyệt</a></div>
@if(request()->boolean('autoprint'))
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
@endif
</body>
</html>
