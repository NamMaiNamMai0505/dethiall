<?php

namespace Tests\Unit;

use Modules\Lms\Support\LmsCampus;
use Tests\TestCase;

class LmsCampusP2Test extends TestCase
{
    public function test_evaluate_gps_within_campus_center(): void
    {
        $r = LmsCampus::evaluateGps(LmsCampus::LAT, LmsCampus::LNG, 20);
        $this->assertTrue($r['provided']);
        $this->assertTrue($r['ok']);
        $this->assertSame(0, $r['distance_m']);
        $this->assertStringContainsString('trong bán kính', mb_strtolower($r['note']));
    }

    public function test_evaluate_gps_outside(): void
    {
        // ~ far from campus (Hanoi approx)
        $r = LmsCampus::evaluateGps(21.0285, 105.8542, 10);
        $this->assertTrue($r['provided']);
        $this->assertFalse($r['ok']);
        $this->assertGreaterThan(LmsCampus::radiusMeters(), $r['distance_m']);
    }

    public function test_evaluate_gps_missing(): void
    {
        $r = LmsCampus::evaluateGps(null, null);
        $this->assertFalse($r['provided']);
        $this->assertFalse($r['ok']);
    }

    public function test_evaluate_gps_poor_accuracy_fails(): void
    {
        $r = LmsCampus::evaluateGps(LmsCampus::LAT, LmsCampus::LNG, LmsCampus::MAX_ACCURACY_M + 50);
        $this->assertTrue($r['provided']);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('chính xác', mb_strtolower($r['note']));
    }

    public function test_meta_includes_radius(): void
    {
        $m = LmsCampus::meta();
        $this->assertArrayHasKey('radius_m', $m);
        $this->assertArrayHasKey('lat', $m);
        $this->assertGreaterThan(0, $m['radius_m']);
    }
}
