@extends('layouts.admin')

@section('title', 'Test IP · Wi‑Fi trường')
@section('page-title', 'Test IP / mạng trường')

@section('content')
@php
    /** @var array $diagnose */
    $ok = (bool) ($diagnose['evaluate']['ok'] ?? false);
    $ip = $diagnose['client_ip'] ?? '—';
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Wi‑Fi trường', 'url' => route('campus-network.index')],
    ['title' => 'Test IP'],
]" />

<x-page-header
    title="TEST IP / MẠNG TRƯỜNG (P0)"
    subtitle="Chẩn đoán IP client, TrustProxies và CIDR — không đọc SSID/MAC máy."
    :actions="[
        ['url' => route('campus-network.index'), 'label' => 'Danh sách Wi‑Fi', 'icon' => 'list-ul', 'color' => 'secondary'],
    ]" />

{{-- Kết quả chính --}}
<div class="mb-4 rounded-xl border px-4 py-4 {{ $ok ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
    <div class="flex flex-wrap items-start gap-4 justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide {{ $ok ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $ok ? 'IP được chấp nhận (mạng trường / bỏ qua kiểm tra)' : 'IP không khớp dải Wi‑Fi trường' }}
            </p>
            <p class="mt-1 text-2xl font-mono font-bold text-slate-900">{{ $ip }}</p>
            <p class="mt-2 text-sm text-slate-700">{{ $diagnose['evaluate']['note'] ?? '' }}</p>
        </div>
        <form method="GET" action="{{ route('campus-network.test-ip') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Mô phỏng IP (tuỳ chọn)</label>
                <input type="text" name="ip" value="{{ request('ip') }}"
                       placeholder="VD: 10.1.2.3"
                       class="border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono w-44">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                Kiểm tra
            </button>
            <a href="{{ route('campus-network.test-ip') }}" class="px-4 py-2 rounded-lg border text-sm text-slate-700 hover:bg-slate-50">
                IP thật
            </a>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    {{-- TrustProxies --}}
    <div class="bg-white rounded-xl border shadow-sm p-5">
        <h3 class="font-bold text-slate-800 mb-3"><i class="bi bi-shield-check text-blue-600"></i> TrustProxies</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-2">
                <dt class="text-slate-500">TRUSTED_PROXIES</dt>
                <dd class="font-mono text-slate-900 text-right break-all">
                    {{ $diagnose['trusted_proxies']['configured']
                        ? ($diagnose['trusted_proxies']['raw'] ?? '—')
                        : 'chưa set' }}
                </dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-slate-500">Đã bật trust</dt>
                <dd class="font-semibold {{ $diagnose['trusted_proxies']['configured'] ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $diagnose['trusted_proxies']['configured'] ? 'Có' : 'Không' }}
                </dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-slate-500 leading-relaxed">
            Sau reverse proxy / Nginx / LB: set <code class="bg-slate-100 px-1 rounded">TRUSTED_PROXIES=*</code>
            hoặc IP proxy, rồi <code class="bg-slate-100 px-1 rounded">php artisan config:clear</code>.
            Không set → <code>request()->ip()</code> có thể là IP proxy.
        </p>
    </div>

    {{-- Headers --}}
    <div class="bg-white rounded-xl border shadow-sm p-5">
        <h3 class="font-bold text-slate-800 mb-3"><i class="bi bi-hdd-network text-indigo-600"></i> Headers request</h3>
        <dl class="space-y-2 text-sm font-mono">
            @foreach($diagnose['headers'] as $h => $v)
                <div class="flex justify-between gap-2 border-b border-slate-50 pb-1">
                    <dt class="text-slate-500 text-xs">{{ $h }}</dt>
                    <dd class="text-slate-900 text-right break-all text-xs">{{ $v ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>

{{-- Global warnings --}}
@if(!empty($diagnose['global_warnings']))
    <div class="mb-4 space-y-2">
        @foreach($diagnose['global_warnings'] as $w)
            @php
                $cls = match($w['level'] ?? 'info') {
                    'error' => 'border-rose-200 bg-rose-50 text-rose-900',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
                    default => 'border-sky-200 bg-sky-50 text-sky-950',
                };
            @endphp
            <div class="rounded-lg border px-3 py-2 text-sm {{ $cls }}">
                <strong class="uppercase text-[10px] tracking-wide">{{ $w['level'] ?? 'info' }}</strong>
                — {{ $w['message'] }}
            </div>
        @endforeach
    </div>
@endif

{{-- Settings --}}
<div class="bg-white rounded-xl border shadow-sm p-5">
    <h3 class="font-bold text-slate-800 mb-3"><i class="bi bi-list-check text-teal-700"></i> Cấu hình đã lưu</h3>
    @if(empty($diagnose['settings_summary']))
        <p class="text-sm text-slate-500">Chưa có bản ghi. <a href="{{ route('campus-network.create') }}" class="text-blue-600 underline">Thêm AP / dải IP</a></p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Tên</th>
                        <th class="px-3 py-2 text-left">CIDR</th>
                        <th class="px-3 py-2 text-left">Bắt buộc</th>
                        <th class="px-3 py-2 text-left">TT</th>
                        <th class="px-3 py-2 text-left">Cảnh báo</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($diagnose['settings_summary'] as $row)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 font-mono text-xs">
                                {{ $row['cidrs'] ? implode(', ', $row['cidrs']) : '—' }}
                            </td>
                            <td class="px-3 py-2">{{ $row['require'] ? 'Có' : 'Không' }}</td>
                            <td class="px-3 py-2">{{ $row['active'] ? 'ON' : 'OFF' }}</td>
                            <td class="px-3 py-2 text-xs">
                                @forelse($row['warnings'] as $w)
                                    <div class="{{ ($w['level'] ?? '') === 'error' ? 'text-rose-600' : (($w['level'] ?? '') === 'warning' ? 'text-amber-700' : 'text-slate-500') }}">
                                        {{ $w['message'] }}
                                    </div>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- P1: client probe --}}
<div class="mt-4 bg-white rounded-xl border shadow-sm p-5">
    <h3 class="font-bold text-slate-800 mb-2"><i class="bi bi-broadcast-pin text-teal-700"></i> Probe LAN (P1)</h3>
    <p class="text-sm text-slate-600 mb-3">
        Thử từ <strong>trình duyệt của bạn</strong> (không phải server). Cần đang online Wi‑Fi trường nếu URL chỉ LAN.
    </p>
    @php $probeUrls = $diagnose['probe_urls'] ?? []; @endphp
    @if(empty($probeUrls))
        <p class="text-sm text-slate-500">Chưa cấu hình <code class="bg-slate-100 px-1 rounded">probe_url</code> trên bản ghi active + bắt buộc.</p>
    @else
        <ul id="probe-url-list" class="text-xs font-mono text-slate-600 space-y-1 mb-3">
            @foreach($probeUrls as $u)
                <li data-probe-url="{{ $u }}">· {{ $u }}</li>
            @endforeach
        </ul>
        <button type="button" id="btn-run-probe" class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">
            Chạy probe client
        </button>
        <div id="probe-result" class="mt-3 text-sm hidden"></div>
        <script>
        (function () {
            const btn = document.getElementById('btn-run-probe');
            const out = document.getElementById('probe-result');
            const urls = @json(array_values($probeUrls));
            async function tryOne(url) {
                const ctrl = new AbortController();
                const t = setTimeout(() => ctrl.abort(), 2500);
                try {
                    await fetch(url, { mode: 'no-cors', cache: 'no-store', signal: ctrl.signal, credentials: 'omit' });
                    clearTimeout(t);
                    return true;
                } catch (e) {
                    clearTimeout(t);
                    return false;
                }
            }
            btn?.addEventListener('click', async () => {
                btn.disabled = true;
                out.classList.remove('hidden');
                out.className = 'mt-3 text-sm text-slate-600';
                out.textContent = 'Đang thử…';
                let ok = false; let hit = null;
                for (const u of urls) {
                    if (await tryOne(u)) { ok = true; hit = u; break; }
                }
                if (ok) {
                    out.className = 'mt-3 text-sm text-emerald-800 font-semibold';
                    out.textContent = 'Probe OK — reach được: ' + hit;
                } else {
                    out.className = 'mt-3 text-sm text-rose-700 font-semibold';
                    out.textContent = 'Probe FAIL — không reach URL nào (đổi Wi‑Fi / kiểm tra URL).';
                }
                btn.disabled = false;
            });
        })();
        </script>
    @endif
</div>

<p class="mt-4 text-xs text-slate-500">
    JSON API: <code class="bg-slate-100 px-1 rounded">{{ route('campus-network.test-ip', ['json' => 1]) }}</code>
    · thêm <code class="bg-slate-100 px-1 rounded">?ip=10.1.2.3</code> để mô phỏng.
</p>
@endsection
