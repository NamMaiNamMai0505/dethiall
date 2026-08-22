<?php

namespace Modules\StandardHours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\ResearchNorm;
use Modules\StandardHours\Services\HourNormService;
use Modules\StandardHours\Services\PeriodService;

/**
 * Định mức NCKH (giờ hành chính) — TT 06/2026/TT-BQP Điều 13.
 * CDHC2 = cao đẳng → 300 giờ HC.
 */
class ResearchNormSeeder extends Seeder
{
    public function run(): void
    {
        $year = app(HourNormService::class)->getCurrentYear();
        $periodMode = app(PeriodService::class)->mode();

        $norms = [
            [
                'name' => 'Học viện / Trường sĩ quan / Đại học',
                'aliases' => ['Đại học'],
                'hours' => 600,
            ],
            [
                'name' => 'Cao đẳng',
                'aliases' => [],
                'hours' => 300,
            ],
            [
                'name' => 'Trung cấp / Trường quân sự',
                'aliases' => ['Trung cấp'],
                'hours' => 150,
            ],
        ];

        foreach ($norms as $norm) {
            $names = array_merge([$norm['name']], $norm['aliases']);
            foreach ($names as $name) {
                $objectType = ObjectType::firstOrCreate(
                    ['name' => $name],
                    ['is_active' => true]
                );

                ResearchNorm::updateOrCreate(
                    [
                        'object_type_id' => $objectType->id,
                        'year' => $year,
                        'period_mode' => $periodMode,
                    ],
                    [
                        'research_hours' => $norm['hours'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
