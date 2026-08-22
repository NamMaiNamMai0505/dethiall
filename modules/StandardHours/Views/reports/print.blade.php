<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo giờ chuẩn {{ $filters['year'] ?? '' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; }
        .pass { color: #166534; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .summary { margin-bottom: 12px; }
        .summary span { margin-right: 16px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">In / Lưu PDF</button>
        <button onclick="window.close()" style="padding: 8px 16px; cursor: pointer;">Đóng</button>
    </div>

    <h1>BÁO CÁO GIỜ CHUẨN GIẢNG VIÊN</h1>
    <div class="meta">
        {{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}:
        <strong>{{ app(\Modules\StandardHours\Services\PeriodService::class)->label($filters['year']) }}</strong> —
        Ngày xuất: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="summary">
        <span>Tổng: <strong>{{ $summary['total'] }}</strong></span>
        <span>Đạt: <strong class="pass">{{ $summary['passed'] }}</strong></span>
        <span>Không đạt: <strong class="fail">{{ $summary['failed'] }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã GV</th>
                <th>Họ tên</th>
                <th>Đơn vị</th>
                <th>Chức danh</th>
                <th>Trực tiếp giảng dạy</th>
                <th>HĐ CM</th>
                <th>Tổng GC</th>
                <th>ĐM GC</th>
                <th>CL GC</th>
                <th>NCKH</th>
                <th>ĐM NCKH</th>
                <th>CL NCKH</th>
                <th>KQ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->instructor->code }}</td>
                <td>{{ $row->instructor->name }}</td>
                <td>{{ $row->instructor->unit->name ?? '—' }}</td>
                <td>{{ $row->position->name ?? '—' }}</td>
                <td>{{ number_format($row->teaching_hours, 0) }}</td>
                <td>{{ number_format($row->conversion_hours, 0) }}</td>
                <td>{{ number_format($row->total_standard_hours, 0) }}</td>
                <td>{{ number_format($row->standard_norm_hours, 0) }}</td>
                <td>{{ number_format($row->standard_difference, 0) }}</td>
                <td>{{ number_format($row->research_hours, 0) }}</td>
                <td>{{ number_format($row->research_norm_hours, 0) }}</td>
                <td>{{ number_format($row->research_difference, 0) }}</td>
                <td class="{{ $row->meets_overall ? 'pass' : 'fail' }}">{{ $row->overall_result_text }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($autoPrint)
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
