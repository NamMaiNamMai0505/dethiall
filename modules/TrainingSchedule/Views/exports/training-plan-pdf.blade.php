<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        /* Khổ giấy và lề được cấu hình trực tiếp trong mPDF.
           Không khai báo `size` bằng @page vì mPDF 8.3 có thể tạo trang trắng
           khi CSS này được dùng đồng thời với format A3-L. */
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: dejavuserif, serif; font-size: 8pt; }
        .document { width: 100%; }
        .new-page { page-break-before: always; }
        table { border-collapse: collapse; width: 100%; }
        .identity td { border: 0; vertical-align: top; text-align: center; padding: 0 2mm; }
        .identity .org { width: 30%; font-weight: bold; line-height: 1.35; border-bottom: 0.25mm solid #000; padding-bottom: 1mm; }
        .identity .title { width: 43%; }
        .identity .title-main { font-size: 15pt; font-weight: bold; line-height: 1.2; }
        .identity .semester { margin-top: 1.5mm; font-size: 10pt; font-weight: bold; }
        .identity .class-info { width: 27%; text-align: left; line-height: 1.4; }
        .range-line { margin: 1.5mm 0 0.8mm; text-align: center; font-style: italic; font-size: 8pt; }
        .respect-line { margin: 0 0 1.5mm; text-align: center; font-size: 8pt; }
        .schedule { table-layout: fixed; font-size: 6.7pt; }
        .schedule th, .schedule td { border: 0.25mm solid #000; padding: 0.6mm 0.4mm; text-align: center; vertical-align: middle; }
        .schedule thead th { font-weight: bold; }
        .schedule .day-column { width: 8mm; font-size: 9pt; }
        .schedule .period-column { width: 14mm; white-space: nowrap; font-weight: bold; }
        .schedule .week-cell { line-height: 1.12; overflow-wrap: anywhere; }
        .schedule .subject { font-weight: bold; white-space: pre-line; }
        .diagonal-image, .date-image { display: block; width: 12mm; height: 6mm; margin: 0 auto; }
        .label-lines, .date-lines { display: flex; flex-direction: column; justify-content: space-between; min-height: 9mm; line-height: 1.1; }
        .label-lines .label-top { text-align: right; }
        .label-lines .label-bottom { text-align: left; }
        .date-lines .date-top { text-align: left; }
        .date-lines .date-bottom { text-align: right; }
        .note { margin-top: 1.5mm; font-size: 7pt; font-style: italic; }
        .date-line { margin-top: 1.5mm; text-align: right; font-size: 8pt; }
        .signatures { width: 100%; margin-top: 1mm; page-break-inside: avoid; table-layout: fixed; }
        .signatures td { border: 0; text-align: center; vertical-align: top; padding: 0 2mm; }
        .sign-role { font-weight: bold; line-height: 1.25; }
        .signature-image { display: block; width: 25mm; height: 14mm; object-fit: contain; margin: 1mm auto 0; }
        .sign-name { font-weight: bold; margin-top: 1mm; }
        /* Khối cuối trang: bảng môn bên trái, KÍ HIỆU CHUNG bên phải */
        .footer-blocks { margin-top: 1.5mm; page-break-inside: avoid; }
        .footer-blocks > tr > td { border: 0; vertical-align: top; padding: 0; }
        .footer-left { width: 58%; padding-right: 3mm !important; }
        .footer-right { width: 42%; }
        .legend { width: 100%; font-size: 5.6pt; }
        .legend th, .legend td { border: 0.2mm solid #000; padding: 0.35mm; text-align: center; }
        .legend .subject-name { text-align: left; }
        .legend .legend-total td { font-weight: bold; }
        .common-notes { width: 100%; font-size: 5.8pt; }
        .common-notes th, .common-notes td { border: 0.2mm solid #000; padding: 0.7mm; vertical-align: top; }
        .common-notes th { text-align: center; font-size: 7pt; }
        .common-notes .note-term { width: 30%; font-weight: bold; }
        .common-notes .note-meaning { text-align: left; }
    </style>
</head>
<body>
@foreach($documents as $document)
    @php($class = $document['class'])
    @php($meta = $document['meta'])
    <section class="document {{ $document['new_page'] ? 'new-page' : '' }}">
        <table class="identity">
            <tr>
                <td class="org">{!! nl2br(e($meta['org_left'] ?? '')) !!}</td>
                <td class="title">
                    <div class="title-main">{{ $meta['title'] ?? 'LỊCH HUẤN LUYỆN' }}</div>
                    <div class="semester">{{ $document['semester_line'] }}</div>
                </td>
                <td class="class-info">
                    <strong>Lớp: {{ $class['class_name'] }}</strong><br>
                    @if(!empty($meta['unit_name']))Đơn vị: {{ $meta['unit_name'] }}<br>@endif
                    @if(!empty($meta['class_size']))Sĩ số: {{ $meta['class_size'] }}<br>@endif
                    @if(!empty($meta['class_leader']))CN lớp: {{ $meta['class_leader'] }}<br>@endif
                    @if(!empty($meta['classroom']))Phòng học: {{ $meta['classroom'] }}@endif
                </td>
            </tr>
        </table>

        <div class="range-line">Từ {{ $class['start']->format('d/m/Y') }} đến {{ $class['end']->format('d/m/Y') }}</div>
        @if(!empty($meta['respect_line']))<div class="respect-line">{{ $meta['respect_line'] }}</div>@endif

        <table class="schedule">
            <thead>
                <tr>
                    <th class="day-column" rowspan="3">Thứ</th>
                    <th class="period-column">Tháng</th>
                    @foreach($document['month_spans'] as $span)
                        <th colspan="{{ $span['count'] }}">{{ $span['month'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    <th>Tuần</th>
                    @foreach($document['weeks'] as $week)<th>{{ $week['number'] }}</th>@endforeach
                </tr>
                <tr>
                    <th class="period-column">
                        @if($document['label_image'])
                            <img class="diagonal-image" src="{{ $document['label_image'] }}" alt="Ngày Tiết">
                        @else
                            <div class="label-lines"><span class="label-top">Ngày</span><span class="label-bottom">Tiết</span></div>
                        @endif
                    </th>
                    @foreach($document['weeks'] as $week)
                        <th>
                            @if($week['date_image'])
                                <img class="date-image" src="{{ $week['date_image'] }}" alt="{{ $week['start_day'] }}-{{ $week['end_day'] }}">
                            @else
                                <div class="date-lines"><span class="date-top">{{ $week['start_day'] }}</span><span class="date-bottom">{{ $week['end_day'] }}</span></div>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($document['days'] as $day)
                    @foreach($day['rows'] as $rowIndex => $row)
                        <tr>
                            @if($rowIndex === 0)
                                <td class="day-column" rowspan="{{ count($day['rows']) }}"><strong>{{ $day['number'] }}</strong></td>
                            @endif
                            <td class="period-column">{{ $row['period'] }}</td>
                            @foreach($row['cells'] as $cell)
                                <td class="week-cell"><span class="subject">{{ $cell }}</span></td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        @if(!empty($meta['note']))<div class="note">Ghi chú: {{ $meta['note'] }}</div>@endif

        {{-- Bảng môn (trái) và KÍ HIỆU CHUNG (phải) nằm cùng một hàng, giống biểu mẫu giấy --}}
        {{-- Chỉ dùng dạng biểu thức cho khối PHP trong file này. Nếu trộn thêm khối
             PHP nhiều dòng, Blade sẽ gộp nhầm từ khối đầu tiên tới thẻ đóng cuối
             cùng và bỏ compile toàn bộ đoạn nằm giữa. --}}
        @php($commonNotes = array_values(array_filter((array) ($meta['common_notes'] ?? []), fn ($n) => is_array($n) && trim((string) ($n['term'] ?? '')) !== '')))
        @php($legendTotals = collect(['credits', 'total', 'theory', 'practice', 'exam'])->mapWithKeys(fn ($k) => [$k => (int) $class['legend']->sum(fn ($s) => (int) ($s->{$k} ?? 0))])->all())
        @php($blank = fn ($v) => ((int) $v) === 0 ? '' : (string) (int) $v)

        @if($class['legend']->isNotEmpty() || $commonNotes !== [])
            <table class="footer-blocks">
                <tr>
                    <td class="footer-left">
                        @if($class['legend']->isNotEmpty())
                            <table class="legend">
                                <thead>
                                    <tr>
                                        <th>Kí hiệu</th><th>Tên môn học</th><th>Tín chỉ</th><th>Tổng số</th>
                                        <th>Lý thuyết</th><th>TH/TL/TT</th><th>Thi, KT</th><th>Khoa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($class['legend'] as $subject)
                                    <tr>
                                        <td>{{ $subject->code }}</td>
                                        <td class="subject-name">{{ $subject->name }}</td>
                                        <td>{{ $blank($subject->credits ?? 0) }}</td>
                                        <td>{{ $blank($subject->total ?? 0) }}</td>
                                        <td>{{ $blank($subject->theory ?? 0) }}</td>
                                        <td>{{ $blank($subject->practice ?? 0) }}</td>
                                        <td>{{ $blank($subject->exam ?? 0) }}</td>
                                        <td>{{ $subject->faculty }}</td>
                                    </tr>
                                @endforeach
                                    <tr class="legend-total">
                                        <td colspan="2">Tổng số</td>
                                        <td>{{ $legendTotals['credits'] }}</td>
                                        <td>{{ $legendTotals['total'] }}</td>
                                        <td>{{ $legendTotals['theory'] }}</td>
                                        <td>{{ $legendTotals['practice'] }}</td>
                                        <td>{{ $legendTotals['exam'] }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    </td>
                    <td class="footer-right">
                        @if($commonNotes !== [])
                            <table class="common-notes">
                                <thead>
                                    <tr><th colspan="2">{{ $meta['common_notes_title'] ?? 'KÍ HIỆU CHUNG' }}</th></tr>
                                </thead>
                                <tbody>
                                @foreach($commonNotes as $note)
                                    <tr>
                                        <td class="note-term">{{ $note['term'] ?? '' }}</td>
                                        <td class="note-meaning">{{ $note['meaning'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
        @endif

        <div class="date-line">{{ $meta['date_line'] ?? ('Ngày     tháng      năm '.$class['end']->format('Y')) }}</div>

        @if($document['signers'] !== [])
            <table class="signatures"><tr>
                @foreach($document['signers'] as $signer)
                    <td>
                        <div class="sign-role">{{ $signer['role_line1'] ?? '' }}</div>
                        @if(!empty($signer['role_line2']))<div class="sign-role">{{ $signer['role_line2'] }}</div>@endif
                        @if(!empty($signer['image_src']))<img class="signature-image" src="{{ $signer['image_src'] }}" alt="Chữ ký">@else<br><br>@endif
                        <div class="sign-name">{{ $signer['name'] ?? '' }}</div>
                    </td>
                @endforeach
            </tr></table>
        @endif
    </section>
@endforeach
</body>
</html>
