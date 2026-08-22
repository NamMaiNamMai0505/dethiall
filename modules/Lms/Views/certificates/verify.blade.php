<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh chứng chỉ LMS</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 min-h-screen">
<div class="max-w-lg mx-auto px-4 py-12">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Xác minh chứng chỉ</h1>
    <p class="text-sm text-slate-500 mb-6">Nhập mã chứng chỉ CDHC2-… để kiểm tra tính hợp lệ.</p>
    <form method="GET" class="flex gap-2 mb-6">
        <input name="code" value="{{ $code }}" placeholder="CDHC2-XXXX-..." class="flex-1 border rounded-lg px-3 py-2 text-sm font-mono">
        <button class="px-4 py-2 bg-teal-700 text-white rounded-lg text-sm">Kiểm tra</button>
    </form>
    @if($code !== '')
        @if($certificate)
            <div class="bg-white border border-emerald-200 rounded-xl p-5 space-y-2">
                <div class="text-emerald-700 font-semibold">✓ Chứng chỉ hợp lệ</div>
                <div class="text-sm"><span class="text-slate-500">HV:</span> {{ $certificate->user?->name }}</div>
                <div class="text-sm"><span class="text-slate-500">Khóa:</span> {{ $certificate->course?->title }}</div>
                <div class="text-sm"><span class="text-slate-500">Mã:</span> <span class="font-mono">{{ $certificate->code }}</span></div>
                <div class="text-sm"><span class="text-slate-500">Cấp:</span> {{ $certificate->issued_at?->format('d/m/Y H:i') }}</div>
            </div>
        @else
            <div class="bg-white border border-rose-200 rounded-xl p-5 text-rose-700 text-sm">
                Không tìm thấy chứng chỉ với mã này (hoặc đã thu hồi).
            </div>
        @endif
    @endif
</div>
</body>
</html>
