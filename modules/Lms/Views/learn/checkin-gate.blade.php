@extends('layouts.lms-learner')

@section('title', 'Xác minh check-in · Điểm danh')

@section('content')
@php
    $postUrl = route('lms.learn.attendance.checkin', [$course, $session]);
    $probeUrls = $probeUrls ?? [];
    $needGps = $needGps ?? false;
    $campus = $campus ?? \Modules\Lms\Support\LmsCampus::meta();
@endphp
<div class="max-w-lg mx-auto mt-8 lms-card p-6 space-y-4">
    <h1 class="text-lg font-bold text-slate-900">
        <i class="bi bi-shield-check text-teal-700"></i> Xác minh điểm danh (P1/P2)
    </h1>
    <p class="text-sm text-slate-600">
        Buổi: <strong>{{ $session->title }}</strong>
        · {{ $session->session_date?->format('d/m/Y') }}
        · mode <strong>{{ $session->mode }}</strong>
    </p>
    <p class="text-sm text-slate-600">
        @if(count($probeUrls))
            Hệ thống thử <strong>probe LAN</strong> (URL chỉ reach khi Wi‑Fi trường).
        @endif
        @if($needGps)
            Cần <strong>GPS trong bán kính {{ $campus['radius_m'] ?? 450 }}m</strong> quanh {{ $campus['address'] ?? 'trường' }}.
        @endif
    </p>

    <div id="gate-status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
        Đang chuẩn bị…
    </div>

    @if(count($probeUrls))
        <ul class="text-xs font-mono text-slate-500 space-y-1 break-all">
            @foreach($probeUrls as $u)
                <li>· {{ $u }}</li>
            @endforeach
        </ul>
    @endif

    <form id="gate-form" method="POST" action="{{ $postUrl }}" class="hidden">
        @csrf
        @if(!empty($token))
            <input type="hidden" name="token" value="{{ $token }}">
        @endif
        <input type="hidden" name="probe_ok" id="probe_ok" value="{{ count($probeUrls) ? '0' : '1' }}">
        <input type="hidden" name="lat" id="lat" value="">
        <input type="hidden" name="lng" id="lng" value="">
        <input type="hidden" name="accuracy" id="accuracy" value="">
    </form>

    <button type="button" id="gate-retry" class="lms-btn-solid w-full hidden">Thử lại</button>
    <a href="{{ route('lms.learn.courses.show', $course) }}?tab=attendance"
       class="block text-center text-sm text-slate-500 hover:text-slate-800 mt-2">
        Huỷ · về phòng học
    </a>
</div>

<script>
(function () {
    const urls = @json(array_values($probeUrls));
    const needGps = @json((bool) $needGps);
    const statusEl = document.getElementById('gate-status');
    const form = document.getElementById('gate-form');
    const probeOk = document.getElementById('probe_ok');
    const retry = document.getElementById('gate-retry');

    async function tryProbe(url, ms) {
        const ctrl = new AbortController();
        const t = setTimeout(() => ctrl.abort(), ms);
        try {
            await fetch(url, { mode: 'no-cors', cache: 'no-store', signal: ctrl.signal, credentials: 'omit' });
            clearTimeout(t);
            return true;
        } catch (e) {
            clearTimeout(t);
            return false;
        }
    }

    function getGps(timeoutMs) {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ ok: false, error: 'Trình duyệt không hỗ trợ geolocation' });
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({
                    ok: true,
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: Math.round(pos.coords.accuracy || 0),
                }),
                (err) => resolve({ ok: false, error: err.message || 'Từ chối / lỗi GPS' }),
                { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 15000 }
            );
        });
    }

    async function run() {
        retry.classList.add('hidden');
        statusEl.className = 'rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-900';

        if (urls.length) {
            statusEl.textContent = 'Đang probe LAN…';
            let pok = false;
            for (const u of urls) {
                if (await tryProbe(u, 2500)) { pok = true; break; }
            }
            if (!pok) {
                statusEl.className = 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900';
                statusEl.textContent = 'Probe LAN FAIL. Kết nối Wi‑Fi trường rồi thử lại (hoặc nhờ GV điểm miệng).';
                retry.classList.remove('hidden');
                return;
            }
            probeOk.value = '1';
        }

        if (needGps) {
            statusEl.textContent = 'Đang lấy GPS… (cho phép định vị khi trình duyệt hỏi)';
            const g = await getGps(12000);
            if (!g.ok) {
                statusEl.className = 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900';
                statusEl.textContent = 'Không lấy được GPS: ' + (g.error || '') + '. Bật định vị / HTTPS rồi thử lại.';
                retry.classList.remove('hidden');
                return;
            }
            document.getElementById('lat').value = g.lat;
            document.getElementById('lng').value = g.lng;
            document.getElementById('accuracy').value = g.accuracy || '';
        }

        statusEl.className = 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900';
        statusEl.textContent = 'Xác minh OK — đang gửi điểm danh…';
        form.classList.remove('hidden');
        form.submit();
    }

    retry.addEventListener('click', run);
    run();
})();
</script>
@endsection
