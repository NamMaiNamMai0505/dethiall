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
        .head { width: 100%; border-collapse: collapse; text-align: center; margin: 8mm 0 18px; }
        .head td { width: 50%; vertical-align: top; padding: 0 6px; }
        .head-left, .head-right { font-weight: 700; font-size: 14px; line-height: 1.35; }
        .header-unit { text-decoration: underline; text-underline-offset: 4px; }
        .head-right { font-size: 14px; }
        .head-right em { display: block; font-weight: 400; font-style: normal; text-decoration: underline; text-underline-offset: 3px; margin-top: 2px; }
        .title { text-align: center; margin-top: 4px; }
        .title h1 { margin: 0 0 12px; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .meta { width: 360px; margin: 0 auto 14px; padding-left: 35px; text-align: left; font-size: 15px; }
        .meta .line { margin: 3px 0; }
        .questions { margin-top: 2px; }
        .question { margin: 0 0 9px; page-break-inside: avoid; }
        .question-title { font-weight: 700; }
        .content { display: inline; }
        .points { margin-left: 3px; }
        .answer-label { margin: 4px 0 2px; font-weight: 700; }
        .answer { margin: 0 0 7px 0; white-space: pre-line; }
        .options { margin: 4px 0 0 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 2px 18px; }
        .answer-key-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 13px; }
        .answer-key-table th, .answer-key-table td { border: 1px solid #222; padding: 4px 6px; text-align: center; }
        .answer-key-table th { font-weight: 700; }
        .section-title { margin: 14px 0 7px; font-weight: 700; }
        .end { text-align: center; font-weight: 700; margin: auto 0 6px; padding-top: 18px; }
        .note { text-align: center; font-size: 12px; font-style: italic; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php
        $isIntegrated = ($draw->exam->exam_type ?? '') === 'Tích hợp';
        $hasMultipleSections = $questions->pluck('question_type')->unique()->count() > 1;
        $formatAnswer = static fn ($answer) => preg_replace('/\R\s*(\[[^\x5D\r\n]*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\])/iu', ' $1', trim((string) $answer)) ?: trim((string) $answer);
        $formatPoint = static function ($point) {
            $value = (float) $point;
            $formatted = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
            return str_contains($formatted, ',') ? $formatted : $formatted.',0';
        };
        $formatAnswerLines = static function ($answer, $point = null) use ($formatAnswer, $formatPoint) {
            $lines = preg_split('/\r\n|\r|\n/', $formatAnswer($answer)) ?: [];
            $lines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));
            if ($point !== null && $lines) {
                $missingIndexes = [];
                $existingPointTotal = 0.0;
                foreach ($lines as $index => $line) {
                    if (preg_match('/\[\s*([\d]+(?:[,.][\d]+)?)\s*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\]/iu', $line, $match)) {
                        $existingPointTotal += (float) str_replace(',', '.', $match[1]);
                    } else {
                        $missingIndexes[] = $index;
                    }
                }
                if ($missingIndexes) {
                    $remainingPoint = (float) $point > $existingPointTotal ? (float) $point - $existingPointTotal : 0.0;
                    $pointPerMissingLine = $remainingPoint > 0 ? $remainingPoint / count($missingIndexes) : (float) $point / count($missingIndexes);
                    foreach ($missingIndexes as $index) {
                        $lines[$index] .= ' ['.$formatPoint($pointPerMissingLine).' điểm]';
                    }
                }
            }
            return implode("\n", array_map(fn ($line) => preg_match('/^[-–—•]/u', $line) ? '- '.trim(preg_replace('/^[-–—•]\s*/u', '', $line)) : '- '.$line, $lines));
        };
        $drawTypeLabel = $draw->draw_type === 'ODD' ? 'Lẻ' : 'Chẵn';
        $examCode = $draw->exam->code.'-D'.str_pad($draw->paper_number, 2, '0', STR_PAD_LEFT);
    @endphp
    <div class="no-print actions"><button onclick="window.print()">In trang này</button><a href="{{ route('essay-exams.draw') }}">Quay lại Rút đề</a></div>
    <table class="head">
        <tr>
            <td class="head-left">TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br><span class="header-unit">BAN KT&amp;ĐBCLGDĐT</span></td>
            <td class="head-right">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<em>Độc lập – Tự do – Hạnh phúc</em></td>
        </tr>
    </table>
    <section class="title">
        <h1>{{ $withAnswers ? 'ĐÁP ÁN ĐỀ THI HẾT HỌC PHẦN' : 'ĐỀ THI HẾT HỌC PHẦN' }}</h1>
        <div class="meta">
            <div class="line">Môn: {{ $draw->exam->subject->name ?? $draw->exam->title }}</div>
            <div class="line">Ngày thi: {{ $draw->exam_date?->format('d/m/Y') ?? '' }}</div>
            <div class="line">Lớp: {{ $draw->class_name ?: '' }}</div>
            <div class="line">Thời gian: {{ $draw->exam->duration_minutes ?: 60 }} phút</div>
            <div class="line">Loại đề: {{ $drawTypeLabel }}</div>
            <div class="line">Mã đề: {{ $examCode }}</div>
        </div>
    </section>
    @if($withAnswers && $isIntegrated)
        @php($mcqQuestions = $questions->where('question_type', 'multiple_choice')->values())
        @php($essayQuestions = $questions->where('question_type', '!=', 'multiple_choice')->values())
        <main class="questions">
            <div class="section-title">Phần I. Đáp án trắc nghiệm</div>
            <table class="answer-key-table">
                <thead><tr><th>Câu</th><th>Đáp án</th><th>Điểm</th><th>Câu</th><th>Đáp án</th><th>Điểm</th></tr></thead>
                <tbody>
                    @for($i = 0; $i < $mcqQuestions->count(); $i += 2)
                        @php($left = $mcqQuestions->get($i))
                        @php($right = $mcqQuestions->get($i + 1))
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ is_numeric($left->answer) ? chr(65 + (int) $left->answer) : ($left->answer ?: '—') }}</td>
                            <td>{{ $formatPoint($draw->question_points ?: $left->points) }}</td>
                            @if($right)
                                <td>{{ $i + 2 }}</td>
                                <td>{{ is_numeric($right->answer) ? chr(65 + (int) $right->answer) : ($right->answer ?: '—') }}</td>
                                <td>{{ $formatPoint($draw->question_points ?: $right->points) }}</td>
                            @else
                                <td></td><td></td><td></td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
            @if($essayQuestions->isNotEmpty())
                <div class="section-title">Phần II. Đáp án tự luận</div>
                @foreach($essayQuestions as $i => $q)
                    <div class="question"><span class="question-title">Câu {{ $i + 1 }}:</span> <span class="content">{{ $q->content }}</span> <span class="points">[{{ $formatPoint($q->points) }} điểm]</span><div class="answer-label">Đáp án/Barem:</div><div class="answer">{{ $q->answer ? $formatAnswerLines($q->answer, $q->points) : 'Chưa cập nhật' }}</div></div>
                @endforeach
            @endif
        </main>
    @else
    <main class="questions">
        @php($lastType = null)
        @php($sectionQuestionNumber = 0)
        @foreach($questions as $q)
            @if($q->question_type !== $lastType)
                @if($isIntegrated || $hasMultipleSections)
                <div class="section-title">{{ $q->question_type === 'multiple_choice' ? 'Phần 1: Trắc nghiệm' : 'Phần 2: Tự luận' }}</div>
                @endif
                @php($lastType = $q->question_type)
                @php($sectionQuestionNumber = 0)
            @endif
            @php($sectionQuestionNumber++)
            <div class="question">
                <span class="question-title">Câu {{ $sectionQuestionNumber }}:</span>
                <span class="content">{{ $q->content }}</span>
                <span class="points">[{{ $formatPoint($draw->question_points ?: $q->points) }} điểm]</span>
                @if($q->question_type === 'multiple_choice' && is_array($q->options))
                    <div class="options">@foreach($q->options as $key=>$option)<span><strong>{{ is_numeric($key) && (int) $key >= 0 && (int) $key <= 3 ? chr(65 + (int) $key) : strtoupper($key) }}.</strong> {{ $option }}</span>@endforeach</div>
                @endif
                @if($withAnswers)
                    <div class="answer-label">Đáp án/Barem:</div>
                    <div class="answer">{{ $q->question_type === 'multiple_choice' && is_numeric($q->answer) ? '- '.chr(65 + (int) $q->answer).' ['.$formatPoint($draw->question_points ?: $q->points).' điểm]' : ($q->answer ? $formatAnswerLines($q->answer, $draw->question_points ?: $q->points) : 'Chưa cập nhật') }}</div>
                @endif
            </div>
        @endforeach
    </main>
    @endif
    <div class="end">--------------------HẾT--------------------</div>
    <div class="note">(Thí sinh không được sử dụng tài liệu, cán bộ coi thi không giải thích gì thêm)</div>
    @if($autoPrint)
        <script>window.addEventListener('load', function () { document.title = ''; setTimeout(function () { window.print(); }, 350); });</script>
    @endif
</body>
</html>
