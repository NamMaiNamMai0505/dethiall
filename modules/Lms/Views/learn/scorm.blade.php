@extends('layouts.lms-learner')

@section('title', $scorm->title)

@section('content')
    <div class="mb-3 flex flex-wrap justify-between gap-2 items-center">
        <a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700 hover:underline">← {{ $course->title }}</a>
        <div class="flex flex-wrap gap-2 items-center text-xs">
            <span class="px-2 py-1 rounded bg-slate-100 text-slate-700">
                Trạng thái: <strong id="scorm-status">{{ $attempt->lesson_status ?? 'incomplete' }}</strong>
            </span>
            <span class="px-2 py-1 rounded bg-slate-100 text-slate-700">
                Điểm: <strong id="scorm-score">{{ $attempt->score_raw !== null ? $attempt->score_raw.($attempt->score_max ? '/'.$attempt->score_max : '') : '—' }}</strong>
            </span>
            @if($scorm->launchUrl())
                <a href="{{ $scorm->launchUrl() }}" target="_blank" class="text-sm px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 no-underline">Tab mới</a>
            @endif
            <button type="button" id="scorm-fs" class="text-sm px-3 py-1.5 rounded-lg bg-teal-600 text-white border-0 cursor-pointer">Toàn màn hình</button>
        </div>
    </div>

    <div id="scorm-shell" class="lms-card overflow-hidden bg-black">
        <div class="px-4 py-2 border-b border-slate-700 bg-slate-900 text-white text-sm flex justify-between gap-2 flex-wrap">
            <span class="font-semibold">{{ $scorm->title }}
                <span class="text-xs font-normal text-slate-400">· SCORM {{ $scorm->version ?? '1.2' }} · runtime track</span>
            </span>
            <span class="text-xs text-slate-400" id="scorm-commit-hint">API commit → tiến độ LMS</span>
        </div>
        @if($scorm->launchUrl())
            <iframe id="scorm-frame"
                    src="{{ $scorm->launchUrl() }}"
                    title="{{ $scorm->title }}"
                    class="w-full border-0 bg-white"
                    style="min-height: 78vh; height: 82vh;"
                    allowfullscreen
                    allow="fullscreen"></iframe>
        @else
            <div class="p-8 text-center text-sm text-slate-300 bg-slate-900">
                Không xác định được file launch (imsmanifest / index.html) trong gói SCORM.
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('scorm-fs')?.addEventListener('click', function () {
        const el = document.getElementById('scorm-shell') || document.getElementById('scorm-frame');
        if (!el) return;
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    });

    // Sprint 9 T3 — SCORM 1.2 API adapter (window parent)
    const commitUrl = @json(route('lms.learn.scorm.commit', [$course, $scorm]));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const store = {
        'cmi.core.lesson_status': @json($attempt->lesson_status ?? 'incomplete'),
        'cmi.core.score.raw': @json($attempt->score_raw !== null ? (string)$attempt->score_raw : ''),
        'cmi.core.score.max': @json($attempt->score_max !== null ? (string)$attempt->score_max : '100'),
        'cmi.core.score.min': @json($attempt->score_min !== null ? (string)$attempt->score_min : '0'),
        'cmi.core.lesson_location': @json($attempt->lesson_location ?? ''),
        'cmi.suspend_data': @json($attempt->suspend_data ?? ''),
        'cmi.core.session_time': '0000:00:00',
        'cmi.core.student_id': @json((string)auth()->id()),
        'cmi.core.student_name': @json(auth()->user()->name ?? ''),
        'cmi.core.credit': 'credit',
        'cmi.core.entry': @json($attempt->suspend_data ? 'resume' : 'ab-initio'),
        'cmi.core.exit': '',
    };
    let dirty = false;

    function commitToServer() {
        const payload = Object.assign({}, store);
        fetch(commitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ cmi: payload }),
            keepalive: true,
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.ok) return;
            const st = document.getElementById('scorm-status');
            const sc = document.getElementById('scorm-score');
            const hint = document.getElementById('scorm-commit-hint');
            if (st && data.attempt) st.textContent = data.attempt.lesson_status || st.textContent;
            if (sc && data.attempt && data.attempt.score_raw != null) {
                sc.textContent = data.attempt.score_raw + (data.attempt.score_max ? '/' + data.attempt.score_max : '');
            }
            if (hint) hint.textContent = 'Đã commit ' + new Date().toLocaleTimeString();
            dirty = false;
        }).catch(function () {});
    }

    const API = {
        LMSInitialize: function () { return 'true'; },
        LMSFinish: function () {
            store['cmi.core.exit'] = store['cmi.core.exit'] || 'suspend';
            commitToServer();
            return 'true';
        },
        LMSGetValue: function (key) { return store[key] != null ? String(store[key]) : ''; },
        LMSSetValue: function (key, val) { store[key] = val; dirty = true; return 'true'; },
        LMSCommit: function () { commitToServer(); return 'true'; },
        LMSGetLastError: function () { return '0'; },
        LMSGetErrorString: function () { return 'No error'; },
        LMSGetDiagnostic: function () { return ''; },
    };
    // SCORM 2004 aliases (minimal)
    API.Initialize = API.LMSInitialize;
    API.Terminate = API.LMSFinish;
    API.GetValue = API.LMSGetValue;
    API.SetValue = API.LMSSetValue;
    API.Commit = API.LMSCommit;
    API.GetLastError = API.LMSGetLastError;
    API.GetErrorString = API.LMSGetErrorString;
    API.GetDiagnostic = API.LMSGetDiagnostic;

    window.API = API;
    window.API_1484_11 = API;

    window.addEventListener('beforeunload', function () {
        if (dirty) commitToServer();
    });
    // Auto-commit mỗi 45s nếu có thay đổi
    setInterval(function () { if (dirty) commitToServer(); }, 45000);
})();
</script>
@endpush
