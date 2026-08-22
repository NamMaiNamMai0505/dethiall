<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Lms\Models\CampusNetworkSetting;
use Modules\Lms\Support\CampusNetwork;
use Modules\Lms\Support\CheckinStats;
use Modules\Lms\Support\LmsCampus;

/**
 * Admin: cấu hình MAC AP / dải IP Wi‑Fi trường cho điểm danh QR LMS.
 * Chỉ super-admin / manager (permission campus-network.*) — không nằm portal GV.
 */
class CampusNetworkController extends ModuleBaseController
{
    public function __construct()
    {
        parent::__construct();
        // P0/P2: test IP + stats dùng quyền xem danh sách (cùng nhóm admin Wi‑Fi)
        $this->middleware('permission:campus-network.index')->only(['testIp', 'stats']);
    }

    public function index(Request $request)
    {
        $query = CampusNetworkSetting::query()->orderBy('sort_order')->orderBy('id');

        if ($request->filled('search')) {
            $s = $request->string('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('wifi_mac', 'like', "%{$s}%")
                    ->orWhere('ip_cidrs', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $networks = $query->paginate(20)->withQueryString();
        $diagnose = CampusNetwork::diagnose();

        return view('lms::campus-network.index', compact('networks', 'diagnose'));
    }

    /**
     * P0: trang chẩn đoán IP client + TrustProxies + CIDR.
     */
    public function testIp(Request $request)
    {
        $simulate = $request->query('ip');
        if (is_string($simulate) && $simulate !== '' && ! filter_var($simulate, FILTER_VALIDATE_IP)) {
            throw ValidationException::withMessages([
                'ip' => 'IP mô phỏng không hợp lệ.',
            ]);
        }

        $diagnose = CampusNetwork::diagnose(
            is_string($simulate) && $simulate !== '' ? $simulate : null
        );

        if ($request->wantsJson() || $request->boolean('json')) {
            // Không serialize Eloquent matched_setting
            $payload = $diagnose;
            $match = $payload['evaluate']['matched_setting'] ?? null;
            $payload['evaluate']['matched_setting'] = $match
                ? ['id' => $match->id, 'name' => $match->name]
                : null;

            return response()->json($payload);
        }

        return view('lms::campus-network.test-ip', compact('diagnose'));
    }

    /**
     * P2: thống kê attempt check-in (mạng / probe / GPS).
     */
    public function stats(Request $request)
    {
        $days = (int) $request->input('days', 14);
        $stats = CheckinStats::summary($days);
        $campus = LmsCampus::meta();

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json(['stats' => $stats, 'campus' => $campus]);
        }

        return view('lms::campus-network.stats', compact('stats', 'campus'));
    }

    public function create()
    {
        return view('lms::campus-network.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $warnings = $data['_cidr_warnings'] ?? [];
        unset($data['_cidr_warnings']);
        CampusNetworkSetting::create($data);

        $redirect = redirect()->route('campus-network.index')
            ->with('success', 'Đã thêm cấu hình Wi‑Fi trường.');

        if ($warnings !== []) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    public function show(CampusNetworkSetting $network)
    {
        $cidrAnalysis = CampusNetwork::analyzeCidrs($network->cidrList());

        return view('lms::campus-network.show', compact('network', 'cidrAnalysis'));
    }

    public function edit(CampusNetworkSetting $network)
    {
        return view('lms::campus-network.edit', compact('network'));
    }

    public function update(Request $request, CampusNetworkSetting $network)
    {
        $data = $this->validated($request);
        $warnings = $data['_cidr_warnings'] ?? [];
        unset($data['_cidr_warnings']);
        $network->update($data);

        $redirect = redirect()
            ->route('campus-network.index')
            ->with('success', 'Đã cập nhật cấu hình Wi‑Fi trường.');
        if ($warnings !== []) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    public function destroy(CampusNetworkSetting $network)
    {
        $network->delete();

        return redirect()
            ->route('campus-network.index')
            ->with('success', 'Đã xoá cấu hình Wi‑Fi.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'wifi_mac' => 'nullable|string|max:32',
            'ip_cidrs' => 'nullable|string|max:2000',
            'probe_url' => 'nullable|url|max:500',
            'require_campus_network' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'note' => 'nullable|string|max:2000',
        ]);

        $cidrCheck = CampusNetwork::validateCidrsString($data['ip_cidrs'] ?? null);
        if (! $cidrCheck['valid']) {
            throw ValidationException::withMessages([
                'ip_cidrs' => $cidrCheck['errors'],
            ]);
        }

        // 0.0.0.0/0 đã là error trong validate — chặn lưu
        $require = $request->boolean('require_campus_network', true);
        if ($require && ($cidrCheck['normalized'] === null || $cidrCheck['normalized'] === '')) {
            throw ValidationException::withMessages([
                'ip_cidrs' => 'Khi bật “Bắt buộc Wi‑Fi”, phải nhập ít nhất một dải IP/CIDR hợp lệ.',
            ]);
        }

        return [
            'name' => $data['name'],
            'wifi_mac' => CampusNetworkSetting::normalizeMac($data['wifi_mac'] ?? null),
            'ip_cidrs' => $cidrCheck['normalized'],
            'probe_url' => $data['probe_url'] ?? null,
            'require_campus_network' => $require,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'note' => $data['note'] ?? null,
            '_cidr_warnings' => $cidrCheck['warnings'],
        ];
    }
}
