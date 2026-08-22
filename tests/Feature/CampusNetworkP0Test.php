<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lms\Models\CampusNetworkSetting;
use Modules\Lms\Support\CampusNetwork;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * P0 — Test IP UI/API, validate store CIDR, evaluate match/mismatch.
 */
class CampusNetworkP0Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $plain;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['campus-network.index', 'campus-network.show', 'campus-network.create', 'campus-network.edit', 'campus-network.delete'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->admin = User::factory()->create([
            'email' => 'campus-admin@test.local',
            'status' => 1,
        ]);
        $this->admin->givePermissionTo([
            'campus-network.index',
            'campus-network.show',
            'campus-network.create',
            'campus-network.edit',
            'campus-network.delete',
        ]);

        $this->plain = User::factory()->create([
            'email' => 'campus-plain@test.local',
            'status' => 1,
        ]);
    }

    public function test_evaluate_allows_when_no_settings(): void
    {
        CampusNetworkSetting::query()->delete();

        $r = CampusNetwork::evaluate('8.8.8.8');
        $this->assertTrue($r['ok']);
        $this->assertStringContainsString('bỏ qua', mb_strtolower($r['note']));
    }

    public function test_evaluate_match_and_mismatch(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'LAN test',
            'ip_cidrs' => '10.50.0.0/16',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $ok = CampusNetwork::evaluate('10.50.1.9');
        $this->assertTrue($ok['ok']);
        $this->assertNotNull($ok['matched_setting']);

        $bad = CampusNetwork::evaluate('8.8.8.8');
        $this->assertFalse($bad['ok']);
        $this->assertNull($bad['matched_setting']);
    }

    public function test_evaluate_skips_when_require_off(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Optional',
            'ip_cidrs' => '10.0.0.0/8',
            'require_campus_network' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $r = CampusNetwork::evaluate('8.8.8.8');
        $this->assertTrue($r['ok']);
    }

    public function test_test_ip_page_requires_permission(): void
    {
        $this->actingAs($this->plain)
            ->get(route('campus-network.test-ip'))
            ->assertForbidden();
    }

    public function test_test_ip_page_ok_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('campus-network.test-ip'))
            ->assertOk()
            ->assertSee('Test IP', false)
            ->assertSee('TrustProxies', false);
    }

    public function test_test_ip_json_and_simulate(): void
    {
        CampusNetworkSetting::query()->delete();
        CampusNetworkSetting::query()->create([
            'name' => 'Sim LAN',
            'ip_cidrs' => '192.168.100.0/24',
            'require_campus_network' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $res = $this->actingAs($this->admin)
            ->getJson(route('campus-network.test-ip', ['json' => 1, 'ip' => '192.168.100.44']));

        $res->assertOk()
            ->assertJsonPath('client_ip', '192.168.100.44')
            ->assertJsonPath('evaluate.ok', true);

        $this->actingAs($this->admin)
            ->getJson(route('campus-network.test-ip', ['json' => 1, 'ip' => '1.1.1.1']))
            ->assertOk()
            ->assertJsonPath('evaluate.ok', false);
    }

    public function test_store_rejects_open_world_cidr(): void
    {
        $this->actingAs($this->admin)
            ->post(route('campus-network.store'), [
                'name' => 'Bad open',
                'ip_cidrs' => '0.0.0.0/0',
                'require_campus_network' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('ip_cidrs');

        $this->assertDatabaseMissing('campus_network_settings', ['name' => 'Bad open']);
    }

    public function test_store_rejects_invalid_cidr(): void
    {
        $this->actingAs($this->admin)
            ->post(route('campus-network.store'), [
                'name' => 'Bad syntax',
                'ip_cidrs' => 'not-a-cidr',
                'require_campus_network' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('ip_cidrs');
    }

    public function test_store_rejects_require_without_cidr(): void
    {
        $this->actingAs($this->admin)
            ->post(route('campus-network.store'), [
                'name' => 'Empty CIDR',
                'ip_cidrs' => '',
                'require_campus_network' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('ip_cidrs');
    }

    public function test_store_accepts_valid_and_normalizes(): void
    {
        $this->actingAs($this->admin)
            ->post(route('campus-network.store'), [
                'name' => 'Good LAN',
                'ip_cidrs' => '10.99.1.5, 192.168.50.0/24',
                'require_campus_network' => '1',
                'is_active' => '1',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('campus-network.index'))
            ->assertSessionHas('success');

        $row = CampusNetworkSetting::query()->where('name', 'Good LAN')->first();
        $this->assertNotNull($row);
        $this->assertSame('10.99.1.5/32,192.168.50.0/24', $row->ip_cidrs);
    }

    public function test_index_shows_diagnose_and_test_link(): void
    {
        $this->actingAs($this->admin)
            ->get(route('campus-network.index'))
            ->assertOk()
            ->assertSee('Test IP', false)
            ->assertSee(route('campus-network.test-ip', absolute: false), false);
    }

    public function test_diagnose_structure(): void
    {
        $d = CampusNetwork::diagnose('10.0.0.1');
        $this->assertArrayHasKey('client_ip', $d);
        $this->assertArrayHasKey('evaluate', $d);
        $this->assertArrayHasKey('headers', $d);
        $this->assertArrayHasKey('trusted_proxies', $d);
        $this->assertArrayHasKey('settings_summary', $d);
        $this->assertArrayHasKey('global_warnings', $d);
        $this->assertSame('10.0.0.1', $d['client_ip']);
    }
}
