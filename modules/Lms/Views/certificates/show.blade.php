<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->code }} — Chứng chỉ</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print { .no-print { display:none !important; } body { background:#fff; } }
        .cert-frame {
            max-width: 860px; margin: 2rem auto; padding: 2.5rem;
            border: 8px double #0f766e; background: linear-gradient(180deg,#fff 0%,#f0fdfa 100%);
            box-shadow: 0 20px 50px -24px rgba(15,23,42,.35);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="no-print max-w-3xl mx-auto px-4 pt-4 flex justify-between text-sm">
        <a href="{{ url()->previous() }}" class="text-teal-700">← Quay lại</a>
        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-teal-700 text-white">In / PDF</button>
    </div>
    <div class="cert-frame text-center">
        <img src="{{ asset('images/brand-logo.png') }}" alt="Logo" class="h-16 w-16 mx-auto object-contain mb-3"
             onerror="this.style.display='none'">
        <div class="text-xs tracking-[0.25em] uppercase text-teal-800 font-semibold">Trường Cao đẳng Hậu cần 2</div>
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mt-4 mb-2">{{ $certificate->title }}</h1>
        <p class="text-slate-600 text-sm mb-6">Chứng nhận học viên đã hoàn thành khóa học LMS</p>
        <div class="text-xl font-semibold text-teal-900 py-3 border-y border-teal-200">
            {{ $certificate->user?->name }}
        </div>
        <p class="mt-6 text-slate-700">
            Khóa học: <strong>{{ $certificate->course?->title ?? $course->title }}</strong>
        </p>
        <div class="mt-4 flex justify-center gap-8 text-sm text-slate-600">
            <div>Điểm: <strong>{{ $certificate->final_score ?? '—' }}</strong></div>
            <div>Tiến độ: <strong>{{ $certificate->progress_pct !== null ? $certificate->progress_pct.'%' : '—' }}</strong></div>
        </div>
        @if($certificate->template?->body_html)
            <div class="mt-4 text-sm text-slate-600">{!! $certificate->template->body_html !!}</div>
        @endif
        <p class="mt-8 text-xs font-mono text-slate-500">Mã xác minh: {{ $certificate->code }}</p>
        <p class="text-xs text-slate-400 mt-1">Cấp ngày {{ $certificate->issued_at?->format('d/m/Y') }}
            · {{ $certificate->meta['issuer'] ?? ($certificate->template->issuer_name ?? config('app.name')) }}
        </p>
        <p class="text-xs text-teal-700 mt-3">
            Xác minh: {{ route('lms.certificates.verify', ['code' => $certificate->code]) }}
        </p>
    </div>
</body>
</html>
