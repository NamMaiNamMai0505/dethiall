<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sổ điểm · {{ $session->title }}</title>
    <style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; color: #111; margin: 24px; }
        h1 { font-size: 16pt; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 13pt; text-align: center; margin: 0 0 16px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background: #f3f4f6; }
        .meta { margin-bottom: 12px; font-size: 11pt; }
        @media print { .no-print { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">In PDF / máy in</button></p>
    <h1>SỔ ĐIỂM · TỔNG KẾT / XÉT TỐT NGHIỆP</h1>
    <h2>{{ $session->title }} — Năm học {{ $session->academic_year }}</h2>
    <p class="meta">Trạng thái: {{ $session->statusLabel() }} · In lúc {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
        <tr>
            <th>STT</th>
            <th>Họ và tên</th>
            <th>Mã</th>
            <th>Lớp</th>
            <th>GPA</th>
            <th>Điểm TN</th>
            <th>Kết quả</th>
        </tr>
        </thead>
        <tbody>
        @foreach($results as $i => $r)
            <tr>
                <td style="text-align:center">{{ $r->rank_order ?? ($i+1) }}</td>
                <td>{{ $r->student?->name }}</td>
                <td>{{ $r->student?->code }}</td>
                <td>{{ $r->classModel?->name }}</td>
                <td style="text-align:center">{{ \Modules\Grades\Support\GradeSettings::format($r->gpa, '') }}</td>
                <td style="text-align:center">{{ \Modules\Grades\Support\GradeSettings::format($r->exam_score, '') }}</td>
                <td>{{ $r->statusLabel() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
