<?php

namespace Modules\Lms\Support;

use Modules\Lms\Models\CampusNetworkSetting;

/**
 * Kiểm tra client có đang “trên mạng trường” không.
 * Web không đọc được BSSID/MAC Wi‑Fi → dùng CIDR IP (+ ghi nhận MAC AP cấu hình).
 *
 * P0: validate CIDR, cảnh báo cấu hình nguy hiểm, diagnose IP + TrustProxies.
 * P1: LAN probe (probe_url) — client fetch URL chỉ reach được trong mạng trường.
 */
class CampusNetwork
{
    /**
     * @param  array{probe_ok?:bool|null,skip_probe?:bool}  $options
     * @return array{
     *   ok:bool,
     *   note:string,
     *   ip:?string,
     *   matched_setting:?CampusNetworkSetting,
     *   probe_required:bool,
     *   probe_urls:list<string>,
     *   probe_ok:?bool,
     *   probe_note:?string
     * }
     */
    public static function evaluate(?string $clientIp = null, array $options = []): array
    {
        $ip = $clientIp ?: request()->ip();
        $settings = CampusNetworkSetting::query()->active()->orderBy('sort_order')->get();
        $probeUrls = self::probeUrlsFrom($settings->all());

        $base = [
            'probe_required' => false,
            'probe_urls' => $probeUrls,
            'probe_ok' => array_key_exists('probe_ok', $options) ? ($options['probe_ok'] === null ? null : (bool) $options['probe_ok']) : null,
            'probe_note' => null,
        ];

        if ($settings->isEmpty()) {
            return array_merge($base, [
                'ok' => true,
                'note' => 'Chưa cấu hình mạng trường — bỏ qua kiểm tra Wi‑Fi.',
                'ip' => $ip,
                'matched_setting' => null,
            ]);
        }

        $anyRequire = $settings->contains(fn ($s) => $s->require_campus_network);
        if (! $anyRequire) {
            return array_merge($base, [
                'ok' => true,
                'note' => 'Không bắt buộc Wi‑Fi trường.',
                'ip' => $ip,
                'matched_setting' => null,
            ]);
        }

        $matched = null;
        $matchNote = null;
        foreach ($settings as $s) {
            if (! $s->require_campus_network) {
                continue;
            }
            foreach ($s->cidrList() as $cidr) {
                if (self::ipInCidr((string) $ip, $cidr)) {
                    $mac = $s->wifi_mac ? ' AP '.$s->wifi_mac : '';
                    $matched = $s;
                    $matchNote = 'IP '.$ip.' khớp '.$cidr.' ('.$s->name.$mac.')';
                    break 2;
                }
            }
        }

        if (! $matched) {
            $macs = $settings->pluck('wifi_mac')->filter()->implode(', ');

            return array_merge($base, [
                'ok' => false,
                'note' => 'IP '.$ip.' không thuộc dải Wi‑Fi trường'
                    .($macs ? ' (AP: '.$macs.')' : '')
                    .'. Hãy kết nối Wi‑Fi trường hoặc nhờ GV điểm miệng.',
                'ip' => $ip,
                'matched_setting' => null,
            ]);
        }

        // P1: nếu có probe_url trên cấu hình active+require → client phải chứng minh reach được LAN
        $requiredProbeUrls = self::probeUrlsFrom(
            $settings->filter(fn ($s) => $s->require_campus_network)->all()
        );
        $probeRequired = $requiredProbeUrls !== [] && empty($options['skip_probe']);
        $base['probe_required'] = $probeRequired;
        $base['probe_urls'] = $requiredProbeUrls;

        if ($probeRequired) {
            $probeOk = array_key_exists('probe_ok', $options) ? (bool) $options['probe_ok'] : false;
            $base['probe_ok'] = $probeOk;
            if (! $probeOk) {
                return array_merge($base, [
                    'ok' => false,
                    'note' => $matchNote.'. Chưa xác minh probe LAN (URL nội bộ). Kết nối Wi‑Fi trường rồi thử lại.',
                    'ip' => $ip,
                    'matched_setting' => $matched,
                    'probe_note' => 'probe_failed_or_missing',
                ]);
            }

            return array_merge($base, [
                'ok' => true,
                'note' => $matchNote.' · Probe LAN OK',
                'ip' => $ip,
                'matched_setting' => $matched,
                'probe_note' => 'probe_ok',
            ]);
        }

        return array_merge($base, [
            'ok' => true,
            'note' => $matchNote,
            'ip' => $ip,
            'matched_setting' => $matched,
        ]);
    }

    /**
     * URL probe đang active (require) — client dùng fetch no-cors.
     *
     * @return list<string>
     */
    public static function activeProbeUrls(): array
    {
        $settings = CampusNetworkSetting::query()->active()
            ->where('require_campus_network', true)
            ->orderBy('sort_order')
            ->get()
            ->all();

        return self::probeUrlsFrom($settings);
    }

    /**
     * @param  list<CampusNetworkSetting>|iterable<CampusNetworkSetting>  $settings
     * @return list<string>
     */
    public static function probeUrlsFrom(iterable $settings): array
    {
        $urls = [];
        foreach ($settings as $s) {
            $u = trim((string) ($s->probe_url ?? ''));
            if ($u !== '' && filter_var($u, FILTER_VALIDATE_URL)) {
                $urls[] = $u;
            }
        }

        return array_values(array_unique($urls));
    }

    /** Có bắt buộc client probe khi check-in Wi‑Fi không. */
    public static function isProbeRequired(): bool
    {
        return self::activeProbeUrls() !== [];
    }

    /**
     * Báo cáo chẩn đoán đầy đủ cho trang “Test IP”.
     *
     * @return array{
     *   client_ip:?string,
     *   evaluate:array{ok:bool,note:string,ip:?string,matched_setting:?CampusNetworkSetting},
     *   headers:array<string,string|null>,
     *   trusted_proxies:array{configured:bool,raw:?string,resolved:list<string>|string},
     *   settings_summary:list<array{id:int,name:string,require:bool,active:bool,cidrs:list<string>,warnings:list<array{level:string,message:string}>}>,
     *   global_warnings:list<array{level:string,message:string}>
     * }
     */
    public static function diagnose(?string $clientIp = null): array
    {
        $request = request();
        $ip = $clientIp ?: $request->ip();
        // Diagnose: đánh giá IP riêng; probe là bước client (hiển thị probe_urls riêng)
        $evaluate = self::evaluate($ip, ['skip_probe' => true]);

        $xff = $request->headers->get('X-Forwarded-For');
        $xri = $request->headers->get('X-Real-IP');
        $headers = [
            'X-Forwarded-For' => $xff,
            'X-Real-IP' => $xri,
            'CF-Connecting-IP' => $request->headers->get('CF-Connecting-IP'),
            'Remote-Addr' => $request->server('REMOTE_ADDR'),
        ];

        $proxyRaw = env('TRUSTED_PROXIES');
        $proxyConfigured = $proxyRaw !== null && trim((string) $proxyRaw) !== '';
        $resolved = self::resolvedTrustedProxies();

        $globalWarnings = [];
        if (! $proxyConfigured && ($xff || $xri)) {
            $globalWarnings[] = [
                'level' => 'warning',
                'message' => 'Request có header X-Forwarded-For / X-Real-IP nhưng TRUSTED_PROXIES chưa cấu hình — IP có thể là IP proxy, không phải client thật.',
            ];
        }
        if ($proxyConfigured && ($resolved === '*' || $resolved === ['*'])) {
            $globalWarnings[] = [
                'level' => 'info',
                'message' => 'TRUSTED_PROXIES=* — tin mọi proxy (ổn sau reverse proxy nội bộ; tránh public internet trực tiếp).',
            ];
        }

        $settings = CampusNetworkSetting::query()->orderBy('sort_order')->orderBy('id')->get();
        $summary = [];
        $activeRequire = 0;
        foreach ($settings as $s) {
            $cidrs = $s->cidrList();
            $warnings = self::analyzeCidrs($cidrs);
            if ($s->is_active && $s->require_campus_network) {
                $activeRequire++;
                if ($cidrs === []) {
                    $warnings[] = [
                        'level' => 'error',
                        'message' => 'Bắt buộc Wi‑Fi nhưng chưa có CIDR — mọi check-in sẽ fail.',
                    ];
                }
            }
            $summary[] = [
                'id' => $s->id,
                'name' => $s->name,
                'require' => (bool) $s->require_campus_network,
                'active' => (bool) $s->is_active,
                'cidrs' => $cidrs,
                'warnings' => $warnings,
            ];
        }

        if ($settings->isEmpty()) {
            $globalWarnings[] = [
                'level' => 'info',
                'message' => 'Chưa có bản ghi mạng trường — hệ thống bỏ qua kiểm tra Wi‑Fi khi điểm danh.',
            ];
        } elseif ($activeRequire === 0) {
            $globalWarnings[] = [
                'level' => 'info',
                'message' => 'Không có cấu hình active + bắt buộc — kiểm tra Wi‑Fi đang tắt.',
            ];
        }

        $probeUrls = self::activeProbeUrls();
        if ($probeUrls !== []) {
            $globalWarnings[] = [
                'level' => 'info',
                'message' => 'P1 LAN probe đang bật ('.count($probeUrls).' URL). Check-in sẽ yêu cầu trình duyệt reach được ít nhất một URL nội bộ.',
            ];
        }

        return [
            'client_ip' => $ip,
            'evaluate' => $evaluate,
            'headers' => $headers,
            'trusted_proxies' => [
                'configured' => $proxyConfigured,
                'raw' => $proxyRaw !== null ? (string) $proxyRaw : null,
                'resolved' => $resolved,
            ],
            'probe_urls' => $probeUrls,
            'probe_required' => $probeUrls !== [],
            'settings_summary' => $summary,
            'global_warnings' => $globalWarnings,
        ];
    }

    /**
     * Phân tích danh sách CIDR — level: error|warning|info.
     *
     * @param  list<string>  $cidrs
     * @return list<array{level:string,message:string,cidr?:string}>
     */
    public static function analyzeCidrs(array $cidrs): array
    {
        $out = [];
        foreach ($cidrs as $cidr) {
            $cidr = trim($cidr);
            if ($cidr === '') {
                continue;
            }
            $parsed = self::parseCidr($cidr);
            if ($parsed === null) {
                $out[] = [
                    'level' => 'error',
                    'message' => 'CIDR không hợp lệ: '.$cidr,
                    'cidr' => $cidr,
                ];

                continue;
            }

            if ($parsed['version'] === 4) {
                if ($parsed['prefix'] === 0 || $cidr === '0.0.0.0/0') {
                    $out[] = [
                        'level' => 'error',
                        'message' => 'CIDR 0.0.0.0/0 cho phép mọi IP — vô hiệu hoá kiểm tra mạng.',
                        'cidr' => $cidr,
                    ];
                } elseif ($parsed['prefix'] <= 8) {
                    $out[] = [
                        'level' => 'warning',
                        'message' => 'CIDR quá rộng (/'.$parsed['prefix'].') — dễ khớp mạng ngoài ý muốn: '.$cidr,
                        'cidr' => $cidr,
                    ];
                } elseif (in_array($cidr, ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'], true)) {
                    $out[] = [
                        'level' => 'info',
                        'message' => 'Dải private RFC1918: '.$cidr.' — ổn làm mặc định; nên thu hẹp theo DHCP thật của trường.',
                        'cidr' => $cidr,
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * Validate chuỗi ip_cidrs (comma/space separated). Trả về errors (chặn lưu) + warnings.
     *
     * @return array{valid:bool,normalized:?string,errors:list<string>,warnings:list<string>}
     */
    public static function validateCidrsString(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [
                'valid' => true,
                'normalized' => null,
                'errors' => [],
                'warnings' => ['Chưa nhập dải IP — nếu bật “Bắt buộc” thì check-in QR sẽ fail.'],
            ];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));
        $errors = [];
        $warnings = [];
        $normalized = [];

        foreach ($parts as $p) {
            $parsed = self::parseCidr($p);
            if ($parsed === null) {
                $errors[] = 'CIDR không hợp lệ: '.$p.' (vd: 192.168.1.0/24 hoặc 10.0.0.5)';

                continue;
            }
            $normalized[] = $parsed['normalized'];
            foreach (self::analyzeCidrs([$parsed['normalized']]) as $w) {
                if ($w['level'] === 'error') {
                    $errors[] = $w['message'];
                } elseif ($w['level'] === 'warning') {
                    $warnings[] = $w['message'];
                }
            }
        }

        return [
            'valid' => $errors === [],
            'normalized' => $normalized === [] ? null : implode(',', $normalized),
            'errors' => $errors,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @return array{version:4|6,prefix:int,normalized:string}|null
     */
    public static function parseCidr(string $cidr): ?array
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return null;
        }

        if (! str_contains($cidr, '/')) {
            if (filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return ['version' => 4, 'prefix' => 32, 'normalized' => $cidr.'/32'];
            }
            if (filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return ['version' => 6, 'prefix' => 128, 'normalized' => $cidr];
            }

            return null;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = trim($mask);
        if (! ctype_digit($mask) && ! (is_numeric($mask) && (string) (int) $mask === $mask)) {
            return null;
        }
        $prefix = (int) $mask;

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($prefix < 0 || $prefix > 32) {
                return null;
            }

            return ['version' => 4, 'prefix' => $prefix, 'normalized' => $subnet.'/'.$prefix];
        }

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($prefix < 0 || $prefix > 128) {
                return null;
            }

            return ['version' => 6, 'prefix' => $prefix, 'normalized' => $subnet.'/'.$prefix];
        }

        return null;
    }

    public static function ipInCidr(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }
        [$subnet, $mask] = explode('/', $cidr, 2);
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || ! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv6 đơn giản: exact match only
            return $ip === $subnet;
        }
        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subLong = ip2long($subnet);
        if ($ipLong === false || $subLong === false) {
            return false;
        }
        $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask));

        return ($ipLong & $maskLong) === ($subLong & $maskLong);
    }

    /** @return list<string>|string */
    public static function resolvedTrustedProxies(): array|string
    {
        $raw = env('TRUSTED_PROXIES');
        if ($raw === null || trim((string) $raw) === '') {
            return [];
        }
        $raw = trim((string) $raw);
        if ($raw === '*') {
            return '*';
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** @return list<array{id:int,name:string,wifi_mac:?string,ip_cidrs:?string}> */
    public static function activeList(): array
    {
        return CampusNetworkSetting::query()->active()->orderBy('sort_order')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'wifi_mac' => $s->wifi_mac,
                'ip_cidrs' => $s->ip_cidrs,
            ])->all();
    }
}
