<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu điểm · {{ $profile->major_name }}</title>
    <style>
        body{font-family:"Times New Roman",serif;margin:28px;font-size:12pt}
        h1{text-align:center;font-size:15pt}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        td{padding:6px;border-bottom:1px solid #ccc;vertical-align:top}
        .label{width:40%;color:#444}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">In</button></p>
<h1>PHIẾU ĐIỂM / HỒ SƠ TỐT NGHIỆP</h1>
<table>
    <tr><td class="label">Ngành</td><td>{{ $profile->major_name }}</td></tr>
    <tr><td class="label">Khóa / Hệ</td><td>{{ $profile->cohort }} · {{ $profile->system_type }}</td></tr>
    <tr><td class="label">Thời gian</td><td>{{ $profile->duration_text }}</td></tr>
    <tr><td class="label">Lớp</td><td>{{ $profile->classModel?->name }}</td></tr>
    <tr><td class="label">Số QĐ · Ngày</td><td>{{ $profile->decision_number }} · {{ optional($profile->decision_date)->format('d/m/Y') }}</td></tr>
    <tr><td class="label">Số vào sổ / Series</td><td>{{ $profile->registry_number }} / {{ $profile->series_number }} ({{ $profile->series_group }})</td></tr>
</table>
</body>
</html>
