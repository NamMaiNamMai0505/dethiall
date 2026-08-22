<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>DS trích ngang</title>
    <style>
        body{font-family:"Times New Roman",serif;font-size:11pt;margin:20px}
        h1{text-align:center;font-size:14pt}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{border:1px solid #333;padding:3px 5px}
        th{background:#eee}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">In</button></p>
<h1>DANH SÁCH TRÍCH NGANG{{ $class ? ' — '.$class->name : '' }}</h1>
<table>
    <thead>
    <tr>
        <th>STT</th><th>Họ tên</th><th>Mã</th><th>Lớp</th><th>CCCD</th><th>Địa chỉ</th><th>SĐT</th>
    </tr>
    </thead>
    <tbody>
    @foreach($students as $i => $s)
        @php $p = $profiles[$s->id] ?? null; @endphp
        <tr>
            <td style="text-align:center">{{ $i+1 }}</td>
            <td>{{ $s->name }}</td>
            <td>{{ $p->student_code ?? $s->code }}</td>
            <td>{{ $s->class?->name }}</td>
            <td>{{ $p->id_number ?? '' }}</td>
            <td>{{ $p->permanent_address ?? '' }}</td>
            <td>{{ $p->phone ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
