<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lms\Models\CampusNetworkSetting;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Support\CampusNetwork;
use Tests\TestCase;

/**
 * P1 — probe_url gate + QR token TTL helpers.
 */
class CampusNetworkP1Test extends TestCase
{
    use RefreshDatabase;

    public function test_evaluate_requires_probe_when_probe_url_configured(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'LAN+Probe',
            'ip_cidrs' => '10.0.0.0/8',
            'probe_url' => 'http://10.1.1.1/health',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $fail = CampusNetwork::evaluate('10.2.3.4', ['probe_ok' => false]);
        $this->assertFalse($fail['ok']);
        $this->assertTrue($fail['probe_required']);
        $this->assertContains('http://10.1.1.1/health', $fail['probe_urls']);

        $ok = CampusNetwork::evaluate('10.2.3.4', ['probe_ok' => true]);
        $this->assertTrue($ok['ok']);
        $this->assertTrue($ok['probe_ok']);
        $this->assertStringContainsString('Probe LAN OK', $ok['note']);
    }

    public function test_evaluate_skip_probe_for_diagnose(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'LAN+Probe',
            'ip_cidrs' => '10.0.0.0/8',
            'probe_url' => 'http://10.1.1.1/health',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $r = CampusNetwork::evaluate('10.2.3.4', ['skip_probe' => true]);
        $this->assertTrue($r['ok']);
        $this->assertFalse($r['probe_required']);
    }

    public function test_active_probe_urls_ignores_invalid(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Bad',
            'ip_cidrs' => '10.0.0.0/8',
            'probe_url' => 'not-a-url',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        CampusNetworkSetting::query()->create([
            'name' => 'Good',
            'ip_cidrs' => '10.0.0.0/8',
            'probe_url' => 'https://intranet.cdhc2.local/ping',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $urls = CampusNetwork::activeProbeUrls();
        $this->assertSame(['https://intranet.cdhc2.local/ping'], $urls);
    }

    public function test_token_ttl_expiry_and_rotate(): void
    {
        $session = new LmsAttendanceSession([
            'lms_course_id' => 1,
            'title' => 'T',
            'session_date' => now()->toDateString(),
            'mode' => 'qr',
            'status' => 'open',
            'checkin_token' => 'old-token-value-xxxxxxxxxxxxxxxxxxxx',
            'qr_ttl_minutes' => 30,
            'token_expires_at' => now()->subMinute(),
        ]);
        // không save — unit pure
        $this->assertTrue($session->isTokenExpired());
        $this->assertFalse($session->isTokenValid('old-token-value-xxxxxxxxxxxxxxxxxxxx'));
        $this->assertFalse($session->allowsSelfCheckin());
    }

    public function test_initial_token_payload_respects_ttl_and_open_until(): void
    {
        $openUntil = now()->addMinutes(20);
        $p = LmsAttendanceSession::initialTokenPayload('qr', 120, $openUntil);
        $this->assertNotNull($p['checkin_token']);
        $this->assertSame(120, $p['qr_ttl_minutes']);
        $this->assertNotNull($p['token_expires_at']);
        // expires capped by open_until (20 min < 120)
        $this->assertTrue($p['token_expires_at']->lte($openUntil->copy()->addSecond()));
        $this->assertTrue($p['token_expires_at']->gte(now()->addMinutes(19)));

        $manual = LmsAttendanceSession::initialTokenPayload('manual');
        $this->assertNull($manual['checkin_token']);
    }
}
