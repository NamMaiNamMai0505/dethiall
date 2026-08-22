<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Biên bản bốc thăm đề thi</title>
    <style>
        @page { size: A4; margin: 16mm 20mm; }
        body { font-family: "Times New Roman", serif; font-size: 14px; line-height: 1.45; color: #111; }
        .no-print { position: fixed; right: 12px; top: 12px; font-family: Arial, sans-serif; }
        .no-print button { padding: 8px 14px; border: 0; border-radius: 5px; background: #1e3a8a; color: #fff; cursor: pointer; }
        .head { display: grid; grid-template-columns: 1fr 1.25fr; text-align: center; font-weight: 700; font-size: 12px; }
        .head em { display: block; font-weight: 400; }
        .title { text-align: center; margin: 18px 0 10px; }
        .title h1 { font-size: 19px; margin: 0 0 10px; }
        .date { text-align: right; font-style: italic; margin: 8px 0 12px; }
        .line { margin: 5px 0; }
        .codes { border-collapse: collapse; width: 58%; margin: 12px auto 26px; }
        .codes th, .codes td { border: 1px solid #111; padding: 7px; text-align: center; }
        .codes th { font-weight: 700; }
        .sign { display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; font-weight: 700; margin-top: 18px; }
        .sign .space { height: 48px; }
        .sign .name { font-weight: 400; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div id="print-actions" class="no-print">
        <button type="button" onclick="window.print()">In biên bản</button>
    </div>

    <header class="head">
        <div>TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br>BAN KT&amp;ĐBCLGDĐT</div>
        <div>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<em>Độc lập – Tự do – Hạnh phúc</em></div>
    </header>
    <div class="date">Thành phố Hồ Chí Minh, ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}</div>
    <section class="title"><h1>BIÊN BẢN BỐC THĂM ĐỀ THI</h1></section>
    <div>Hôm nay ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}, vào lúc {{ now()->format('H:i') }} tại ....................</div>
    <div class="line">Chúng tôi gồm:</div>
    <div class="line">1. Đại diện Ban KT&amp;ĐBCLGDĐT: <b>Quản trị viên hệ thống</b></div>
    <div class="line">2. Đại diện cán bộ coi thi: ....................</div>
    <div class="line">3. Đại diện học viên: .................... &nbsp;&nbsp; Lớp: <b>{{ $className }}</b></div>
    <div class="line">Đã tiến hành bốc thăm đề thi môn: <b>{{ $even->exam->subject->name ?? '' }}</b></div>
    <div class="line">Kết quả như sau:</div>
    <table class="codes">
        <thead><tr><th>Đề thi</th><th>Mã đề thi</th></tr></thead>
        <tbody>
            <tr><td>Đề chẵn</td><td><b>{{ $even->exam->code }}-D{{ str_pad($even->paper_number, 2, '0', STR_PAD_LEFT) }}</b></td></tr>
            <tr><td>Đề lẻ</td><td><b>{{ $odd->exam->code }}-D{{ str_pad($odd->paper_number, 2, '0', STR_PAD_LEFT) }}</b></td></tr>
        </tbody>
    </table>
    <div class="sign">
        <div>Ban KT&amp;ĐBCLGDĐT<div class="space"></div><div class="name">Quản trị viên hệ thống</div></div>
        <div>Cán bộ coi thi<div class="space"></div><div class="name">....................</div></div>
        <div>Học viên<div class="space"></div><div class="name">....................</div></div>
    </div>

    <script>
        const representativeName = @json($representativeName ?? 'Đại diện Ban Khảo thí');
        document.querySelectorAll('.sign .name').forEach(function (el, index) {
            if (index === 0) el.textContent = representativeName;
        });
        document.querySelectorAll('.line').forEach(function (el) {
            if (el.textContent.includes('Quản trị viên hệ thống')) {
                el.innerHTML = '1. Đại diện Ban KT&ĐBCLGDĐT: <b>' + representativeName + '</b>';
            }
        });
        window.addEventListener('afterprint', function () {
            document.getElementById('print-actions').style.display = 'block';
        });
    </script>
</body>
</html>
