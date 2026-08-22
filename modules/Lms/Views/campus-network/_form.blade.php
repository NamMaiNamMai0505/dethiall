@php
    /** @var \Modules\Lms\Models\CampusNetworkSetting|null $network */
    $network = $network ?? null;
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tên *</label>
        <input type="text" name="name" required maxlength="255"
               value="{{ old('name', $network->name ?? 'Wi‑Fi trường') }}"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
               placeholder="VD: Wi‑Fi chính CDHC2 · Nhà A">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">MAC Access Point (BSSID)</label>
        <input type="text" name="wifi_mac" maxlength="32"
               value="{{ old('wifi_mac', $network->wifi_mac ?? '') }}"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono"
               placeholder="AA:BB:CC:DD:EE:FF — cập nhật khi đổi router">
        <p class="text-xs text-slate-400 mt-1">Chỉ để quản trị theo dõi AP. Check-in web dùng dải IP bên dưới.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Dải IP / CIDR</label>
        <textarea name="ip_cidrs" rows="3"
                  class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono"
                  placeholder="10.10.0.0/16,192.168.1.0/24">{{ old('ip_cidrs', $network->ip_cidrs ?? '') }}</textarea>
        <div class="mt-1 space-y-1 text-xs text-slate-500">
            <p>Phân tách bằng dấu phẩy / khoảng trắng. IP client (sau TrustProxies) phải khớp một dải thì QR check-in mới OK.</p>
            <p><strong class="text-slate-600">Gợi ý:</strong> dùng dải DHCP thực tế của trường (vd <code class="bg-slate-100 px-1 rounded">10.20.0.0/16</code>), không dùng <code class="bg-slate-100 px-1 rounded">0.0.0.0/0</code>.</p>
            <p class="text-amber-700">CIDR quá rộng (≤ /8) sẽ bị cảnh báo; <code class="bg-amber-50 px-1 rounded">0.0.0.0/0</code> bị <strong>chặn lưu</strong>. Khi bật “Bắt buộc” phải có ít nhất một CIDR hợp lệ.</p>
            <p class="text-slate-400">IP đơn lẻ (vd <code class="bg-slate-100 px-1 rounded">10.1.2.3</code>) được chuẩn hoá thành <code class="bg-slate-100 px-1 rounded">/32</code>.</p>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Probe URL (P1 · LAN)</label>
        <input type="text" name="probe_url" maxlength="500"
               value="{{ old('probe_url', $network->probe_url ?? '') }}"
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono"
               placeholder="http://10.x.x.x/… hoặc https://portal-noi-bo.local/ping">
        <div class="mt-1 space-y-1 text-xs text-slate-500">
            <p>URL <strong>chỉ reach được trong mạng trường</strong>. Khi có probe + “Bắt buộc”, check-in sẽ bắt trình duyệt HV fetch URL này (no-cors, timeout ~2.5s).</p>
            <p class="text-amber-700">Server cloud thường <em>không</em> reach được URL LAN — probe chạy phía <strong>client</strong>. Dùng favicon/health endpoint HTTP nội bộ (không cần CORS).</p>
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Thứ tự</label>
            <input type="number" name="sort_order" min="0" max="9999"
                   value="{{ old('sort_order', $network->sort_order ?? 10) }}"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex flex-col justify-end gap-2 pb-1">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="require_campus_network" value="1"
                    @checked(old('require_campus_network', $network->require_campus_network ?? true))>
                Bắt buộc khi điểm danh QR
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1"
                    @checked(old('is_active', $network->is_active ?? true))>
                Đang dùng
            </label>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Ghi chú</label>
        <textarea name="note" rows="2" maxlength="2000"
                  class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  placeholder="Vị trí AP, SSID, liên hệ IT…">{{ old('note', $network->note ?? '') }}</textarea>
    </div>
</div>
