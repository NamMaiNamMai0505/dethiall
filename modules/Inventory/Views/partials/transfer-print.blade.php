<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quyết định {{ $transfer->decision_number ?: $transfer->id }}</title>
    <style>
        @page{size:A4;margin:18mm}*{box-sizing:border-box}body{font-family:"Times New Roman",serif;color:#111;font-size:14px;line-height:1.35}.head{display:grid;grid-template-columns:1fr 1.2fr;text-align:center;font-weight:bold}.under{text-decoration:underline}.number{font-weight:normal;margin-top:10px}h1{text-align:center;font-size:18px;margin:26px 0 10px}.subtitle{text-align:center;font-size:16px;font-weight:bold;text-decoration:underline;margin-bottom:18px}.justify{text-align:justify;margin:7px 0}table{width:100%;border-collapse:collapse;margin:10px 0 14px}th,td{border:1px solid #555;padding:6px;text-align:center}th{font-weight:bold}td.name{text-align:left}.bottom{display:grid;grid-template-columns:1.2fr 1fr;gap:25px;margin-top:32px}.receive b{display:block;margin-bottom:8px}.sign{text-align:center;font-weight:bold}.sign small{display:block;font-weight:normal;font-style:italic;margin-top:4px}.sign-space{height:115px}.no-print{font-family:Arial,sans-serif;margin:18px 0;text-align:center}.no-print a{display:inline-block;padding:8px 14px;border:1px solid #64748b;border-radius:6px;color:#1d4ed8;text-decoration:none}@media print{.no-print{display:none}}
    </style>
</head>
<body>
    <div class="head">
        <div>TỔNG CỤC HẬU CẦN<br><span class="under">TRƯỜNG CAO ĐẲNG HẬU CẦN 2</span><div class="number">Số: {{ $transfer->decision_number ?: '……/QĐ' }}</div></div>
        <div>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br><span class="under">Độc lập - Tự do - Hạnh phúc</span></div>
    </div>
    <h1>QUYẾT ĐỊNH</h1>
    <div class="subtitle">Về việc {{ $transfer->type === 'RECALL' ? 'thu hồi' : 'điều động' }} vật tư, trang bị kỹ thuật</div>
    <p class="justify">Căn cứ nhu cầu biên chế và nhu cầu huấn luyện;</p>
    <p class="justify">Căn cứ yêu cầu nhiệm vụ của các đơn vị;</p>
    <p class="justify">Theo đề nghị của đồng chí Trưởng: {{ $transfer->requesting_unit ?: '……………………' }};</p>
    <p class="justify"><b>Điều 1.</b> {{ $transfer->type === 'RECALL' ? 'Thu hồi' : 'Điều động' }} của {{ $sourceUnit }} {{ $transfer->type === 'RECALL' ? 'về' : 'cho' }} {{ $receiverUnit }} các loại vật tư, trang bị kỹ thuật cụ thể sau:</p>
    <table><thead><tr><th>STT</th><th>Mã số trang bị</th><th>Tên trang bị</th><th>ĐVT</th><th>Phân cấp</th><th>Số lượng</th><th>Ghi chú</th></tr></thead><tbody><tr><td>1</td><td>{{ $transfer->asset?->asset_code ?: '—' }}</td><td class="name">{{ $transfer->asset?->name ?: '—' }}</td><td>{{ $transfer->asset?->unit ?: 'Cái' }}</td><td>{{ $transfer->asset?->grade ?: '—' }}</td><td>{{ (int)$transfer->quantity }}</td><td></td></tr></tbody></table>
    <p class="justify"><b>Điều 2.</b> {{ $receiverUnit }} liên hệ với {{ $callingUnit }} để giao nhận tại kho {{ $sourceUnit }}.</p>
    <p class="justify"><b>Điều 3.</b> Quyết định có hiệu lực thi hành kể từ ngày ký. Chỉ huy {{ $receiverUnit }}, {{ $sourceUnit }} và các đơn vị có liên quan chịu trách nhiệm thi hành Quyết định này.</p>
    <div class="bottom"><div class="receive"><b>Nơi nhận:</b>- {{ $sourceUnit }};<br>- {{ $receiverUnit }};<br>- Lưu: Vật tư, QĐ số {{ $transfer->decision_number ?: '……' }};</div><div class="sign">HIỆU TRƯỞNG<small>(Ký, ghi rõ họ tên)</small><div class="sign-space"></div></div></div>
    <div class="no-print"><a href="{{ route('inventory.proposals.approval') }}">← Quay lại trang duyệt</a></div>
    <script>window.onload=()=>window.print();</script>
</body>
</html>
