<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Văn bằng · {{ $profile->series_number }}</title>
    <style>
        body{font-family:"Times New Roman",serif;margin:40px;text-align:center}
        .frame{border:3px double #333;padding:40px 32px;max-width:640px;margin:0 auto}
        h1{font-size:18pt;letter-spacing:.08em}
        .sub{margin:8px 0 24px;font-size:12pt}
        .field{margin:10px 0;font-size:13pt}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">In văn bằng</button></p>
<div class="frame">
    <h1>VĂN BẰNG / CHỨNG CHỈ</h1>
    <p class="sub">{{ $profile->major_name }} · Khóa {{ $profile->cohort }}</p>
    <p class="field">Hệ: {{ $profile->system_type }} · Thời gian: {{ $profile->duration_text }}</p>
    <p class="field">Số QĐ: <strong>{{ $profile->decision_number }}</strong> ngày {{ optional($profile->decision_date)->format('d/m/Y') }}</p>
    <p class="field">Số vào sổ: {{ $profile->registry_number }} · Series: <strong>{{ $profile->series_number }}</strong></p>
    <p class="field">Ngày ký VB: {{ optional($profile->diploma_sign_date)->format('d/m/Y') }}</p>
</div>
</body>
</html>
