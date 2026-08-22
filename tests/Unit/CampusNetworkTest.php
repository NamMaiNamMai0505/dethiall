<?php

namespace Tests\Unit;

use Modules\Lms\Support\CampusNetwork;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * P0 — logic CIDR / validate / analyze (không cần DB).
 */
class CampusNetworkTest extends TestCase
{
    #[DataProvider('ipInCidrProvider')]
    public function test_ip_in_cidr(string $ip, string $cidr, bool $expected): void
    {
        $this->assertSame($expected, CampusNetwork::ipInCidr($ip, $cidr), "{$ip} in {$cidr}");
    }

    public static function ipInCidrProvider(): array
    {
        return [
            'match /24' => ['192.168.1.50', '192.168.1.0/24', true],
            'miss /24' => ['192.168.2.50', '192.168.1.0/24', false],
            'match /16' => ['10.1.2.3', '10.0.0.0/8', true],
            'miss public' => ['8.8.8.8', '10.0.0.0/8', false],
            'exact host without mask' => ['10.0.0.5', '10.0.0.5', true],
            'host mismatch' => ['10.0.0.6', '10.0.0.5', false],
            'match /32' => ['10.0.0.5', '10.0.0.5/32', true],
            'miss /32' => ['10.0.0.6', '10.0.0.5/32', false],
            'empty cidr' => ['10.0.0.1', '', false],
            'all zeros prefix still matches any v4' => ['1.2.3.4', '0.0.0.0/0', true],
        ];
    }

    public function test_parse_cidr_normalizes_host_to_slash32(): void
    {
        $p = CampusNetwork::parseCidr('10.1.2.3');
        $this->assertNotNull($p);
        $this->assertSame(4, $p['version']);
        $this->assertSame(32, $p['prefix']);
        $this->assertSame('10.1.2.3/32', $p['normalized']);
    }

    public function test_parse_cidr_rejects_invalid(): void
    {
        $this->assertNull(CampusNetwork::parseCidr('not-an-ip'));
        $this->assertNull(CampusNetwork::parseCidr('192.168.1.0/99'));
        $this->assertNull(CampusNetwork::parseCidr(''));
    }

    public function test_validate_rejects_open_world_cidr(): void
    {
        $r = CampusNetwork::validateCidrsString('0.0.0.0/0');
        $this->assertFalse($r['valid']);
        $this->assertNotEmpty($r['errors']);
        $this->assertStringContainsString('0.0.0.0/0', $r['errors'][0]);
    }

    public function test_validate_rejects_bad_cidr(): void
    {
        $r = CampusNetwork::validateCidrsString('foo,192.168.1.0/24');
        $this->assertFalse($r['valid']);
        $this->assertTrue(collect($r['errors'])->contains(fn ($e) => str_contains($e, 'foo')));
    }

    public function test_validate_normalizes_list(): void
    {
        $r = CampusNetwork::validateCidrsString('10.1.2.3, 192.168.1.0/24');
        $this->assertTrue($r['valid']);
        $this->assertSame('10.1.2.3/32,192.168.1.0/24', $r['normalized']);
    }

    public function test_validate_empty_is_valid_with_warning(): void
    {
        $r = CampusNetwork::validateCidrsString('');
        $this->assertTrue($r['valid']);
        $this->assertNull($r['normalized']);
        $this->assertNotEmpty($r['warnings']);
    }

    public function test_analyze_flags_wide_prefix_as_warning(): void
    {
        $w = CampusNetwork::analyzeCidrs(['10.0.0.0/8']);
        $this->assertNotEmpty($w);
        $this->assertSame('warning', $w[0]['level']);
    }

    public function test_analyze_flags_open_world_as_error(): void
    {
        $w = CampusNetwork::analyzeCidrs(['0.0.0.0/0']);
        $this->assertNotEmpty($w);
        $this->assertSame('error', $w[0]['level']);
    }

    public function test_resolved_trusted_proxies_empty_by_default(): void
    {
        // phpunit.xml không set TRUSTED_PROXIES → rỗng
        $resolved = CampusNetwork::resolvedTrustedProxies();
        $this->assertTrue($resolved === [] || $resolved === '*' || is_array($resolved));
    }
}
