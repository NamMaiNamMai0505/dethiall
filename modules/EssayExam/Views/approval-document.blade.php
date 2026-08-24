<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $document->decision_code }} - {{ $document->title }}</title>
    <style>
        @page { size: A4; margin: 16mm 15mm; }
        body { font-family: Arial, sans-serif; color:#111827; font-size:13px; line-height:1.45; }
        .toolbar { position: sticky; top:0; padding:10px; background:#eff6ff; border-bottom:1px solid #bfdbfe; text-align:right; }
        .toolbar a, .toolbar button { display:inline-block; padding:8px 12px; border-radius:6px; border:0; background:#2563eb; color:white; text-decoration:none; cursor:pointer; }
        .header { text-align:center; margin-bottom:16px; }
        .header strong { display:block; font-size:16px; }
        .header h1 { margin:14px 0 6px; font-size:18px; }
        .meta { width:100%; border-collapse:collapse; margin:10px 0 18px; }
        .meta td { border:1px solid #cbd5e1; padding:6px 8px; }
        .meta td:first-child { width:22%; font-weight:bold; background:#f8fafc; }
        .paper { page-break-before:always; }
        .paper:first-of-type { page-break-before:auto; }
        .paper-title { font-weight:bold; font-size:15px; padding:8px 0; border-bottom:2px solid #1e3a8a; }
        .question { margin:10px 0; page-break-inside:avoid; }
        .answer { margin-top:3px; white-space:pre-line; }
        .points { color:#475569; font-style:italic; }
        .signature { margin-top:28px; margin-left:60%; text-align:center; min-height:150px; }
        .signature img { display:block; width:180px; max-height:90px; object-fit:contain; margin:8px auto; }
        .signature strong { display:block; }
        @media print { .toolbar { display:none; } }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">In / Lưu PDF</button> <a href="{{ route('essay-exams.approval-documents.download', $document) }}">Tải văn bản</a></div>
<main>
    <div class="header">
        <strong>TRƯỜNG CAO ĐẲNG HẬU CẦN 2</strong>
        <div>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
        <div>Độc lập - Tự do - Hạnh phúc</div>
        <h1>VĂN BẢN PHÊ DUYỆT BỘ ĐỀ THI</h1>
        <div>Số: {{ $document->decision_code }}</div>
    </div>
    <table class="meta">
        <tr><td>Tên bộ đề</td><td>{{ $document->title }}</td></tr>
        <tr><td>Lớp</td><td>{{ $document->class_name ?: '—' }}</td></tr>
        <tr><td>Môn</td><td>{{ $document->subject_name ?: '—' }}</td></tr>
        <tr><td>Người phê duyệt</td><td>{{ $document->approver_name ?: '—' }}</td></tr>
        <tr><td>Thời gian phê duyệt</td><td>{{ $document->approved_at?->format('d/m/Y H:i') }}</td></tr>
    </table>
    <p><strong>Nội dung bộ đề đã được phê duyệt:</strong></p>
    @foreach($document->exam?->questions?->groupBy('paper_number') ?? [] as $paper => $questions)
        <section class="paper">
            <div class="paper-title">ĐỀ SỐ {{ $paper }}</div>
            @foreach($questions as $question)
                <div class="question">
                    <strong>Câu {{ $question->question_number }}.</strong> {{ $question->content }} <span class="points">({{ number_format((float) $question->points, 2, ',', '.') }} điểm)</span>
                    @if($question->answer)<div class="answer"><strong>Đáp án / barem:</strong> {{ $question->answer }}</div>@endif
                </div>
            @endforeach
        </section>
    @endforeach
    <div class="signature">
        <div>BAN GIÁM HIỆU</div>
        <div>(Ký, xác nhận)</div>
        @if($signatureUrl)<img src="{{ $signatureUrl }}" alt="Chữ ký BGH">@else<div style="height:90px"></div>@endif
        <strong>{{ $document->approver_name }}</strong>
    </div>
</main>
</body>
</html>
