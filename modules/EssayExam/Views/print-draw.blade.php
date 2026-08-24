<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $withAnswers ? 'Đáp án đề thi' : 'Đề thi hết học phần' }}</title>
    <style>
        @page { size: A4; margin: 16mm 20mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 263mm; color: #111; font-family: "Times New Roman", Times, serif; font-size: 14px; line-height: 1.35; display: flex; flex-direction: column; }
        .no-print { position: fixed; top: 12px; right: 12px; display: flex; gap: 8px; z-index: 5; font-family: Arial, sans-serif; }
        .no-print button, .no-print a { padding: 8px 14px; border: 0; border-radius: 5px; background: #1e3a8a; color: #fff; cursor: pointer; text-decoration: none; font: inherit; }
        .no-print a { background: #475569; }
        .head { display: grid; grid-template-columns: 1fr 1fr; text-align: center; align-items: start; margin: 8mm 0 16px; }
        .head-left, .head-right { font-weight: 700; font-size: 14px; line-height: 1.35; }
        .head-right { font-size: 14px; }
        .head-right em { display: block; font-weight: 400; margin-top: 2px; }
        .title { text-align: center; margin-top: 4px; }
        .title h1 { margin: 0 0 8px; font-size: 18px; }
        .title .line { margin: 3px 0; font-size: 15px; }
        .title .line strong { display: inline-block; min-width: 72px; }
        .code { text-align: center; margin: 9px 0 15px; font-size: 15px; font-weight: 700; }
        .questions { margin-top: 2px; }
        .question { margin: 0 0 9px; }
        .question-title { font-weight: 700; }
        .content { display: inline; }
        .points { font-style: italic; margin-left: 3px; }
        .answer { margin: 4px 0 0 18px; white-space: pre-line; }
        .options { margin: 4px 0 0 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 2px 18px; }
        .section-title { margin: 14px 0 7px; font-weight: 700; }
        .end { text-align: center; font-weight: 700; margin: auto 0 10px; padding-top: 18px; }
        .rule { border-top: 1px solid #777; margin: 0 auto 7px; width: 92%; }
        .note { text-align: center; font-size: 12px; font-style: italic; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print actions"><button onclick="window.print()">In trang này</button><a href="{{ route('essay-exams.draw') }}">Quay lại Rút đề</a></div>
    <header class="head">
        <div class="head-left">TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br>KHOA ĐIỀU DƯỠNG</div>
        <div class="head-right">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<em>Độc lập – Tự do – Hạnh phúc</em></div>
    </header>
    <section class="title">
        <h1>BỘ CÂU HỎI{{ $withAnswers ? ' - ĐÁP ÁN' : '' }} THI TỰ LUẬN</h1>
        <div class="line"><strong>Môn:</strong> {{ $draw->exam->subject->name ?? $draw->exam->title }}</div>
        <div class="line"><strong>Lớp:</strong> {{ $draw->class_name ?: '—' }}</div>
        <div class="line"><strong>Ngày thi:</strong> {{ $draw->exam_date?->format('d/m/Y') ?? '—' }}</div>
        <div class="line"><strong>Thời gian:</strong> {{ $draw->exam->duration_minutes ?: 60 }} phút</div>
        <div class="line"><strong>Tổng điểm:</strong> {{ number_format($draw->question_points ? $questions->count() * (float) $draw->question_points : $questions->sum('points'), 2, ',', '.') }}</div>
        <div class="line"><strong>Loại phiếu:</strong> {{ $draw->draw_type === 'ODD' ? 'Lẻ' : 'Chẵn' }}</div>
    </section>
    <div class="code">Mã đề thi {{ $draw->exam->code }}-D{{ str_pad($draw->paper_number, 2, '0', STR_PAD_LEFT) }}</div>
    <main class="questions">
        @php($lastType = null)
        @php($sectionQuestionNumber = 0)
        @foreach($questions as $q)
            @if($q->question_type !== $lastType)
                <div class="section-title">{{ $q->question_type === 'multiple_choice' ? 'Phần 1: Trắc nghiệm' : 'Phần 2: Tự luận' }}</div>
                @php($lastType = $q->question_type)
                @php($sectionQuestionNumber = 0)
            @endif
            @php($sectionQuestionNumber++)
            <div class="question">
                <span class="question-title">Câu {{ $sectionQuestionNumber }}:</span>
                <span class="content">{{ $q->content }}</span>
                @if($withAnswers)
                    <span class="points">({{ rtrim(rtrim(number_format((float) ($draw->question_points ?: $q->points), 2, '.', ''), '0'), '.') }} điểm)</span>
                @endif
                @if($q->question_type === 'multiple_choice' && is_array($q->options))
                    <div class="options">@foreach($q->options as $key=>$option)<span><strong>{{ is_numeric($key) && (int) $key >= 0 && (int) $key <= 3 ? chr(65 + (int) $key) : strtoupper($key) }}.</strong> {{ $option }}</span>@endforeach</div>
                @endif
                @if($withAnswers)
                    <div class="answer"><strong>Đáp án/Barem:</strong> {{ $q->question_type === 'multiple_choice' && is_numeric($q->answer) ? chr(65 + (int) $q->answer) : ($q->answer ?: 'Chưa cập nhật') }}</div>
                @endif
            </div>
        @endforeach
    </main>
    <div class="end">HẾT</div>
    <div class="rule"></div>
    <div class="note">(Thí sinh không được sử dụng tài liệu, cán bộ coi thi không giải thích gì thêm)</div>
    @if($autoPrint)
        <script>window.addEventListener('load', function () { document.title = ''; setTimeout(function () { window.print(); }, 350); });</script>
    @endif
</body>
</html>
