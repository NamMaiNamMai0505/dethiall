<?php

namespace Modules\Lms\Support;

/**
 * Vị trí campus dùng cho điểm danh GPS (P2 soft/hard signal).
 * Trường Cao đẳng Hậu cần 2 — 50 Lê Văn Việt, Tăng Nhơn Phú, TP.HCM.
 */
class LmsCampus
{
    /** @var float */
    public const LAT = 10.8471500;

    /** @var float */
    public const LNG = 106.7963500;

    /** Bán kính cho phép điểm danh (mét) — override bằng CAMPUS_RADIUS_M */
    public const RADIUS_M = 450;

    public const ADDRESS = '50 Lê Văn Việt, Tăng Nhơn Phú, TP. Hồ Chí Minh';

    /** Độ chính xác GPS tối đa chấp nhận (mét); null = không giới hạn */
    public const MAX_ACCURACY_M = 200;

    public static function radiusMeters(): int
    {
        $env = env('CAMPUS_RADIUS_M');
        if ($env !== null && $env !== '' && is_numeric($env)) {
            return max(50, min(5000, (int) $env));
        }

        return self::RADIUS_M;
    }

    public static function distanceMeters(float $lat, float $lng): float
    {
        $earth = 6371000; // m
        $φ1 = deg2rad(self::LAT);
        $φ2 = deg2rad($lat);
        $Δφ = deg2rad($lat - self::LAT);
        $Δλ = deg2rad($lng - self::LNG);

        $a = sin($Δφ / 2) ** 2
            + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function withinRadius(float $lat, float $lng, ?int $radius = null): bool
    {
        return self::distanceMeters($lat, $lng) <= ($radius ?? self::radiusMeters());
    }

    /**
     * P2: đánh giá toạ độ client.
     *
     * @return array{
     *   provided:bool,
     *   ok:bool,
     *   lat:?float,
     *   lng:?float,
     *   accuracy_m:?int,
     *   distance_m:?int,
     *   radius_m:int,
     *   note:string
     * }
     */
    public static function evaluateGps(
        mixed $lat,
        mixed $lng,
        mixed $accuracy = null,
        ?int $radius = null
    ): array {
        $radiusM = $radius ?? self::radiusMeters();
        $base = [
            'provided' => false,
            'ok' => false,
            'lat' => null,
            'lng' => null,
            'accuracy_m' => null,
            'distance_m' => null,
            'radius_m' => $radiusM,
            'note' => 'Chưa có toạ độ GPS.',
        ];

        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return $base;
        }
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return array_merge($base, ['note' => 'Toạ độ GPS không hợp lệ.']);
        }

        $latF = (float) $lat;
        $lngF = (float) $lng;
        if ($latF < -90 || $latF > 90 || $lngF < -180 || $lngF > 180) {
            return array_merge($base, ['note' => 'Toạ độ GPS ngoài phạm vi.']);
        }

        $acc = is_numeric($accuracy) ? (int) round((float) $accuracy) : null;
        $dist = (int) round(self::distanceMeters($latF, $lngF));
        $within = $dist <= $radiusM;

        $accNote = '';
        $accOk = true;
        if ($acc !== null && $acc > self::MAX_ACCURACY_M) {
            $accOk = false;
            $accNote = ' Độ chính xác ±'.$acc.'m kém (ngưỡng '.self::MAX_ACCURACY_M.'m).';
        }

        $ok = $within && $accOk;

        return [
            'provided' => true,
            'ok' => $ok,
            'lat' => $latF,
            'lng' => $lngF,
            'accuracy_m' => $acc,
            'distance_m' => $dist,
            'radius_m' => $radiusM,
            'note' => $within
                ? ('GPS trong bán kính campus ('.$dist.'m / '.$radiusM.'m).'.$accNote)
                : ('GPS ngoài campus ('.$dist.'m > '.$radiusM.'m).'.$accNote),
        ];
    }

    /** @return array{lat:float,lng:float,radius_m:int,address:string,max_accuracy_m:int} */
    public static function meta(): array
    {
        return [
            'lat' => self::LAT,
            'lng' => self::LNG,
            'radius_m' => self::radiusMeters(),
            'address' => self::ADDRESS,
            'max_accuracy_m' => self::MAX_ACCURACY_M,
        ];
    }
}
