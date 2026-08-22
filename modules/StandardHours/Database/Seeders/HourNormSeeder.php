<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\HourNorm;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Services\HourNormService;
use Modules\StandardHours\Services\PeriodService;

/**
 * Định mức giờ chuẩn năm — TT 06/2026/TT-BQP Điều 11 (nhà giáo không giữ chức vụ).
 * 01 giờ chuẩn = 03 giờ hành chính (Điều 3.4).
 * CDHC2 = trường cao đẳng → mặc định 380 GC.
 */
class HourNormSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(HourNormService::class);
        $year = $service->getCurrentYear();
        $periodMode = app(PeriodService::class)->mode();

        // Điều 11.1 a–c
        $objectTypes = [
            [
                'name' => 'Học viện / Trường sĩ quan / Đại học',
                'aliases' => ['Đối tượng 01', 'Đại học'],
                'hours' => 280, // = 840 giờ HC
            ],
            [
                'name' => 'Cao đẳng',
                'aliases' => ['Đối tượng 02'],
                'hours' => 380, // = 1.140 giờ HC — CDHC2
            ],
            [
                'name' => 'Trung cấp / Trường quân sự',
                'aliases' => ['Đối tượng 03', 'Trung cấp'],
                'hours' => 430, // = 1.290 giờ HC
            ],
        ];

        $position = Position::where('name', 'Giảng viên')->first();
        if (! $position) {
            return;
        }

        foreach ($objectTypes as $item) {
            $objectType = ObjectType::firstOrCreate(
                ['name' => $item['name']],
                ['is_active' => true]
            );

            // Đồng bộ alias cũ → cùng định mức (nếu còn record gắn tên cũ)
            foreach ($item['aliases'] as $alias) {
                $old = ObjectType::where('name', $alias)->first();
                if ($old) {
                    HourNorm::updateOrCreate(
                        [
                            'object_type_id' => $old->id,
                            'position_id' => $position->id,
                            'year' => $year,
                            'period_mode' => $periodMode,
                        ],
                        [
                            'standard_hours' => $item['hours'],
                            'is_active' => true,
                        ]
                    );
                }
            }

            HourNorm::updateOrCreate(
                [
                    'object_type_id' => $objectType->id,
                    'position_id' => $position->id,
                    'year' => $year,
                    'period_mode' => $periodMode,
                ],
                [
                    'standard_hours' => $item['hours'],
                    'is_active' => true,
                ]
            );
        }
    }
}
